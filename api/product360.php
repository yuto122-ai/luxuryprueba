<?php
require_once '../php/config.php';

header('Content-Type: application/json; charset=utf-8');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo json_encode(['error' => 'No product ID']);
    exit;
}

$db = getDB();

/* PRODUCTO */
$stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND active = 1");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['error' => 'Product not found']);
    exit;
}

/* IMÁGENES PARA 360 */
$stmt = $db->prepare("
    SELECT image_path 
    FROM product_images 
    WHERE product_id = ? 
    ORDER BY sort_order
");
$stmt->execute([$id]);

$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

$imagePaths = [];

foreach ($images as $img) {
    $imagePaths[] = 'uploads/products/' . $img['image_path'];
}

/* SI NO HAY IMÁGENES */
if (empty($imagePaths)) {
    $imagePaths[] = 'assets/placeholder.jpg';
}

/* VARIANTES */
$stmt = $db->prepare("
    SELECT * 
    FROM product_variants 
    WHERE product_id = ? 
    AND stock > 0 
    ORDER BY FIELD(size,'XS','S','M','L','XL','XXL','XXXL')
");
$stmt->execute([$id]);

$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* RESPUESTA JSON */
echo json_encode([
    'product' => $product,
    'images' => $imagePaths,
    'variants' => $variants
]);