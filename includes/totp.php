<?php
/**
 * Minimal, dependency-free TOTP (RFC 6238), compatible with Google
 * Authenticator / Authy / any standard authenticator app. No Composer
 * packages needed — just PHP's built-in hash_hmac().
 */

function totp_generate_secret(int $bytes = 20): string
{
    return totp_base32_encode(random_bytes($bytes));
}

function totp_base32_encode(string $data): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    foreach (str_split($data) as $char) {
        $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
    }
    $output = '';
    foreach (str_split($bits, 5) as $chunk) {
        $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        $output .= $alphabet[bindec($chunk)];
    }
    return $output;
}

function totp_base32_decode(string $b32): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $b32));
    $bits = '';
    foreach (str_split($b32) as $char) {
        $pos = strpos($alphabet, $char);
        if ($pos === false) {
            continue;
        }
        $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $bytes = '';
    foreach (str_split($bits, 8) as $byte) {
        if (strlen($byte) < 8) {
            continue;
        }
        $bytes .= chr(bindec($byte));
    }
    return $bytes;
}

function totp_code(string $secretBase32, ?int $timestamp = null, int $period = 30, int $digits = 6): string
{
    $timestamp = $timestamp ?? time();
    $key = totp_base32_decode($secretBase32);
    $counter = intdiv($timestamp, $period);
    $binCounter = pack('N2', $counter >> 32, $counter & 0xFFFFFFFF);
    $hash = hash_hmac('sha1', $binCounter, $key, true);
    $offset = ord($hash[19]) & 0x0F;
    $truncated =
        ((ord($hash[$offset]) & 0x7F) << 24) |
        ((ord($hash[$offset + 1]) & 0xFF) << 16) |
        ((ord($hash[$offset + 2]) & 0xFF) << 8) |
        (ord($hash[$offset + 3]) & 0xFF);
    $code = $truncated % (10 ** $digits);
    return str_pad((string)$code, $digits, '0', STR_PAD_LEFT);
}

/**
 * Verifies a 6-digit code, allowing +/- 1 time step (30s) of clock drift.
 */
function totp_verify(string $secretBase32, string $inputCode, int $window = 1, int $period = 30): bool
{
    $inputCode = preg_replace('/\s+/', '', (string)$inputCode);
    if ($inputCode === '' || !ctype_digit($inputCode)) {
        return false;
    }
    $now = time();
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(totp_code($secretBase32, $now + ($i * $period), $period), $inputCode)) {
            return true;
        }
    }
    return false;
}

/**
 * otpauth:// URI for the secret. Most authenticator apps can add an account
 * either by scanning a QR code or by typing the secret in manually — since
 * we don't have a QR-code library available, we show the secret as text and
 * this URI, both of which work fine with manual entry.
 */
function totp_otpauth_uri(string $secretBase32, string $accountEmail, string $issuer): string
{
    return sprintf(
        'otpauth://totp/%s:%s?secret=%s&issuer=%s',
        rawurlencode($issuer),
        rawurlencode($accountEmail),
        $secretBase32,
        rawurlencode($issuer)
    );
}
