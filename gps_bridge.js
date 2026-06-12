/**
 * AgroMind GPS Bridge - Jimi/Concox JM-VG03 (GT06 Protocol) TCP Server
 * 
 * Ushbu Node.js skripti real GPS trekkerlaridan TCP orqali keladigan raw (ikkilik)
 * ma'lumotlarni qabul qiladi, shifrlaydi va Laravel backend API-ga yuboradi.
 * 
 * Ishga tushirish uchun:
 * 1. Node.js o'rnatilgan bo'lishi kerak.
 * 2. Skript joylashgan papkada `npm install axios` buyrug'ini bering.
 * 3. `node gps_bridge.js` buyrug'i orqali ishga tushiring.
 * 4. Ngrok orqali tashqi tarmoqqa ochish: `ngrok tcp 5000`
 */

import net from 'net';
import axios from 'axios';
import http from 'http';

// SOZLAMALAR
const PORT = 5000; // GPS trekker ulanadigan port
const LARAVEL_API_URL = 'http://localhost:8000/api/telemetry'; // Laravel API manzili
const HTTP_PORT = 5001; // Buyruqlar qabul qilish HTTP porti

// Faol soket ulanishlarini IMEI bo'yicha saqlash
const activeSockets = new Map();

// CRC-16/X-25 (CRC16) hisoblash funksiyasi (GT06 protokoli uchun zarur)
function getCRC16(buffer) {
    let crc = 0xFFFF;
    for (let i = 0; i < buffer.length; i++) {
        crc ^= buffer[i];
        for (let j = 0; j < 8; j++) {
            if (crc & 0x0001) {
                crc = (crc >> 1) ^ 0x8408;
            } else {
                crc >>= 1;
            }
        }
    }
    return (~crc) & 0xFFFF;
}

// BCD formatidagi baytlarni o'qiladigan satrga (IMEI) o'tkazish
function parseBCD(buffer) {
    let result = '';
    for (let i = 0; i < buffer.length; i++) {
        let high = (buffer[i] >> 4) & 0x0F;
        let low = buffer[i] & 0x0F;
        result += high.toString() + low.toString();
    }
    return result;
}

// Javob (ACK) paketini tayyorlash
function buildACK(protocolNumber, serialNo) {
    const header = Buffer.from([0x78, 0x78]);
    const length = Buffer.from([0x05]);
    const protocol = Buffer.from([protocolNumber]);
    const serial = Buffer.from(serialNo);
    
    // CRC hisoblash (length + protocol + serial ustida)
    const crcData = Buffer.concat([length, protocol, serial]);
    const crcVal = getCRC16(crcData);
    const crc = Buffer.alloc(2);
    crc.writeUInt16BE(crcVal, 0);

    const footer = Buffer.from([0x0D, 0x0A]);

    return Buffer.concat([header, length, protocol, serial, crc, footer]);
}

// Buyruq paketini (protocol 0x80) tayyorlash
function buildCommandPacket(commandStr, serialNo) {
    const cmdBytes = Buffer.from(commandStr, 'ascii');
    const serverFlag = Buffer.from([0x00, 0x00, 0x00, 0x00]);
    const cmdLen = 4 + cmdBytes.length;
    
    // Command Info: 1 bayt cmd uzunligi + 4 bayt server bayrog'i + buyruq matni + 2 bayt til (Inglizcha: 0x00 0x02)
    const cmdInfo = Buffer.concat([
        Buffer.from([cmdLen]),
        serverFlag,
        cmdBytes,
        Buffer.from([0x00, 0x02])
    ]);
    
    const protocol = Buffer.from([0x80]);
    const serial = Buffer.alloc(2);
    serial.writeUInt16BE(serialNo, 0);
    
    const lengthVal = 1 + cmdInfo.length + 2 + 2; // protocol + cmdInfo + serial + crc
    const length = Buffer.from([lengthVal]);
    
    const crcData = Buffer.concat([length, protocol, cmdInfo, serial]);
    const crcVal = getCRC16(crcData);
    const crc = Buffer.alloc(2);
    crc.writeUInt16BE(crcVal, 0);
    
    const header = Buffer.from([0x78, 0x78]);
    const footer = Buffer.from([0x0D, 0x0A]);
    
    return Buffer.concat([header, length, protocol, cmdInfo, serial, crc, footer]);
}

