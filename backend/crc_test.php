<?php

$data = hex2bin('11010862292055529242806632010059');

// 1. Standard Reflected CCITT (X.25 style with final inversion)
function crc16_x25($data) {
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

// 2. Reflected CCITT (without final inversion)
function crc16_ccitt_no_inv($data) {
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
    return $crc;
}

echo "crc16_x25: " . dechex(crc16_x25($data)) . "\n";
echo "crc16_ccitt_no_inv: " . dechex(crc16_ccitt_no_inv($data)) . "\n";
