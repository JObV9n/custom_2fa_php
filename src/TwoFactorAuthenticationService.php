<?php

namespace Custom2FA;

use RuntimeException;

class TwoFactorAuthenticationService
{
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const QR_CODE_SIZE = '300x300';
    private const TOTP_WINDOW = 1;

    public function generateSecretKey(int $length = 32): string
    {
        $bytes = random_bytes(($length * 5) / 8);
        return $this->base32Encode($bytes);
    }

    public function getQRCodeUrl(string $email, string $secret): string
    {
        $appName = 'MyTOTPTest';
        $issuer = urlencode($appName);
        $label = urlencode("{$appName}:{$email}");

        $otpauth = "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&digits=6&period=30";

        return 'https://chart.googleapis.com/chart?' . http_build_query([
            'chs' => self::QR_CODE_SIZE,
            'cht' => 'qr',
            'chl' => $otpauth,
            'chld' => 'M|0',
        ]);
    }

    public function verifyCode(string $secret, string $code, int $window = self::TOTP_WINDOW): bool
    {
        $code = preg_replace('/[^0-9]/', '', $code);
        if (strlen($code) !== 6) {
            return false;
        }

        $timeSlice = floor(time() / 30);

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals($this->generateCode($secret, $timeSlice + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    public function generateCode(string $secret, ?int $timeSlice = null): string
    {
        $timeSlice ??= floor(time() / 30);

        $secretKey = $this->base32Decode($secret);
        if ($secretKey === false) {
            throw new RuntimeException('Invalid Base32 secret key.');
        }

        $time = pack('N*', 0, $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);

        $offset = ord($hash[19]) & 0x0F;
        $code = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad($code % 1000000, 6, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $data): string
    {
        $binaryString = '';
        foreach (str_split($data) as $char) {
            $binaryString .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $chunks = str_split($binaryString, 5);
        $result = '';

        foreach ($chunks as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0');
            }
            $result .= self::BASE32_CHARS[bindec($chunk)];
        }

        $padding = strlen($binaryString) % 40;
        if ($padding !== 0) {
            $padCount = [0 => 0, 8 => 6, 16 => 4, 24 => 3, 32 => 1][$padding] ?? 0;
            $result .= str_repeat('=', $padCount);
        }

        return $result;
    }

    private function base32Decode(string $secret): string|false
    {
        $secret = strtoupper(rtrim($secret, '='));

        if ($secret === '') return '';

        $binaryString = '';
        foreach (str_split($secret) as $char) {
            $pos = strpos(self::BASE32_CHARS, $char);
            if ($pos === false) return false;
            $binaryString .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $bytes = [];
        foreach (str_split($binaryString, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $bytes[] = chr(bindec($chunk));
            }
        }

        return implode('', $bytes);
    }
}
