<?php


ini_set('display_errors', 0);
error_reporting(E_ALL);


define('DB_HOST', 'localhost');
define('DB_NAME', 'seu_banco');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');

define('APP_NAME', 'AFP - Agenda Farmacêutica de Planejamento');


class Database {
    private ?PDO $conn = null;

    public function connect(): PDO {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

            $this->conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;padding:20px;margin:20px;border-radius:8px;">
                <strong>Erro de conexão:</strong> Não foi possível conectar ao banco de dados.
            </div>');
        }

        return $this->conn;
    }
}


function formatMoeda($valor): string {
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}


function formatData($data): string {
    if (!$data || $data === '0000-00-00 00:00:00') return '-';
    return date('d/m/Y H:i', strtotime($data));
}


function formatDataSimples($data): string {
    if (!$data || $data === '0000-00-00') return '-';
    return date('d/m/Y', strtotime($data));
}


function sanitize($data): string {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}
