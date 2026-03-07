<?php
require_once '../php/config.php';
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['error' => 'No product ID']); exit; }

$db = getDB();

$stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND active = 1");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) { echo json_encode(['error' => 'Product not found']); exit; }

// Images
$stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order");
$stmt->execute([$id]);
$images = $stmt->fetchAll();
$imagePaths = array_map(fn($img) => '../uploads/products/' . $img['image_path'], $images);
if (empty($imagePaths)) $imagePaths = ['../assets/placeholder.jpg'];

// Variants
$stmt = $db->prepare("SELECT * FROM product_variants WHERE product_id = ? AND stock > 0 ORDER BY FIELD(size,'XS','S','M','L','XL','XXL','XXXL')");
$stmt->execute([$id]);
$variants = $stmt->fetchAll();

echo json_encode([
    'product' => $product,
    'images' => $imagePaths,
    'variants' => $variants
]);
?>
