<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===========================
// BASE DE DATOS
// ===========================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'black_clothes');

function getDB() {
    static $pdo;
    if (!$pdo) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            die("Error conexión DB: " . $e->getMessage());
        }
    }
    return $pdo;
}

// ===========================
// FUNCIONES GENERALES
// ===========================

function sanitize($text) {
    return htmlspecialchars(strip_tags(trim($text)));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireAdmin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit;
    }
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        echo "Acceso solo para administradores";
        exit;
    }
}

function generateOrderNumber() {
    return "BC" . date("Ymd") . rand(1000, 9999);
}

// ===========================
// TELEGRAM BOT CONFIG
// BUG 22 FIX: guarda el token en variables de entorno o en un archivo fuera del webroot.
// Por ahora se deja aquí para compatibilidad — cámbialo antes de subir a producción.
// ===========================

define('TELEGRAM_BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');
define('TELEGRAM_CHAT_ID',   'YOUR_CHAT_ID_HERE');

/**
 * BUG 23 FIX: agregado parse_mode HTML para que los mensajes con <b> funcionen.
 */
function sendTelegramMessage($message) {
    $token   = TELEGRAM_BOT_TOKEN;
    $chat_id = TELEGRAM_CHAT_ID;

    if ($token === 'YOUR_BOT_TOKEN_HERE' || $chat_id === 'YOUR_CHAT_ID_HERE') {
        return false;
    }

    $url  = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = [
        'chat_id'    => $chat_id,
        'text'       => $message,
        'parse_mode' => 'HTML',   // ← BUG 23 FIX
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10,
        ]
    ];

    $context = stream_context_create($options);
    $result  = @file_get_contents($url, false, $context);
    return $result !== false;
}
