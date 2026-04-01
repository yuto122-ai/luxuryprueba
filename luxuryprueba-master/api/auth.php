<?php
require_once '../php/config.php';
header('Content-Type: application/json');

$rawBody = file_get_contents('php://input');
$input   = json_decode($rawBody, true);

if (!is_array($input)) {
    $input = [];
}

// Fallback: acepta JSON y tambien form-data/x-www-form-urlencoded
if (empty($input) && !empty($_POST)) {
    $input = $_POST;
} elseif (empty($input) && is_string($rawBody) && $rawBody !== '') {
    parse_str($rawBody, $parsed);
    if (is_array($parsed) && !empty($parsed)) {
        $input = $parsed;
    }
}

$action = strtolower(trim((string)($input['action'] ?? '')));
$db     = getDB();

switch ($action) {

    case 'login':
        $email    = sanitize($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (!$email || !$password) {
            echo json_encode(['success' => false, 'message' => 'Completa todos los campos']);
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            exit;
        }

        if (!password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Contrasena incorrecta']);
            exit;
        }

        // Migrar carrito de sesion anonima al usuario
        $sessionId = session_id();
        $db->prepare("UPDATE cart SET user_id = ?, session_id = NULL
                      WHERE session_id = ? AND user_id IS NULL")
           ->execute([$user['id'], $sessionId]);

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];

        // Admin va al panel, cliente a la tienda
        $redirect = $user['role'] === 'admin' ? 'admin' : 'index';

        echo json_encode(['success' => true, 'user' => $user['name'], 'redirect' => $redirect]);
        break;

    case 'register':
        $name     = sanitize($input['name'] ?? '');
        $email    = sanitize($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (!$name || !$email || !$password) {
            echo json_encode(['success' => false, 'message' => 'Completa todos los campos']);
            exit;
        }

        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'La contrasena debe tener al menos 6 caracteres']);
            exit;
        }

        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ese correo ya esta registrado']);
            exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?,?,?,'customer')");
        $stmt->execute([$name, $email, $hash]);
        $newId = $db->lastInsertId();

        // Migrar carrito de sesion al nuevo usuario
        $sessionId = session_id();
        $db->prepare("UPDATE cart SET user_id = ?, session_id = NULL
                      WHERE session_id = ? AND user_id IS NULL")
           ->execute([$newId, $sessionId]);

        $_SESSION['user_id']    = $newId;
        $_SESSION['user_name']  = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role']  = 'customer';

        echo json_encode(['success' => true, 'user' => $name, 'redirect' => 'index']);
        break;

    case 'logout':
        session_destroy();
        echo json_encode(['success' => true, 'redirect' => 'login']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Accion invalida']);
}
