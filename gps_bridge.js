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

const net = require('net');
const axios = require('axios');

// SOZLAMALAR
const PORT = 5000; // GPS trekker ulanadigan port
const LARAVEL_API_URL = 'http://localhost/api/telemetry'; // Laravel API manzili

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

            let latitude = latRaw / (1800000 * 60);
            let longitude = lngRaw / (1800000 * 60);

            // Koordinata yo'nalishlarini aniqlash (Janubiy/G'arbiy holatlari uchun)
            const courseStatus = data[19];
            const isLatNorth = (courseStatus & 0x04) === 0;
            const isLngEast = (courseStatus & 0x08) !== 0;

            if (!isLatNorth) latitude = -latitude;
            if (!isLngEast) longitude = -longitude;

            // Tezlik: 1 bayt
            const speed = data[20];

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

    socket.on('close', () => {
        console.log(`[-] Ulanish yopildi: ${socket.remoteAddress}`);
    });

    socket.on('error', (err) => {
        console.error(`[❌ Xatolik]:`, err.message);
    });
});

server.listen(PORT, () => {
    console.log(`=======================================================`);
    console.log(`📡 AgroMind GPS Bridge ishga tushdi.`);
    console.log(`👉 TCP port: ${PORT} da real GPS trekkerlarni kutmoqda...`);
    console.log(`👉 Laravel API: ${LARAVEL_API_URL}`);
    console.log(`=======================================================`);
});
