<?php

function env_value(string $key, ?string $default = null): ?string {
    static $loaded = false;
    static $values = [];

    if (!$loaded) {
        $envPath = dirname(__DIR__) . '/.env';
        if (is_file($envPath) && is_readable($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                $values[$name] = trim($value, "\"'");
            }
        }
        $loaded = true;
    }

    $runtimeValue = getenv($key);
    if ($runtimeValue !== false) {
        return $runtimeValue;
    }

    return $values[$key] ?? $default;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host = env_value('DB_HOST', 'localhost');
    $port = env_value('DB_PORT', '3306');
    $name = env_value('DB_NAME', 'db-2026s1a9');
    $user = env_value('DB_USER', 'db-2026s1a9');
    $pass = env_value('DB_PASS', '');

    $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}
