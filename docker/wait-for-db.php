<?php

declare(strict_types=1);

/**
 * Exit 0 when Postgres is reachable, 1 otherwise.
 */

$sslMode = getenv('DB_SSLMODE') ?: 'require';

try {
    $dsnUrl = getenv('DB_URL') ?: getenv('DATABASE_URL') ?: '';

    if ($dsnUrl !== '') {
        $parts = parse_url($dsnUrl);
        if ($parts === false) {
            throw new RuntimeException('Invalid DB_URL / DATABASE_URL');
        }

        $host = $parts['host'] ?? '127.0.0.1';
        $port = (string) ($parts['port'] ?? 5432);
        $db = ltrim($parts['path'] ?? '/postgres', '/');
        $user = $parts['user'] ?? 'postgres';
        $pass = $parts['pass'] ?? '';

        // query may already include sslmode
        $query = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        $ssl = $query['sslmode'] ?? $sslMode;

        $pdo = new PDO(
            sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=%s', $host, $port, $db, $ssl),
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
        );
        $pdo->query('select 1');
        exit(0);
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '5432';
    $db = getenv('DB_DATABASE') ?: 'postgres';
    $user = getenv('DB_USERNAME') ?: 'postgres';
    $pass = getenv('DB_PASSWORD') ?: '';

    $pdo = new PDO(
        sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=%s', $host, $port, $db, $sslMode),
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
    );
    $pdo->query('select 1');
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
