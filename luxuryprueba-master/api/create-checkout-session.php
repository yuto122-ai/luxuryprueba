<?php
require_once __DIR__ . '/../php/config.php';

// Instalar Stripe con composer:
// composer require stripe/stripe-php
$autoloadCandidates = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
];
$autoloadPath = null;
foreach ($autoloadCandidates as $path) {
    if (file_exists($path)) {
        $autoloadPath = $path;
        break;
    }
}

if (!$autoloadPath) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Falta vendor/autoload.php. Ejecuta `composer install` o `composer require stripe/stripe-php` en la raíz del proyecto.',
    ]);
    exit;
}

require_once $autoloadPath;

// Usa la clave secreta de Stripe desde config.php, evita hardcodear aquí.
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

header('Content-Type: application/json; charset=utf-8');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $db = getDB();

    // Obtener datos del pedido
    $orderType = $input['order_type'] ?? 'individual';
    $shippingName = $input['shipping_name'] ?? 'Cliente';
    $userId = $_SESSION['user_id'] ?? null;
    $sessionId = session_id();

    // Obtener carrito con variant_id
    if ($userId) {
        $stmt = $db->prepare("
            SELECT c.id, c.product_id, c.variant_id, c.quantity, 
                   p.name, p.price_individual, p.price_wholesale,
                   pv.price AS variant_price
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_variants pv ON c.variant_id = pv.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $db->prepare("
            SELECT c.id, c.product_id, c.variant_id, c.quantity, 
                   p.name, p.price_individual, p.price_wholesale,
                   pv.price AS variant_price
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_variants pv ON c.variant_id = pv.id
            WHERE c.session_id = ?
        ");
        $stmt->execute([$sessionId]);
    }
    $items = $stmt->fetchAll();

    if (!$items || count($items) === 0) {
        throw new Exception("Carrito vacío");
    }

    // GENERAR ORDER_NUMBER ÚNICO
    $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(bin2hex(random_bytes(4)), true), -8));

    // CREAR ORDEN EN BD PRIMERO
    $orderStmt = $db->prepare("
        INSERT INTO orders (order_number, user_id, order_type, status, created_at)
        VALUES (?, ?, ?, 'pending', NOW())
    ");
    $orderStmt->execute([$orderNumber, $userId, $orderType]);
    $orderId = $db->lastInsertId();

    // Calcular precios y crear line_items
    $line_items = [];
    $total = 0;

    foreach ($items as $item) {
        // Resolver precio: variant > wholesale/individual > fallback
        if (!empty($item['variant_price'])) {
            $price = floatval($item['variant_price']);
        } elseif ($orderType === 'wholesale' && !empty($item['price_wholesale'])) {
            $price = floatval($item['price_wholesale']);
        } else {
            $price = floatval($item['price_individual']);
        }

        $lineTotal = $price * intval($item['quantity']);
        $total += $lineTotal;

        $line_items[] = [
            'price_data' => [
                'currency' => 'mxn',
                'product_data' => [
                    'name' => $item['name'],
                ],
                'unit_amount' => intval($price * 100),
            ],
            'quantity' => intval($item['quantity']),
        ];
    }

    // Envío fijo
    $shippingCost = 150; // $150 MXN
    $total += $shippingCost;
    $line_items[] = [
        'price_data' => [
            'currency' => 'mxn',
            'product_data' => [
                'name' => 'Envío',
            ],
            'unit_amount' => intval($shippingCost * 100),
        ],
        'quantity' => 1,
    ];

    // Crear sesión Stripe CON metadata del order_id
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => 'payment',
        'metadata' => ['order_id' => (string)$orderId],
        'success_url' => BASE_URL . '/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => BASE_URL . '/cancel.php?order_id=' . urlencode((string)$orderId),
    ]);

    echo json_encode(['success' => true, 'id' => $session->id, 'order_id' => $orderId]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}