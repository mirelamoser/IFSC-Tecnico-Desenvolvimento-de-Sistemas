<?php

declare(strict_types=1);

namespace PhiloQuest;

final class Config
{
    private static bool $loaded = false;

    public static function load(string $rootPath): void
    {
        if (self::$loaded) {
            return;
        }

        $envFile = rtrim($rootPath, '/\\') . DIRECTORY_SEPARATOR . '.env';
        if (is_readable($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines !== false) {
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#')) {
                        continue;
                    }
                    if (!str_contains($line, '=')) {
                        continue;
                    }
                    [$key, $value] = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value, " \t\"'");
                    if ($key !== '') {
                        putenv("{$key}={$value}");
                        $_ENV[$key] = $value;
                    }
                }
            }
        } elseif (is_file($envFile)) {
            error_log('PhiloQuest: .env existe mas o PHP não consegue ler (permissões?). Caminho: ' . $envFile);
        } else {
            error_log('PhiloQuest: .env não encontrado em ' . $envFile);
        }

        self::$loaded = true;
    }

    public static function isProduction(): bool
    {
        return self::get('APP_ENV', 'local') === 'production';
    }

    public static function applyRuntimeSettings(): void
    {
        if (!self::isProduction()) {
            return;
        }

        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
        error_reporting(E_ALL);
    }

    /**
     * Valida variáveis obrigatórias quando APP_ENV=production.
     */
    public static function validateProduction(): void
    {
        if (!self::isProduction()) {
            return;
        }

        $password = self::envRaw('DB_PASSWORD');
        if ($password === null || $password === '') {
            error_log('PhiloQuest: DB_PASSWORD é obrigatório em produção.');
            self::abortConfig('Configuração do servidor incompleta.');
        }

        $user = strtolower(self::envRaw('DB_USER') ?? '');
        if ($user === 'root') {
            error_log('PhiloQuest: DB_USER não deve ser root em produção.');
            self::abortConfig('Configuração do servidor incompleta.');
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }
        return (string) $value;
    }

    private static function envRaw(string $key): ?string
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false) {
            return null;
        }

        return (string) $value;
    }

    private static function abortConfig(string $message): never
    {
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $message . PHP_EOL);
            exit(1);
        }

        http_response_code(500);
        exit($message);
    }
}
