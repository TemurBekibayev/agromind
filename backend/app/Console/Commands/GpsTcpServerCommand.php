<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Services\GpsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GpsTcpServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gps:tcp-server {--port=5000 : Port to listen on}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Jimi/Concox JM-VG03 (GT06 Protocol) TCP Socket Server to receive real GPS telemetry';

    protected $gpsService;

    public function __construct(GpsService $gpsService)
    {
        parent::__construct();
        $this->gpsService = $gpsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $port = (int) $this->option('port');
        $address = '0.0.0.0';

        // TCP Socket yaratish
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            $this->error("Socket yaratishda xatolik: " . socket_strerror(socket_last_error()));
            return 1;
        }

        // Portni band qilish
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        if (socket_bind($socket, $address, $port) === false) {
            $this->error("Socket bind qilishda xatolik ($address:$port): " . socket_strerror(socket_last_error($socket)));
            return 1;
        }

        // Ulanishlarni tinglash
        if (socket_listen($socket, 5) === false) {
            $this->error("Socket tinglashda xatolik: " . socket_strerror(socket_last_error($socket)));
            return 1;
        }

        $this->info("==================================================================");
        $this->info("📡 AgroMind GPS Real TCP Server ishga tushdi!");
        $this->info("👉 Port: $port da real GPS trekkerlarni kutmoqda...");
        $this->info("==================================================================");

        // Ulanishlarni blokirovka qilmasdan boshqarish uchun socketni non-blocking rejimga o'tkazish mumkin, 
        // lekin soddalik va barqarorlik uchun ulanishlarni navbat bilan qabul qilamiz (singled-threaded loop).
        
        while (true) {
            $clientSocket = socket_accept($socket);
            if ($clientSocket === false) {
                continue;
            }

            // Ulanish uchun 120 soniyalik o'qish taymautini o'rnatish (stale ulanishlarni tozalash uchun)
            socket_set_option($clientSocket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 120, 'usec' => 0]);

            // Mijoz ma'lumotlarini olish
            socket_getpeername($clientSocket, $clientIp, $clientPort);
            $this->info("\n[+] Yangi ulanish: $clientIp:$clientPort");

            $deviceIMEI = null;

            while (true) {
                // Ma'lumotlarni o'qish (1024 baytgacha)
                $data = @socket_read($clientSocket, 1024, PHP_BINARY_READ);
                
                if ($data === false || $data === '') {
                    $this->warn("[-] Ulanish yopildi: $clientIp:$clientPort");
                    break;
                }

                $bytes = unpack('C*', $data);
                $hex = bin2hex($data);
                $this->line("[RAW Data Received] (" . count($bytes) . " bytes): " . strtoupper($hex));

                if (count($bytes) < 10) {
                    continue;
                }

                // Start baytlari: 0x78 0x78 yoki 0x79 0x79
                $isExtended = false;
                if ($bytes[1] === 0x78 && $bytes[2] === 0x78) {
                    $isExtended = false;
                } elseif ($bytes[1] === 0x79 && $bytes[2] === 0x79) {
                    $isExtended = true;
                } else {
                    $this->error("[-] Noto'g'ri start bitlari: " . sprintf("0x%02X 0x%02X", $bytes[1], $bytes[2]));
                    continue;
                }

                if ($isExtended) {
                    $length = ($bytes[3] << 8) | $bytes[4];
                    $protocolNumber = $bytes[5];
                    $offset = 1;
                } else {
                    $length = $bytes[3];
                    $protocolNumber = $bytes[4];
                    $offset = 0;
                }
                
                // Serial number (oxiridan oldingi 5 va 6-baytlar)
                $serialNo = array_slice($bytes, count($bytes) - 5, 2);

                // 1. LOGIN PACKET (0x01) - IMEI shu erda o'qiladi
                if ($protocolNumber === 0x01) {
                    // IMEI 8 bayt BCD formatida
                    $imeiStartByte = 4 + $offset;
                    $imeiBytes = array_slice($bytes, $imeiStartByte, 8);
                    $deviceIMEI = $this->parseBCD($imeiBytes);
                    
                    if (str_starts_with($deviceIMEI, '0') && strlen($deviceIMEI) > 15) {
                        $deviceIMEI = substr($deviceIMEI, 1);
                    }

                    $this->info("[🔑 Login] Qurilma IMEI: $deviceIMEI");

                    // ACK (tasdiqlash) javobini jo'natish - ulanish uzilib qolmasligi uchun shart!
                    $ack = $this->buildACK(0x01, $serialNo, $isExtended);
                    @socket_write($clientSocket, $ack, strlen($ack));
                    $this->info("[➔ ACK Sent] Login tasdiqlandi.");
                }

                // 2. LOCATION PACKET (0x12 yoki 0x22)
                elseif ($protocolNumber === 0x12 || $protocolNumber === 0x22) {
                    if (!$deviceIMEI) {
                        $this->warn("[-] GPS Ma'lumot keldi, lekin login qilinmagan.");
                        continue;
                    }

                    $this->info("[🛰 GPS Data] Joylashuv paketi qabul qilindi.");

                    // GT06 protokoli bo'yicha kenglik va uzunlik (11-baytdan boshlab 4 baytdan)
                    // Pack/unpack orqali 32-bitli butun sonni BE (Big Endian) rejimida o'qiymiz
                    $latRaw = unpack('N', substr($data, 11 + $offset, 4))[1];
                    $lngRaw = unpack('N', substr($data, 15 + $offset, 4))[1];

                    $latitude = $latRaw / 1800000;
                    $longitude = $lngRaw / 1800000;

                    // Yo'nalish va holat baytlari (21 + offset va 22 + offset baytlar - jami 2 bayt)
                    $courseStatus = ($bytes[21 + $offset] << 8) | $bytes[22 + $offset];
                    $isLatNorth = ($courseStatus & 0x0400) !== 0; // Bit 10: 1 = North, 0 = South
                    $isLngEast = ($courseStatus & 0x0800) === 0;   // Bit 11: 0 = East, 1 = West

                    if (!$isLatNorth) $latitude = -$latitude;
                    if (!$isLngEast) $longitude = -$longitude;

                    // Tezlik (20 + offset-bayt)
                    $speed = $bytes[20 + $offset];

                    $this->info("[📊 Telemetriya] IMEI: $deviceIMEI | Lat: " . number_format($latitude, 6) . " | Lng: " . number_format($longitude, 6) . " | Tezlik: $speed km/soat");

                    // Bazadan texnikani qidirish (IMEI orqali)
                    $vehicle = Vehicle::where('gps_device_id', $deviceIMEI)->first();

                    if ($vehicle) {
                        // Telemetriyani bazaga yozish va ogohlantirishlarni tekshirish
                        $track = $this->gpsService->processIncoming([
                            'vehicle_id' => $vehicle->id,
                            'latitude' => $latitude,
                            'longitude' => $longitude,
                            'speed' => (double) $speed,
                            'fuel_level' => 75.0, // Virtual yoqilg'i darajasi datchik bo'lmasa
                            'signal_strength' => 90,
                        ]);
                        $this->info("[➔ Database] Koordinatalar bazaga yozildi. ID: {$track->id}");
                    } else {
                        $this->warn("[⚠️ Warning] IMEI: $deviceIMEI raqamli texnika bazada ro'yxatdan o'tmagan!");
                    }

                    // Location uchun ham ACK qaytaramiz (0x12 holatida)
                    if ($protocolNumber === 0x12) {
                        $ack = $this->buildACK(0x12, $serialNo, $isExtended);
                        @socket_write($clientSocket, $ack, strlen($ack));
                    }
                }

                // 3. HEARTBEAT/STATUS PACKET (0x13)
                elseif ($protocolNumber === 0x13) {
                    $this->info("[💓 Heartbeat] Status paketi qabul qilindi.");
                    $ack = $this->buildACK(0x13, $serialNo, $isExtended);
                    @socket_write($clientSocket, $ack, strlen($ack));
                }

                // 4. INFORMATION TRANSMISSION PACKET (0x94)
                elseif ($protocolNumber === 0x94) {
                    $this->info("[ℹ️ Info Packet] Kengaytirilgan ma'lumot paketi qabul qilindi (0x94).");
                    $ack = $this->buildACK(0x94, $serialNo, $isExtended);
                    @socket_write($clientSocket, $ack, strlen($ack));
                    $this->info("[➔ ACK Sent] Info paketi (0x94) tasdiqlandi.");
                }

                // Kutilayotgan buyruq bormi cache da?
                if ($deviceIMEI) {
                    $pendingCommand = \Illuminate\Support\Facades\Cache::pull("gps_command_{$deviceIMEI}");
                    if ($pendingCommand) {
                        $this->info("[📡 Command] Kutilayotgan buyruq topildi: $pendingCommand. Qurilmaga yuborilmoqda...");
                        $cmdPacket = $this->buildCommandPacket($pendingCommand, $serialNo);
                        @socket_write($clientSocket, $cmdPacket, strlen($cmdPacket));
                        $this->info("[➔ Command Sent] Hex: " . strtoupper(bin2hex($cmdPacket)));
                    }
                }
            }

            @socket_close($clientSocket);
        }

        @socket_close($socket);
    }

    /**
     * BCD formatidagi baytlarni IMEI satriga o'tkazish.
     */
    protected function parseBCD(array $bytes): string
    {
        $imei = '';
        foreach ($bytes as $byte) {
            $high = ($byte >> 4) & 0x0F;
            $low = $byte & 0x0F;
            $imei .= dechex($high) . dechex($low);
        }
        return $imei;
    }

    /**
     * CRC-ITU (CRC16) hisoblash.
     */
    protected function getCRC16(string $data): int
    {
        $crc = 0xFFFF;
        $len = strlen($data);
        for ($i = 0; $i < $len; $i++) {
            $crc ^= ord($data[$i]);
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x0001) {
                    $crc = ($crc >> 1) ^ 0x8408;
                } else {
                    $crc >>= 1;
                }
            }
        }
        return ~$crc & 0xFFFF;
    }

    /**
     * GT06 protokoli bo'yicha ACK paketi yaratish.
     */
    protected function buildACK(int $protocolNumber, array $serialNo, bool $extended = false): string
    {
        $protocol = pack('C', $protocolNumber);
        $serial = pack('C*', ...$serialNo);

        if ($extended) {
            $header = pack('C*', 0x79, 0x79);
            // Extended ACK length: 1 byte protocol + 2 bytes serial + 2 bytes CRC = 5 bytes
            $lengthVal = 5;
            $length = pack('n', $lengthVal); // 16-bit BE
            
            $crcData = $length . $protocol . $serial;
            $crcVal = $this->getCRC16($crcData);
            $crc = pack('n', $crcVal);
            
            $footer = pack('C*', 0x0D, 0x0A);
            return $header . $length . $protocol . $serial . $crc . $footer;
        } else {
            $header = pack('C*', 0x78, 0x78);
            // Standard ACK length: 5 bytes
            $length = pack('C', 0x05);
            
            $crcData = $length . $protocol . $serial;
            $crcVal = $this->getCRC16($crcData);
            $crc = pack('n', $crcVal);
            
            $footer = pack('C*', 0x0D, 0x0A);
            return $header . $length . $protocol . $serial . $crc . $footer;
        }
    }

    /**
     * GT06 protokoli bo'yicha onlayn buyruq (0x80) paketini yaratish.
     */
    protected function buildCommandPacket(string $command, array $serialNo): string
    {
        $protocol = 0x80;
        $cmdLength = 4 + strlen($command); // 4 bayt server flag + buyruq uzunligi
        $serverFlag = pack('N', 0); // 4 bayt 0x00
        
        $body = pack('C', $protocol) . pack('C', $cmdLength) . $serverFlag . $command . pack('C*', ...$serialNo);
        
        $lengthVal = strlen($body) + 2; // protocol + content + serial + crc
        $length = pack('C', $lengthVal);
        
        $header = pack('C*', 0x78, 0x78);
        
        $crcData = $length . $body;
        $crcVal = $this->getCRC16($crcData);
        $crc = pack('n', $crcVal);
        
        $footer = pack('C*', 0x0D, 0x0A);
        
        return $header . $length . $body . $crc . $footer;
    }
}
