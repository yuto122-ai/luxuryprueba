<?php
require_once '../php/config.php';
header('Content-Type: application/json');

try {
    $input  = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    $action = $input['action'] ?? '';

    if ($action !== 'place') {
        echo json_encode(['success' => false, 'message' => 'Acción inválida']);
        exit;
    }

    // BUG 9 FIX: permitir guest checkout (ya no se exige login)
    $db        = getDB();
    $userId    = $_SESSION['user_id'] ?? null;
    $sessionId = session_id();

    // ── OBTENER CARRITO ──────────────────────────────────────────────────────
    if ($userId) {
        $stmt = $db->prepare("
            SELECT c.*, p.name, p.material,
                   p.price_individual, p.price_wholesale, p.sale_type,
                   pv.size
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_variants pv ON c.variant_id = pv.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $db->prepare("
            SELECT c.*, p.name, p.material,
                   p.price_individual, p.price_wholesale, p.sale_type,
                   pv.size
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_variants pv ON c.variant_id = pv.id
            WHERE c.session_id = ?
        ");
        $stmt->execute([$sessionId]);
    }

    $items = $stmt->fetchAll();

    if (!$items) {
        echo json_encode(['success' => false, 'message' => 'Carrito vacío']);
        exit;
    }

    // ── DATOS DE ENVÍO ───────────────────────────────────────────────────────
    $orderType = sanitize($input['order_type']       ?? 'individual');
    $name      = sanitize($input['shipping_name']    ?? '');
    $phone     = sanitize($input['shipping_phone']   ?? '');  // BUG 8 FIX: capturado
    $address   = sanitize($input['shipping_address'] ?? '');
    $notes     = sanitize($input['notes']            ?? '');

    // BUG 20 FIX: phone es opcional; solo name y address son obligatorios
    if (!$name || !$address) {
        echo json_encode(['success' => false, 'message' => 'Completa los datos de envío requeridos']);
        exit;
    }

    // ── CALCULAR TOTALES ─────────────────────────────────────────────────────
    // BUG 5 FIX: usa precio mayoreo cuando el tipo de orden es wholesale
    $subtotal = 0;
    foreach ($items as $item) {
        $price = ($orderType === 'wholesale' && !empty($item['price_wholesale']))
            ? (float)$item['price_wholesale']
            : (float)($item['price_individual'] ?? $item['price_wholesale'] ?? 0);
        $subtotal += $price * $item['quantity'];
    }

    // BUG 7 FIX: calcular y guardar envío
    $shipping = $subtotal >= 1500 ? 0.00 : 150.00;
    $total    = $subtotal + $shipping;

    // ── NÚMERO DE ORDEN ──────────────────────────────────────────────────────
    $orderNumber = generateOrderNumber();

    // ── INSERTAR ORDEN ───────────────────────────────────────────────────────
    // BUG 7+8 FIX: guarda subtotal, shipping, total, shipping_phone, order_type
    $stmt = $db->prepare("
        INSERT INTO orders
            (user_id, order_number, order_type, status,
             subtotal, shipping, total,
             shipping_name, shipping_phone, shipping_address, notes,
             telegram_sent)
        VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, 0)
    ");
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
    $orderId = $db->lastInsertId();

    // ── INSERTAR ORDER_ITEMS ─────────────────────────────────────────────────
    // BUG 6 FIX: se guardan los productos del pedido
    $productList = '';
    foreach ($items as $item) {
        $price = ($orderType === 'wholesale' && !empty($item['price_wholesale']))
            ? (float)$item['price_wholesale']
            : (float)($item['price_individual'] ?? $item['price_wholesale'] ?? 0);
        $lineTotal = $price * $item['quantity'];

        $db->prepare("
            INSERT INTO order_items
                (order_id, product_id, variant_id, product_name, size, quantity, unit_price, subtotal)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $orderId,
            $item['product_id'],
            $item['variant_id'] ?? null,
            $item['name'],
            $item['size'] ?? null,
            $item['quantity'],
            $price,
            $lineTotal,
        ]);

        $productList .= "• {$item['name']}"
            . ($item['size'] ? " (Talla {$item['size']})" : '')
            . " x{$item['quantity']} — $" . number_format($price, 2) . "\n";
    }

    // ── MENSAJE TELEGRAM ─────────────────────────────────────────────────────
    // BUG 10 FIX: mensaje con parse_mode HTML (config ya lo envía), incluye envío y tipo
    $typeLabel = $orderType === 'wholesale' ? '🏭 MAYOREO' : '🛍 INDIVIDUAL';
    $msg  = "🖤 <b>NUEVO PEDIDO — BLACK CLOTHES</b>\n\n";
    $msg .= "📦 Pedido: <b>#{$orderNumber}</b>\n";
    $msg .= "🏷 Tipo: {$typeLabel}\n\n";
    $msg .= "👤 Cliente: {$name}\n";
    if ($phone) $msg .= "📞 Teléfono: {$phone}\n";
    $msg .= "📍 Dirección: {$address}\n\n";
    $msg .= "🛒 Productos:\n{$productList}\n";
    $msg .= "💵 Subtotal: $" . number_format($subtotal, 2) . "\n";
    $msg .= "🚚 Envío: " . ($shipping > 0 ? "$" . number_format($shipping, 2) : "GRATIS") . "\n";
    $msg .= "💰 <b>Total: $" . number_format($total, 2) . "</b>";
    if ($notes) $msg .= "\n\n📝 Notas: {$notes}";

    $telegramSent = 0;
    try {
        if (sendTelegramMessage($msg)) {
            $telegramSent = 1;
            $db->prepare("UPDATE orders SET telegram_sent = 1 WHERE id = ?")->execute([$orderId]);
        }
    } catch (Exception $e) {}

    // ── LIMPIAR CARRITO ──────────────────────────────────────────────────────
    if ($userId) {
        $db->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$userId]);
    } else {
        $db->prepare("DELETE FROM cart WHERE session_id = ?")->execute([$sessionId]);
    }

    echo json_encode([
        'success'      => true,
        'order_number' => $orderNumber,
        'total'        => $total,
        'telegram'     => (bool)$telegramSent,
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor',
        'error'   => $e->getMessage(),
    ]);
}