const server = net.createServer((socket) => {
    let deviceIMEI = null;
    console.log(`\n[+] Yangi ulanish: ${socket.remoteAddress}:${socket.remotePort}`);

    socket.on('data', async (data) => {
        console.log(`[RAW Data Received] (${data.length} bytes): ${data.toString('hex').toUpperCase()}`);

        // Eng kamida start(2) + length(1) + protocol(1) + serial(2) + crc(2) + stop(2) = 10 bayt bo'lishi kerak
        if (data.length < 10) return;

        // Start bitlarini tekshirish (0x78 0x78)
        if (data[0] !== 0x78 || data[1] !== 0x78) {
            console.log('[-] Noto\'g\'ri start paket.');
            return;
        }

        const length = data[2];
        const protocolNumber = data[3];
        const serialNo = [data[data.length - 6], data[data.length - 5]]; // Serial number oxiridan oldingi 5 va 6-baytlar

        // 1. LOGIN PACKET (0x01) - IMEI shu erda keladi
        if (protocolNumber === 0x01) {
            const imeiBuffer = data.slice(4, 12);
            deviceIMEI = parseBCD(imeiBuffer);
            // Concox / Jimi odatda 16 ta raqam qaytarishi mumkin, birinchi '0' ni olib tashlaymiz
            if (deviceIMEI.startsWith('0') && deviceIMEI.length > 15) {
                deviceIMEI = deviceIMEI.substring(1);
            }
            console.log(`[🔑 Login] Qurilma IMEI: ${deviceIMEI}`);

            // Soketni xotirada saqlash
            activeSockets.set(deviceIMEI, socket);
            console.log(`[Active Sockets] Ro'yxatga olindi: ${deviceIMEI}. Jami faol: ${activeSockets.size}`);

            // Javob yuborish (ACK) - agar yuborilmasa qurilma uzilib qoladi
            const ack = buildACK(0x01, serialNo);
            socket.write(ack);
            console.log(`[➔ ACK Sent] Login muvaffaqiyatli tasdiqlandi.`);
        }
        
        // 2. LOCATION PACKET (0x12 yoki 0x22)
        else if (protocolNumber === 0x12 || protocolNumber === 0x22) {
            if (!deviceIMEI) {
                console.log('[-] GPS Ma\'lumot keldi, lekin login qilinmagan.');
                return;
            }

            console.log(`[🛰 GPS Data] Joylashuv paketi qabul qilindi.`);

            // Kenglik va uzunlikni o'qish (GT06 spetsifikatsiyasi bo'yicha)
            // Lat: 4 bayt, Lng: 4 bayt
            const latRaw = data.readUInt32BE(11);
            const lngRaw = data.readUInt32BE(15);

            let latitude = latRaw / 1800000;
            let longitude = lngRaw / 1800000;

            // Course and Status flags: 2 bytes starting at index 20 (high byte: data[20], low byte: data[21])
            const flags = data.readUInt16BE(20);

            // Bit 10 determines latitude direction: 1 = North, 0 = South (negate if South)
            const isLatNorth = (flags & (1 << 10)) !== 0;
            if (!isLatNorth) latitude = -latitude;

            // Bit 11 determines longitude direction: 1 = West, 0 = East (negate if West)
            const isLngWest = (flags & (1 << 11)) !== 0;
            if (isLngWest) longitude = -longitude;

            // Tezlik: 1 bayt (index 19)
            const speed = data[19];

            console.log(`[📊 Telemetriya] IMEI: ${deviceIMEI} | Lat: ${latitude.toFixed(6)} | Lng: ${longitude.toFixed(6)} | Tezlik: ${speed} km/soat`);

            // Laravel backendga jo'natish
            try {
                const response = await axios.post(LARAVEL_API_URL, {
                    device_id: deviceIMEI,
                    latitude: latitude,
                    longitude: longitude,
                    speed: speed,
                    fuel_level: 75.0, // Agar datchik bo'lsa uni ham o'qish mumkin
                    signal_strength: 90
                });
                console.log(`[➔ Laravel API] Javob:`, response.data);
            } catch (err) {
                console.error(`[❌ Laravel API Xatolik]:`, err.message);
            }

            // Ba'zi Concox modellar location uchun ham ACK talab qiladi
            if (protocolNumber === 0x12) {
                const ack = buildACK(0x12, serialNo);
                socket.write(ack);
            }
        }

        // 3. STATUS/HEARTBEAT PACKET (0x13)
        else if (protocolNumber === 0x13) {
            console.log(`[💓 Heartbeat] Status paketi qabul qilindi.`);
            const ack = buildACK(0x13, serialNo);
            socket.write(ack);
        }
    });

    // Socket faolsizlik taymauti (3 daqiqa) - aloqa uzilganini aniqlash uchun
    socket.setTimeout(180000);
    socket.on('timeout', () => {
        console.log(`[-] Soket taymauti (3 daqiqa faolsizlik): ${socket.remoteAddress}`);
        socket.destroy();
    });

    socket.on('close', () => {
        console.log(`[-] Ulanish yopildi: ${socket.remoteAddress}`);
        if (deviceIMEI && activeSockets.get(deviceIMEI) === socket) {
            activeSockets.delete(deviceIMEI);
            console.log(`[Active Sockets] O'chirildi: ${deviceIMEI}. Jami faol: ${activeSockets.size}`);
        }
    });

    socket.on('error', (err) => {
        console.error(`[❌ Xatolik]:`, err.message);
        if (deviceIMEI && activeSockets.get(deviceIMEI) === socket) {
            activeSockets.delete(deviceIMEI);
            console.log(`[Active Sockets] Xatolik sabab o'chirildi: ${deviceIMEI}. Jami faol: ${activeSockets.size}`);
        }
    });
});

