<?php
// adminpanel/config.php
// Contains encryption key for social tokens - change ENCRYPTION_KEY before production use.

define('ENCRYPTION_KEY', 'FjVnzhAJjExZ3QEJsaKsAhAFCm6xNX6RxqVJHtE59Zv9b02mk8HFMXTmFIFEXE82');
define('ENCRYPTION_CIPHER', 'aes-256-cbc');

function encryption_key_bytes(): string {
    return hash('sha256', ENCRYPTION_KEY, true);
}

function encrypt_token(string $token): string {
    if ($token === '') return '';
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(ENCRYPTION_CIPHER));
    $encrypted = openssl_encrypt($token, ENCRYPTION_CIPHER, encryption_key_bytes(), 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function decrypt_token(string $encrypted_token): string {
    if ($encrypted_token === '') return '';
    $decoded = base64_decode($encrypted_token, true);
    if ($decoded === false || strpos($decoded, '::') === false) return '';
    [$encrypted, $iv] = explode('::', $decoded, 2);
    return openssl_decrypt($encrypted, ENCRYPTION_CIPHER, encryption_key_bytes(), 0, $iv) ?: '';
}
?>