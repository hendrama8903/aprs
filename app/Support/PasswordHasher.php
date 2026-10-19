<?php

namespace App\Support;

/**
 * Hash password akun lokal dengan PBKDF2-SHA256, port dari Helpers/PasswordHasher.cs.
 * Format tersimpan: PBKDF2$<iterations>$<salt base64>$<hash base64>
 *
 * HARUS backward-compatible dengan hash yang sudah tersimpan di UserAccount.PasswordHash
 * (dibuat oleh aplikasi C# asli) supaya user lama tetap bisa login.
 */
final class PasswordHasher
{
    private const SALT_SIZE = 16;
    private const HASH_SIZE = 32;
    private const ITERATIONS = 100000;

    public static function hash(string $password): string
    {
        $salt = random_bytes(self::SALT_SIZE);
        $hash = hash_pbkdf2('sha256', $password, $salt, self::ITERATIONS, self::HASH_SIZE, true);

        return implode('$', ['PBKDF2', self::ITERATIONS, base64_encode($salt), base64_encode($hash)]);
    }

    public static function verify(string $password, ?string $storedHash): bool
    {
        if ($password === '' || $storedHash === null || $storedHash === '') {
            return false;
        }

        $parts = explode('$', $storedHash);
        if (count($parts) !== 4 || $parts[0] !== 'PBKDF2') {
            return false;
        }

        $iterations = (int) $parts[1];
        if ($iterations <= 0) {
            return false;
        }

        $salt = base64_decode($parts[2], true);
        $expected = base64_decode($parts[3], true);
        if ($salt === false || $expected === false) {
            return false;
        }

        $actual = hash_pbkdf2('sha256', $password, $salt, $iterations, strlen($expected), true);

        return hash_equals($expected, $actual);
    }
}
