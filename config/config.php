<?php

declare(strict_types=1);

$envPath = __DIR__ . '/env.env';
$env = is_file($envPath) ? parse_ini_file($envPath, false, INI_SCANNER_TYPED) : [];

if (!is_array($env)) {
    $env = [];
}

if (!defined('DB_HOST')) define('DB_HOST', (string)($env['DB_HOST'] ?? 'localhost'));
if (!defined('DB_NAME')) define('DB_NAME', (string)($env['DB_NAME'] ?? 'afp_exemplo'));
if (!defined('DB_USER')) define('DB_USER', (string)($env['DB_USER'] ?? 'root'));
if (!defined('DB_PASS')) define('DB_PASS', (string)($env['DB_PASS'] ?? ''));

class Database
{
    private ?PDO $conn = null;

    public function connect(): PDO
    {
        if ($this->conn instanceof PDO) {
            return $this->conn;
        }

        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $this->conn = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $this->conn;
    }
}

function sanitize(?string $value): string
{
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

function formatMoeda($valor): string
{
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

function formatData(?string $data, string $formato = 'd/m/Y'): string
{
    if (empty($data) || $data === '0000-00-00' || $data === '0000-00-00 00:00:00') {
        return '-';
    }

    try {
        return (new DateTime($data))->format($formato);
    } catch (Throwable $e) {
        return '-';
    }
}
