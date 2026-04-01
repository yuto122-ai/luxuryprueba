<?php
require_once '../php/config.php';
header('Content-Type: application/json; charset=utf-8');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo json_encode(['error' => 'ID de producto requerido']);
    exit;
}

$db = getDB();

$stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND active = 1");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['error' => 'Producto no encontrado']);
    exit;
}

// Imágenes para vista 360 (ordenadas por sort_order)
$stmt = $db->prepare("
    SELECT image_path
    FROM product_images
    WHERE product_id = ?
    ORDER BY sort_order ASC, id ASC
");
$stmt->execute([$id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

$imagePaths = [];
foreach ($images as $img) {
    $imagePaths[] = 'uploads/products/' . $img['image_path'];
}
if (empty($imagePaths)) {
    $imagePaths[] = 'assets/placeholder.jpg';
}

// Variantes / tallas — sin filtrar por stock (se muestran todas, las sin stock aparecen deshabilitadas)
$stmt = $db->prepare("
    SELECT *
    FROM product_variants
    WHERE product_id = ?
    ORDER BY FIELD(size,'XS','S','M','L','XL','XXL','XXXL')
");
$stmt->execute([$id]);
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'product'  => $product,
    'images'   => $imagePaths,
    'variants' => $variants,
]);
