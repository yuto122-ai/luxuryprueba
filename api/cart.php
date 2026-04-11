<?php
require_once '../php/config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true);
if ($input) $action = $input['action'] ?? $action;

$db        = getDB();
$sessionId = session_id();
$userId    = $_SESSION['user_id'] ?? null;

function normalizeCartImagePath(?string $raw): string {
    if (!$raw) {
        return '';
    }

    $path = trim($raw);
    $path = preg_replace('/[\x00-\x1F\x7F]/', '', $path);
    return substr($path, 0, 255);
}

function getCartItems($db, $userId, $sessionId) {
    if ($userId) {
        $stmt = $db->prepare("
            SELECT c.id, c.product_id, c.variant_id, c.quantity,
                                     p.name, p.material, p.price_individual, p.price_wholesale, p.min_wholesale_qty, p.sale_type,
                                         pv.size, pv.price AS variant_price, pv.price_individual AS variant_price_individual, pv.price_wholesale AS variant_price_wholesale,
                   c.image_path AS cart_image,
                   (SELECT image_path FROM product_images
                    WHERE product_id = p.id AND is_main = 1 LIMIT 1) as image
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_variants pv ON c.variant_id = pv.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $db->prepare("
            SELECT c.id, c.product_id, c.variant_id, c.quantity,
                                     p.name, p.material, p.price_individual, p.price_wholesale, p.min_wholesale_qty, p.sale_type,
                                         pv.size, pv.price AS variant_price, pv.price_individual AS variant_price_individual, pv.price_wholesale AS variant_price_wholesale,
                   c.image_path AS cart_image,
                   (SELECT image_path FROM product_images
                    WHERE product_id = p.id AND is_main = 1 LIMIT 1) as image
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_variants pv ON c.variant_id = pv.id
            WHERE c.session_id = ?
        ");
        $stmt->execute([$sessionId]);
    }

    $items = $stmt->fetchAll();

    foreach ($items as &$item) {
        $qty = (int)($item['quantity'] ?? 1);
        $minWholesaleQty = (int)($item['min_wholesale_qty'] ?? 1);
        $saleType = $item['sale_type'] ?? 'individual';
        $isWholesale = ($saleType === 'wholesale') || ($saleType === 'both' && $qty >= max(1, $minWholesaleQty));

        $variantIndividual = (float)($item['variant_price_individual'] ?? $item['variant_price'] ?? 0);
        $variantWholesale  = (float)($item['variant_price_wholesale'] ?? $item['variant_price'] ?? 0);
        $baseIndividual = (float)($item['price_individual'] ?? 0);
        $baseWholesale  = (float)($item['price_wholesale'] ?? 0);

        if ($isWholesale) {
            if ($variantWholesale > 0) {
                $item['price'] = $variantWholesale;
            } else {
                $item['price'] = $baseWholesale > 0 ? $baseWholesale : $baseIndividual;
            }
        } else {
            if ($variantIndividual > 0) {
                $item['price'] = $variantIndividual;
            } else {
                $item['price'] = $baseIndividual > 0 ? $baseIndividual : $baseWholesale;
            }
        }

        // BUG 4 FIX: material_label en español para mostrarse en el carrito
        $item['material_label'] = $item['material'] === 'cotton'
            ? 'Algodón'
            : ($item['material'] === 'polyester' ? 'Poliéster' : 'Mixto');

        // BUG 3 FIX: rutas relativas desde raíz del sitio (no '../')
        if (!empty($item['cart_image'])) {
            $item['image'] = $item['cart_image'];
        } else {
            $item['image'] = $item['image']
                ? 'uploads/products/' . $item['image']
                : 'assets/placeholder.jpg';
        }
    }

    return $items;
}

switch ($action) {

    case 'get':
        $items  = getCartItems($db, $userId, $sessionId);
        $total  = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
        $count  = array_sum(array_column($items, 'quantity'));
        echo json_encode(['success' => true, 'items' => $items, 'total' => $total, 'count' => $count]);
        break;

    case 'count':
        $items = getCartItems($db, $userId, $sessionId);
        $count = array_sum(array_column($items, 'quantity'));
        echo json_encode(['count' => $count]);
        break;

    case 'add':
        $productId = (int)($input['product_id'] ?? 0);
        $variantId = (isset($input['variant_id']) && $input['variant_id'] !== null && $input['variant_id'] !== '' && $input['variant_id'] !== 'null') ? (int)$input['variant_id'] : null;
        $qty       = max(1, (int)($input['quantity'] ?? 1));
        $imagePath = normalizeCartImagePath($input['image_path'] ?? null);
        $imagePath = $imagePath !== '' ? $imagePath : null;

        if (!$productId) {
            echo json_encode(['success' => false, 'message' => 'Producto inválido']);
            break;
        }

        // Verificar que el producto existe y está activo
        $prod = $db->prepare("SELECT id FROM products WHERE id = ? AND active = 1");
        $prod->execute([$productId]);
        if (!$prod->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
            break;
        }

        if ($userId) {
            $check = $db->prepare("SELECT id, quantity FROM cart
                WHERE user_id = ? AND product_id = ?
                AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))
                AND ((image_path IS NULL AND ? IS NULL) OR image_path = ?)");
            $check->execute([$userId, $productId, $variantId, $variantId, $imagePath, $imagePath]);
        } else {
            $check = $db->prepare("SELECT id, quantity FROM cart
                WHERE session_id = ? AND product_id = ?
                AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))
                AND ((image_path IS NULL AND ? IS NULL) OR image_path = ?)");
            $check->execute([$sessionId, $productId, $variantId, $variantId, $imagePath, $imagePath]);
        }
        $existing = $check->fetch();

        if ($existing) {
            $db->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?")
               ->execute([$qty, $existing['id']]);
        } else {
            if ($userId) {
                $db->prepare("INSERT INTO cart (user_id, product_id, variant_id, image_path, quantity) VALUES (?,?,?,?,?)")
                   ->execute([$userId, $productId, $variantId, $imagePath, $qty]);
            } else {
                $db->prepare("INSERT INTO cart (session_id, product_id, variant_id, image_path, quantity) VALUES (?,?,?,?,?)")
                   ->execute([$sessionId, $productId, $variantId, $imagePath, $qty]);
            }
        }

        $items = getCartItems($db, $userId, $sessionId);
        $count = array_sum(array_column($items, 'quantity'));
        echo json_encode(['success' => true, 'count' => $count, 'message' => 'Añadido al carrito']);
        break;

    case 'update':
        $itemId = (int)($input['item_id'] ?? 0);
        $qty    = max(0, (int)($input['quantity'] ?? 0));
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
        echo json_encode(['error' => 'Acción inválida']);
}