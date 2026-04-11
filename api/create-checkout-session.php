<?php
require_once __DIR__ . '/../php/config.php';

$autoloadCandidates = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
];

$autoloadPath = null;
foreach ($autoloadCandidates as $candidate) {
    if (file_exists($candidate)) {
        $autoloadPath = $candidate;
        break;
    }
}

header('Content-Type: application/json; charset=utf-8');

if (!$autoloadPath) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Falta vendor/autoload.php en el servidor.']);
    exit;
}

if (!defined('STRIPE_SECRET_KEY') || STRIPE_SECRET_KEY === '') {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Falta configurar STRIPE_SECRET_KEY.']);
    exit;
}

require_once $autoloadPath;

if (!class_exists('Stripe\\Stripe') || !class_exists('Stripe\\Checkout\\Session')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Stripe no está disponible en el servidor.']);
    exit;
}

call_user_func(['Stripe\\Stripe', 'setApiKey'], STRIPE_SECRET_KEY);

function normalizeStripeText(?string $value): string {
    $text = trim((string)$value);
    $text = preg_replace('/[\x00-\x1F\x7F]/', '', $text);
    return substr($text, 0, 255);
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    if (($input['action'] ?? '') !== 'create_checkout_session') {
        echo json_encode(['success' => false, 'error' => 'Acción inválida']);
        exit;
    }

    $db = getDB();
    $userId = $_SESSION['user_id'] ?? null;
    $sessionId = session_id();

    $orderType = sanitize($input['order_type'] ?? 'individual');
    $name = sanitize($input['shipping_name'] ?? '');
    $phone = sanitize($input['shipping_phone'] ?? '');
    $address = sanitize($input['shipping_address'] ?? '');
    $notes = sanitize($input['notes'] ?? '');

    if (!$name || !$address) {
        echo json_encode(['success' => false, 'error' => 'Completa los datos de envío requeridos']);
        exit;
    }

    if ($userId) {
        $stmt = $db->prepare("SELECT c.*, p.name, p.price_individual, p.price_wholesale, pv.price AS variant_price, pv.size
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_variants pv ON c.variant_id = pv.id
            WHERE c.user_id = ?");
        $stmt->execute([$userId]);
    } else {
        $stmt = $db->prepare("SELECT c.*, p.name, p.price_individual, p.price_wholesale, pv.price AS variant_price, pv.size
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_variants pv ON c.variant_id = pv.id
            WHERE c.session_id = ?");
        $stmt->execute([$sessionId]);
    }

    $items = $stmt->fetchAll();
    if (!$items) {
        echo json_encode(['success' => false, 'error' => 'Carrito vacío']);
        exit;
    }

    $subtotal = 0.0;
    $lineItems = [];
    foreach ($items as $item) {
        $wholesalePrice = (float)($item['price_wholesale'] ?? 0);
        $variantPrice = (float)($item['variant_price'] ?? 0);
        if ($orderType === 'wholesale' && $wholesalePrice > 0) {
            $price = $wholesalePrice;
        } elseif ($variantPrice > 0) {
            $price = $variantPrice;
        } else {
            $price = (float)($item['price_individual'] ?? 0);
        }

        $subtotal += $price * (int)$item['quantity'];
        $lineItems[] = [
            'price_data' => [
                'currency' => 'mxn',
                'product_data' => ['name' => normalizeStripeText($item['name'])],
                'unit_amount' => (int)round($price * 100),
            ],
            'quantity' => (int)$item['quantity'],
        ];
    }

    $shipping = $subtotal >= 1500 ? 0.0 : 150.0;
    if ($shipping > 0) {
        $lineItems[] = [
            'price_data' => [
                'currency' => 'mxn',
                'product_data' => ['name' => 'Envío'],
                'unit_amount' => (int)round($shipping * 100),
            ],
            'quantity' => 1,
        ];
    }

    $total = $subtotal + $shipping;

    $db->beginTransaction();

    $orderNumber = generateOrderNumber();
    $stmt = $db->prepare("INSERT INTO orders
        (user_id, order_number, order_type, status, subtotal, shipping, total, shipping_name, shipping_phone, shipping_address, notes, telegram_sent)
        VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, 0)");
    $stmt->execute([
        $userId,
        $orderNumber,
        $orderType,
        $subtotal,
        $shipping,
        $total,
        $name,
        $phone,
        $address,
        $notes,
    ]);
    $orderId = (int)$db->lastInsertId();

    foreach ($items as $item) {
        $wholesalePrice = (float)($item['price_wholesale'] ?? 0);
        $variantPrice = (float)($item['variant_price'] ?? 0);
        if ($orderType === 'wholesale' && $wholesalePrice > 0) {
            $price = $wholesalePrice;
        } elseif ($variantPrice > 0) {
            $price = $variantPrice;
        } else {
            $price = (float)($item['price_individual'] ?? 0);
        }
        $lineTotal = $price * (int)$item['quantity'];

        $db->prepare("INSERT INTO order_items (order_id, product_id, variant_id, product_name, size, quantity, unit_price, subtotal)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)")->execute([
            $orderId,
            $item['product_id'],
            $item['variant_id'] ?? null,
            $item['name'],
            $item['size'] ?? null,
            $item['quantity'],
            $price,
            $lineTotal,
        ]);
    }

    $session = call_user_func(['Stripe\\Checkout\\Session', 'create'], [
        'payment_method_types' => ['card'],
        'line_items' => $lineItems,
        'mode' => 'payment',
        'metadata' => [
            'order_id' => (string)$orderId,
            'order_number' => $orderNumber,
        ],
        'success_url' => BASE_URL . '/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => BASE_URL . '/cancel.php?order_id=' . urlencode((string)$orderId),
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'id' => $session->id,
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'total' => $total,
    ]);
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}