server.listen(PORT, () => {
    console.log(`=======================================================`);
    console.log(`📡 AgroMind GPS Bridge ishga tushdi.`);
    console.log(`👉 TCP port: ${PORT} da real GPS trekkerlarni kutmoqda...`);
    console.log(`👉 Laravel API: ${LARAVEL_API_URL}`);
    console.log(`=======================================================`);
});

// Laravel-dan keladigan buyruqlarni qabul qiluvchi HTTP Server
const httpServer = http.createServer((req, res) => {
    // Xavfsizlik: Faqat Localhost va Ichki Docker tarmog'idan kelgan so'rovlarni qabul qilish
    const remoteIp = req.socket.remoteAddress || '';
    const isLocal = remoteIp === '127.0.0.1' || remoteIp === '::1' || remoteIp === '::ffff:127.0.0.1' ||
                    remoteIp.startsWith('172.') || remoteIp.startsWith('::ffff:172.') ||
                    remoteIp.startsWith('10.') || remoteIp.startsWith('::ffff:10.') ||
                    remoteIp.startsWith('192.168.') || remoteIp.startsWith('::ffff:192.168.');

    if (!isLocal) {
        console.warn(`[HTTP API] Ruxsat etilmagan kirish urinishi (IP: ${remoteIp})`);
        res.writeHead(403, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ success: false, error: 'Access denied: Unauthorized origin.' }));
        return;
    }

    if (req.method === 'POST' && req.url === '/send-command') {
        let body = '';
        req.on('data', chunk => { body += chunk; });
        req.on('end', () => {
            try {
                const payload = JSON.parse(body);
                const { imei, action } = payload;
                
                if (!imei || !action) {
                    res.writeHead(400, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({ success: false, error: 'IMEI va action parametri yuborilmadi.' }));
                    return;
                }
                
                const clientSocket = activeSockets.get(imei);
                if (!clientSocket) {
                    res.writeHead(404, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({ success: false, error: 'Trekker aloqada emas (offline).' }));
                    return;
                }
                
                let commands = [];
                if (action === 'cutoff') {
                    commands = ['RELAY,1#', 'DYD,000000#'];
                } else if (action === 'restore') {
                    commands = ['RELAY,0#', 'HFYD,000000#'];
                } else {
                    res.writeHead(400, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({ success: false, error: 'Noto\'g\'ri action. cutoff yoki restore ishlating.' }));
                    return;
                }
                
                console.log(`[HTTP Command] IMEI: ${imei} | Action: ${action} yuborilmoqda...`);
                
                let serialNo = Math.floor(Math.random() * 1000) + 1;
                for (const cmd of commands) {
                    const packet = buildCommandPacket(cmd, serialNo++);
                    clientSocket.write(packet);
                    console.log(`[HTTP Command] Trekkerga yuborildi: ${cmd} | Hex: ${packet.toString('hex').toUpperCase()}`);
                }
                
                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: true, message: `Dvigatel buyrug'i (${action}) jo'natildi.` }));
                
            } catch (err) {
                res.writeHead(500, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: false, error: err.message }));
            }
        });
    } else {
        res.writeHead(404);
        res.end();
    }
});

httpServer.listen(HTTP_PORT, '0.0.0.0', () => {
    console.log(`[HTTP API] Buyruq qabul qiluvchi port: http://0.0.0.0:${HTTP_PORT} (Faqat ichki tarmoq uchun ochiq)`);
});
