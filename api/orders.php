<?php
require_once '../php/config.php';
header('Content-Type: application/json');

function respondJson(array $payload): void {
    echo json_encode($payload);
    exit;
}

function sanitizeIdempotencyKey(?string $raw): string {
    if (!$raw) {
        return '';
    }
    $key = preg_replace('/[^a-zA-Z0-9_:\\-]/', '', trim($raw));
    return substr($key, 0, 120);
}

try {
    $input  = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        respondJson(['success' => false, 'message' => 'Datos inválidos']);
    }

    $action = $input['action'] ?? '';

    if ($action !== 'place') {
        respondJson(['success' => false, 'message' => 'Acción inválida']);
    }

    $headerKey = $_SERVER['HTTP_X_IDEMPOTENCY_KEY'] ?? null;
    $bodyKey = $input['idempotency_key'] ?? null;
    $idempotencyKey = sanitizeIdempotencyKey($headerKey ?: $bodyKey);

    if (!isset($_SESSION['checkout_idempotency_cache']) || !is_array($_SESSION['checkout_idempotency_cache'])) {
        $_SESSION['checkout_idempotency_cache'] = [];
    }

    $now = time();
    foreach ($_SESSION['checkout_idempotency_cache'] as $cachedKey => $entry) {
        if (!is_array($entry) || !isset($entry['ts']) || ($now - (int)$entry['ts']) > 600) {
            unset($_SESSION['checkout_idempotency_cache'][$cachedKey]);
        }
    }

    if ($idempotencyKey !== '' && isset($_SESSION['checkout_idempotency_cache'][$idempotencyKey]['response'])) {
        $cached = $_SESSION['checkout_idempotency_cache'][$idempotencyKey]['response'];
        if (is_array($cached)) {
            $cached['duplicate'] = true;
            respondJson($cached);
        }
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
                 pv.size, pv.price AS variant_price
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
                 pv.size, pv.price AS variant_price
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_variants pv ON c.variant_id = pv.id
            WHERE c.session_id = ?
        ");
        $stmt->execute([$sessionId]);
    }

    $items = $stmt->fetchAll();

    if (!$items) {
        respondJson(['success' => false, 'message' => 'Carrito vacío']);
    }

    // ── DATOS DE ENVÍO ───────────────────────────────────────────────────────
    $orderType = sanitize($input['order_type']       ?? 'individual');
    $name      = sanitize($input['shipping_name']    ?? '');
    $phone     = sanitize($input['shipping_phone']   ?? '');  // BUG 8 FIX: capturado
    $address   = sanitize($input['shipping_address'] ?? '');
    $notes     = sanitize($input['notes']            ?? '');

    // BUG 20 FIX: phone es opcional; solo name y address son obligatorios
    if (!$name || !$address) {
        respondJson(['success' => false, 'message' => 'Completa los datos de envío requeridos']);
    }

    $totalPieces = array_sum(array_map(static function ($item) {
        return (int)($item['quantity'] ?? 0);
    }, $items));

    if ($totalPieces < 12 && $orderType !== 'individual') {
        respondJson([
            'success' => false,
            'message' => 'Con menos de 12 piezas, el pedido debe ser Individual (Menudeo).',
            'forced_order_type' => 'individual',
        ]);
    }

    // ── CALCULAR TOTALES ─────────────────────────────────────────────────────
    // BUG 5 FIX: usa precio mayoreo cuando el tipo de orden es wholesale
    $subtotal = 0;
    foreach ($items as $item) {
        $wholesalePrice = (float)($item['price_wholesale'] ?? 0);
        $variantPrice = (float)($item['variant_price'] ?? 0);
        $colorExtra = (float)($item['color_extra'] ?? 0);
        if ($orderType === 'wholesale' && $wholesalePrice > 0) {
            $price = $wholesalePrice;
        } elseif ($variantPrice > 0) {
            $price = $variantPrice;
        } else {
            $price = (float)($item['price_individual'] ?? $item['price_wholesale'] ?? 0);
        }
        $price += $colorExtra;
        $subtotal += $price * $item['quantity'];
    }

    // BUG 7 FIX: calcular y guardar envío
    $shipping = $subtotal >= 1500 ? 0.00 : 150.00;
    $total    = $subtotal + $shipping;

    // Session-level fingerprint: blocks accidental double submits with different keys.
    $itemSignature = array_map(function ($item) {
        return implode(':', [
            (string)($item['product_id'] ?? ''),
            (string)($item['variant_id'] ?? ''),
            (string)($item['color_id'] ?? ''),
            (string)($item['color_extra'] ?? ''),
            (string)($item['quantity'] ?? ''),
        ]);
    }, $items);
    sort($itemSignature);

    $fingerprint = hash('sha256', json_encode([
        'user' => $userId ?: ('session:' . $sessionId),
        'type' => $orderType,
        'name' => $name,
        'phone' => $phone,
        'address' => $address,
        'notes' => $notes,
        'items' => $itemSignature,
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total' => $total,
    ]));

    $lastSuccess = $_SESSION['checkout_last_success'] ?? null;
    if (
        is_array($lastSuccess)
        && isset($lastSuccess['fingerprint'], $lastSuccess['ts'], $lastSuccess['response'])
        && $lastSuccess['fingerprint'] === $fingerprint
        && ($now - (int)$lastSuccess['ts']) <= 90
        && is_array($lastSuccess['response'])
    ) {
        $replayed = $lastSuccess['response'];
        $replayed['duplicate'] = true;
        if ($idempotencyKey !== '') {
            $_SESSION['checkout_idempotency_cache'][$idempotencyKey] = [
                'ts' => $now,
                'response' => $replayed,
            ];
        }
        respondJson($replayed);
    }

    // ── NÚMERO DE ORDEN ──────────────────────────────────────────────────────
    $orderNumber = generateOrderNumber();

    $productList = '';
    try {
        $db->beginTransaction();

        // ── INSERTAR ORDEN ───────────────────────────────────────────────────
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

        // ── INSERTAR ORDER_ITEMS ─────────────────────────────────────────────
        // BUG 6 FIX: se guardan los productos del pedido
        foreach ($items as $item) {
            $wholesalePrice = (float)($item['price_wholesale'] ?? 0);
            $variantPrice = (float)($item['variant_price'] ?? 0);
            $colorExtra = (float)($item['color_extra'] ?? 0);
            if ($orderType === 'wholesale' && $wholesalePrice > 0) {
                $price = $wholesalePrice;
            } elseif ($variantPrice > 0) {
                $price = $variantPrice;
            } else {
                $price = (float)($item['price_individual'] ?? $item['price_wholesale'] ?? 0);
            }
            $price += $colorExtra;
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

        // ── LIMPIAR CARRITO ──────────────────────────────────────────────────
        if ($userId) {
            $db->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$userId]);
        } else {
            $db->prepare("DELETE FROM cart WHERE session_id = ?")->execute([$sessionId]);
        }

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        respondJson([
            'success' => false,
            'message' => 'Error en el servidor',
            'error'   => $e->getMessage(),
        ]);
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

    $response = [
        'success'      => true,
        'order_number' => $orderNumber,
        'total'        => $total,
        'telegram'     => (bool)$telegramSent,
    ];

    if ($idempotencyKey !== '') {
        $_SESSION['checkout_idempotency_cache'][$idempotencyKey] = [
            'ts' => $now,
            'response' => $response,
        ];
    }

    $_SESSION['checkout_last_success'] = [
        'ts' => $now,
        'fingerprint' => $fingerprint,
        'response' => $response,
    ];

    respondJson($response);

} catch (Throwable $e) {
    respondJson([
        'success' => false,
        'message' => 'Error en el servidor',
        'error'   => $e->getMessage(),
    ]);
}
