<?php
require_once 'php/config.php';

$paymentStatus = 'Pago completado';
$message = 'Gracias por tu compra en BLACK CLOTHES.';
$orderDetails = null;

if (!isset($_GET['session_id'])) {
    $paymentStatus = 'Pago incompleto';
    $message = 'No se encontró información de sesión. Por favor contacta a soporte.';
} else {
    $sessionId = $_GET['session_id'];

    $vendorAutoload = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($vendorAutoload)) {
        $vendorAutoload = __DIR__ . '/../vendor/autoload.php';
    }
    if (!file_exists($vendorAutoload)) {
        die('Falta vendor/autoload.php. Ejecuta `composer install` o `composer require stripe/stripe-php` en la raíz del proyecto.');
    }
    require_once $vendorAutoload;

    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    try {
        $session = \Stripe\Checkout\Session::retrieve($sessionId);

        if ($session && $session->payment_status === 'paid') {
            $orderId = $session->metadata->order_id ?? null;
            if ($orderId) {
                $db = getDB();
                
                // Actualizar estado de orden
                $stmt = $db->prepare("UPDATE orders SET status = 'paid', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$orderId]);
                
                // LIMPIAR CARRITO - crucial para evitar duplicados
                $sessionIdLocal = session_id();
                $userId = $_SESSION['user_id'] ?? null;
                if ($userId) {
                    $db->prepare('DELETE FROM cart WHERE user_id = ?')->execute([$userId]);
                } else {
                    $db->prepare('DELETE FROM cart WHERE session_id = ?')->execute([$sessionIdLocal]);
                }
                
                // Obtener detalles de orden para mostrar
                $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
                $stmt->execute([$orderId]);
                $orderDetails = $stmt->fetch();
            }
            $paymentStatus = 'Pago exitoso';
            $message = 'Tu pago se ha registrado correctamente. Tu pedido se encuentra en proceso.';
        } else {
            $paymentStatus = 'Pago pendiente';
            $message = 'El pago aún no se ha confirmado. Si ya pagaste, espera unos minutos y recarga esta página.';
        }
    } catch (Throwable $e) {
        $paymentStatus = 'Error en verificación de pago';
        $message = 'No se pudo verificar el pago: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Pago exitoso</title>
</head>
<body style="background:black;color:white;text-align:center;padding:100px">
<h1>✅ <?= htmlspecialchars($paymentStatus) ?></h1>
<p><?= nl2br(htmlspecialchars($message)) ?></p>
<a href="index" style="color:gold">Volver</a>
</body>
</html>