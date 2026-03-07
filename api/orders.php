<?php
require_once '../php/config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';
$db = getDB();

switch ($action) {
    case 'place':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Inicia sesión para continuar']);
            break;
        }

        $userId = $_SESSION['user_id'];
        $sessionId = session_id();

        // Get cart items
        $stmt = $db->prepare("
            SELECT c.*, p.name as product_name, p.material, p.price_individual, p.price_wholesale, p.sale_type,
                   pv.size
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_variants pv ON c.variant_id = pv.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
        $cartItems = $stmt->fetchAll();

        if (empty($cartItems)) {
            echo json_encode(['success' => false, 'message' => 'Carrito vacío']);
            break;
        }

        $shippingName = sanitize($input['shipping_name'] ?? '');
        $shippingAddress = sanitize($input['shipping_address'] ?? '');
        $shippingPhone = sanitize($input['shipping_phone'] ?? '');
        $notes = sanitize($input['notes'] ?? '');
        $orderType = $input['order_type'] ?? 'individual';

        if (!$shippingName || !$shippingAddress) {
            echo json_encode(['success' => false, 'message' => 'Completa los datos de envío']);
            break;
        }

        // Calculate totals
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $price = $orderType === 'wholesale' ? ($item['price_wholesale'] ?? $item['price_individual']) : $item['price_individual'];
            $subtotal += $price * $item['quantity'];
        }
        $shipping = $subtotal > 1500 ? 0 : 150;
        $total = $subtotal + $shipping;

        $orderNumber = generateOrderNumber();

        // Create order
        $stmt = $db->prepare("
            INSERT INTO orders (user_id, order_number, order_type, subtotal, shipping, total, 
                shipping_name, shipping_address, shipping_phone, notes)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([$userId, $orderNumber, $orderType, $subtotal, $shipping, $total,
            $shippingName, $shippingAddress, $shippingPhone, $notes]);
        $orderId = $db->lastInsertId();

        // Insert order items
        foreach ($cartItems as $item) {
            $price = $orderType === 'wholesale' ? ($item['price_wholesale'] ?? $item['price_individual']) : $item['price_individual'];
            $db->prepare("
                INSERT INTO order_items (order_id, product_id, variant_id, product_name, size, quantity, unit_price, subtotal)
                VALUES (?,?,?,?,?,?,?,?)
            ")->execute([$orderId, $item['product_id'], $item['variant_id'], $item['product_name'],
                $item['size'], $item['quantity'], $price, $price * $item['quantity']]);
        }

        // Clear cart
        $db->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$userId]);

        // Build Telegram message
        $itemsList = '';
        foreach ($cartItems as $item) {
            $price = $orderType === 'wholesale' ? ($item['price_wholesale'] ?? $item['price_individual']) : $item['price_individual'];
            $materialLabel = $item['material'] === 'cotton' ? 'Algodón' : ($item['material'] === 'polyester' ? 'Poliéster' : 'Mixto');
            $itemsList .= "• {$item['product_name']} ({$materialLabel})" .
                ($item['size'] ? " · Talla {$item['size']}" : '') .
                " x{$item['quantity']} — $" . number_format($price * $item['quantity'], 2) . "\n";
        }

        $typeLabel = $orderType === 'wholesale' ? '🏭 MAYOREO' : '🛍 INDIVIDUAL';
        $telegramMsg = "🖤 <b>NUEVO PEDIDO — BLACK CLOTHES</b>\n\n" .
            "📋 <b>#{$orderNumber}</b> · {$typeLabel}\n\n" .
            "👤 <b>Cliente:</b> {$shippingName}\n" .
            "📱 <b>Teléfono:</b> {$shippingPhone}\n" .
            "📍 <b>Envío:</b> {$shippingAddress}\n\n" .
            "🧾 <b>Productos:</b>\n{$itemsList}\n" .
            "💰 Subtotal: $" . number_format($subtotal, 2) . "\n" .
            "🚚 Envío: " . ($shipping > 0 ? "$" . number_format($shipping, 2) : "GRATIS") . "\n" .
            "💵 <b>TOTAL: $" . number_format($total, 2) . "</b>\n\n" .
            ($notes ? "📝 Notas: {$notes}\n\n" : '') .
            "⏰ " . date('d/m/Y H:i') . " hrs";

        $result = sendTelegramMessage($telegramMsg);
        $db->prepare("UPDATE orders SET telegram_sent = ? WHERE id = ?")->execute([$result ? 1 : 0, $orderId]);

        echo json_encode([
            'success' => true,
            'order_number' => $orderNumber,
            'total' => $total,
            'message' => '¡Pedido realizado exitosamente!'
        ]);
        break;

    case 'my_orders':
        if (!isLoggedIn()) { echo json_encode(['success' => false, 'message' => 'No autenticado']); break; }
        $stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$_SESSION['user_id']]);
        echo json_encode(['success' => true, 'orders' => $stmt->fetchAll()]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
