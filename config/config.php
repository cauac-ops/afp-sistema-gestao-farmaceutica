<?php
$env = [];
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $val) = explode('=', $line, 2);
        $env[trim($key)] = trim($val);
    }
}

define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
define('DB_NAME', $env['DB_NAME'] ?? '');
define('DB_USER', $env['DB_USER'] ?? '');
define('DB_PASS', $env['DB_PASS'] ?? '');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(0, '/', '', true, true);
    session_start();
}

if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = rtrim(str_replace(basename($script), '', $script), '/');
    $dir = preg_replace('#/(pages|public|ajax)$#', '', $dir);
    define('BASE_URL', $protocol . '://' . $host . $dir);
}

class Database {
    private $conn = null;
    public function connect() {
        if ($this->conn !== null) return $this->conn;
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $this->conn = new PDO($dsn, DB_USER, DB_PASS);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $this->conn;
        } catch (PDOException $e) {
            die('Erro de conexao: ' . $e->getMessage());
        }
    }
}

function sanitize($value) {
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}
function formatMoeda($valor) {
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}
function formatData($data, $formato = 'd/m/Y H:i') {
    if (empty($data) || $data === '0000-00-00' || $data === '0000-00-00 00:00:00') return '-';
    try { return (new DateTime($data))->format($formato); } catch (Exception $e) { return '-'; }
}
function formatDataSimples($data) {
    return formatData($data, 'd/m/Y');
}
