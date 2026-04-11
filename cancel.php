<?php
require_once 'php/config.php';

if (!empty($_GET['order_id'])) {
    $orderId = (int)$_GET['order_id'];
    if ($orderId > 0) {
        try {
            $db = getDB();
            $db->beginTransaction();
            $db->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$orderId]);
            $db->prepare("DELETE FROM orders WHERE id = ? AND status = 'pending'")->execute([$orderId]);
            $db->commit();
        } catch (Throwable $e) {
            if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
                $db->rollBack();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago cancelado — BLACK CLOTHES</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="background:black;color:white;text-align:center;padding:100px 20px">
    <h1>❌ Pago cancelado</h1>
    <p>No se completó el pago.</p>
    <a href="checkout" style="color:gold;text-decoration:none">Intentar otra vez</a>
</body>
</html>