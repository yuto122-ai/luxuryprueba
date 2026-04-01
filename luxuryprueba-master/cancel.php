<?php
require_once 'php/config.php';

// Limpiar orden pendiente si es necesario
if (!empty($_GET['order_id'])) {
    $orderId = intval($_GET['order_id']);
    if ($orderId > 0) {
        try {
            $db = getDB();
            $db->prepare("DELETE FROM orders WHERE id = ? AND status = 'pending'")
                ->execute([$orderId]);
        } catch (Exception $e) {
            // Log o ignorar
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Pago cancelado</title>
</head>
<body style="background:black;color:white;text-align:center;padding:100px">
<h1>❌ Pago cancelado</h1>
<p>No se completó el pago</p>
<p>Tu carrito se ha preservado. Puedes intentar nuevamente cuando estés listo.</p>
<a href="index.php" style="color:gold;text-decoration:none">Intentar otra vez</a>
</body>
</html>