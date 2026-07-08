<?php

declare(strict_types=1);

namespace PhiloQuest;

final class LoginThrottle
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 900;

    public static function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function isBlocked(?int &$retryAfterSeconds = null): bool
    {
        $data = self::readState();
        if ($data === null) {
            return false;
        }

        $lockedUntil = (int) ($data['locked_until'] ?? 0);
        if ($lockedUntil > time()) {
            $retryAfterSeconds = $lockedUntil - time();
            return true;
        }

        if ($lockedUntil > 0) {
            self::clear();
        }

        return false;
    }

    public static function recordFailure(): void
    {
        $data = self::readState() ?? ['attempts' => 0, 'locked_until' => 0];
        $data['attempts'] = (int) ($data['attempts'] ?? 0) + 1;

        if ($data['attempts'] >= self::MAX_ATTEMPTS) {
            $data['locked_until'] = time() + self::LOCKOUT_SECONDS;
            $data['attempts'] = 0;
        }

        self::writeState($data);
    }

    public static function clear(): void
    {
        $path = self::statePath();
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public static function blockedMessage(int $retryAfterSeconds): string
    {
        $minutos = max(1, (int) ceil($retryAfterSeconds / 60));
        return "Muitas tentativas de login. Aguarde {$minutos} minuto(s) e tente novamente.";
    }

    private static function storageDir(): string
    {
        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'rate_limit';
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        return $dir;
    }

    private static function statePath(): string
    {
        return self::storageDir() . DIRECTORY_SEPARATOR . hash('sha256', self::clientIp()) . '.json';
    }

    /** @return array{attempts?: int, locked_until?: int}|null */
    private static function readState(): ?array
    {
        $path = self::statePath();
        if (!is_readable($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    /** @param array{attempts?: int, locked_until?: int} $data */
    private static function writeState(array $data): void
    {
        file_put_contents(
            self::statePath(),
            json_encode($data, JSON_THROW_ON_ERROR),
            LOCK_EX
        );
    }
}
