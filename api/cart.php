<?php
require_once '../php/config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
if ($input) $action = $input['action'] ?? $action;

$db = getDB();
$sessionId = session_id();
$userId = $_SESSION['user_id'] ?? null;

function getCartItems($db, $userId, $sessionId) {
    if ($userId) {
        $stmt = $db->prepare("
            SELECT c.id, c.product_id, c.variant_id, c.quantity,
                   p.name, p.material, p.price_individual, p.price_wholesale, p.sale_type,
                   pv.size,
                   (SELECT image_path FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as image
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_variants pv ON c.variant_id = pv.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $db->prepare("
            SELECT c.id, c.product_id, c.variant_id, c.quantity,
                   p.name, p.material, p.price_individual, p.price_wholesale, p.sale_type,
                   pv.size,
                   (SELECT image_path FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as image
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_variants pv ON c.variant_id = pv.id
            WHERE c.session_id = ?
        ");
        $stmt->execute([$sessionId]);
    }
    $items = $stmt->fetchAll();
    
    foreach ($items as &$item) {
        $item['price'] = $item['price_individual'] ?? $item['price_wholesale'];
        $item['material_label'] = $item['material'] === 'cotton' ? 'Algodón' : ($item['material'] === 'polyester' ? 'Poliéster' : 'Mixto');
        $item['image'] = $item['image'] ? '../uploads/products/' . $item['image'] : '../assets/placeholder.jpg';
    }
    return $items;
}

switch ($action) {
    case 'get':
        $items = getCartItems($db, $userId, $sessionId);
        $total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
        $count = array_sum(array_column($items, 'quantity'));
        echo json_encode(['success' => true, 'items' => $items, 'total' => $total, 'count' => $count]);
        break;

    case 'count':
        $items = getCartItems($db, $userId, $sessionId);
        $count = array_sum(array_column($items, 'quantity'));
        echo json_encode(['count' => $count]);
        break;

    case 'add':
        $productId = (int)($input['product_id'] ?? 0);
        $variantId = !empty($input['variant_id']) ? (int)$input['variant_id'] : null;
        $qty = max(1, (int)($input['quantity'] ?? 1));

        if (!$productId) { echo json_encode(['success' => false, 'message' => 'Producto inválido']); break; }

        // Check if already in cart
        if ($userId) {
            $check = $db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))");
            $check->execute([$userId, $productId, $variantId, $variantId]);
        } else {
            $check = $db->prepare("SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))");
            $check->execute([$sessionId, $productId, $variantId, $variantId]);
        }
        $existing = $check->fetch();

        if ($existing) {
            $db->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?")->execute([$qty, $existing['id']]);
        } else {
            if ($userId) {
                $db->prepare("INSERT INTO cart (user_id, product_id, variant_id, quantity) VALUES (?,?,?,?)")->execute([$userId, $productId, $variantId, $qty]);
            } else {
                $db->prepare("INSERT INTO cart (session_id, product_id, variant_id, quantity) VALUES (?,?,?,?)")->execute([$sessionId, $productId, $variantId, $qty]);
            }
        }

        $items = getCartItems($db, $userId, $sessionId);
        $count = array_sum(array_column($items, 'quantity'));
        echo json_encode(['success' => true, 'count' => $count, 'message' => 'Añadido al carrito']);
        break;

    case 'update':
        $itemId = (int)($input['item_id'] ?? 0);
        $qty = max(0, (int)($input['quantity'] ?? 0));
        if ($qty === 0) {
            $db->prepare("DELETE FROM cart WHERE id = ?")->execute([$itemId]);
        } else {
            $db->prepare("UPDATE cart SET quantity = ? WHERE id = ?")->execute([$qty, $itemId]);
        }
        $items = getCartItems($db, $userId, $sessionId);
        $count = array_sum(array_column($items, 'quantity'));
        echo json_encode(['success' => true, 'count' => $count]);
        break;

    case 'remove':
        $itemId = (int)($input['item_id'] ?? 0);
        $db->prepare("DELETE FROM cart WHERE id = ?")->execute([$itemId]);
        $items = getCartItems($db, $userId, $sessionId);
        $count = array_sum(array_column($items, 'quantity'));
        echo json_encode(['success' => true, 'count' => $count]);
        break;

    case 'clear':
        if ($userId) {
            $db->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$userId]);
        } else {
            $db->prepare("DELETE FROM cart WHERE session_id = ?")->execute([$sessionId]);
        }
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}

