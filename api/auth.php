<?php
require_once '../php/config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_POST['action'] ?? '';
$db = getDB();

switch ($action) {
    case 'login':
        $email = sanitize($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (!$email || !$password) {
            echo json_encode(['success' => false, 'message' => 'Completa todos los campos']);
            break;
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
            break;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];

        // Merge guest cart into user cart
        $sessionId = session_id();
        $db->prepare("UPDATE cart SET user_id = ?, session_id = NULL WHERE session_id = ?")->execute([$user['id'], $sessionId]);

        echo json_encode([
            'success' => true,
            'redirect' => $user['role'] === 'admin' ? SITE_URL . '/admin/' : SITE_URL . '/index.php',
            'message' => '¡Bienvenido, ' . $user['name'] . '!'
        ]);
        break;

    case 'register':
        $name = sanitize($input['name'] ?? '');
        $email = sanitize($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $phone = sanitize($input['phone'] ?? '');

        if (!$name || !$email || !$password) {
            echo json_encode(['success' => false, 'message' => 'Completa todos los campos requeridos']);
            break;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Email inválido']);
            break;
        }

        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
            break;
        }

        // Check existing email
        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'El email ya está registrado']);
            break;
        }

        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (name, email, password, phone) VALUES (?,?,?,?)");
        $stmt->execute([$name, $email, $hashed, $phone]);
        $userId = $db->lastInsertId();

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = 'customer';
        $_SESSION['user_email'] = $email;

        // Merge guest cart
        $sessionId = session_id();
        $db->prepare("UPDATE cart SET user_id = ?, session_id = NULL WHERE session_id = ?")->execute([$userId, $sessionId]);

        echo json_encode(['success' => true, 'redirect' => SITE_URL . '/index.php', 'message' => '¡Cuenta creada exitosamente!']);
        break;

    case 'logout':
        session_destroy();
        echo json_encode(['success' => true, 'redirect' => SITE_URL . '/index.php']);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
