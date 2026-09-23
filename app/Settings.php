<?php

declare(strict_types=1);

define('APP_ROOT',    __DIR__);
define('APP_NAME',    'GERROTINAS');
define('APP_SESSION_KEY', 'Zo#2@oK6');
define('APP_VERSION', '6.0');

$env = loadEnv(APP_ROOT . '/.env');

date_default_timezone_set($env['APP_TIMEZONE'] ?? 'America/Sao_Paulo');
define('APP_ENVIRONMENT', $env['APP_ENVIRONMENT'] ?? 'development');
define('APP_BASE_URL',    $env['APP_BASE_URL']    ?? 'http://localhost/gerrotinas');

if (APP_ENVIRONMENT === 'production') requireEnv($env, ['DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_DATABASE', 'APP_BASE_URL']);
define('DB', [
    'HOST' => $env['DB_HOST'] ?? 'localhost',
    'USER' => $env['DB_USER'] ?? 'root',
    'PASSWORD' => $env['DB_PASSWORD'] ?? '',
    'DATABASE' => $env['DB_DATABASE'] ?? 'gerrotinas',
    'PORT' => (int) ($env['DB_PORT'] ?? 3306),
]);

/**
 * Loads and parses a .env file into an associative array.
 *
 * @param  string $path  Absolute path to the .env file.
 * @return array<string, string>  Parsed key-value pairs.
 *
 * @throws RuntimeException  If the file exists but cannot be read.
 */
function loadEnv(string $path): array
{
    if (!file_exists($path)) return [];

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) throw new RuntimeException("Unable to read environment file: {$path}");

    $env = [];
    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;

        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
    }

    return $env;
}

/**
 * Ensures that all required environment keys are present and non-empty.
 *
 * @param  array<string, string> $env   The loaded environment variables.
 * @param  string[]              $keys  List of required variable names.
 * @return void
 *
 * @throws RuntimeException  If one or more required variables are missing.
 */
function requireEnv(array $env, array $keys): void
{
    $missing = array_filter($keys, fn($key) => empty($env[$key]));

    if (!empty($missing)) throw new RuntimeException('Missing required environment variables: ' . implode(', ', $missing));
}
