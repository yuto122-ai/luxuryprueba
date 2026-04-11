<?php
require_once 'php/config.php';

$paymentStatus = 'Pago completado';
$message = 'Gracias por tu compra en BLACK CLOTHES.';
$orderDetails = null;

if (!isset($_GET['session_id'])) {
    $paymentStatus = 'Pago incompleto';
    $message = 'No se encontró información de la sesión.';
} else {
    $sessionId = $_GET['session_id'];

    $vendorAutoload = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($vendorAutoload)) {
        $vendorAutoload = __DIR__ . '/../vendor/autoload.php';
    }
    if (!file_exists($vendorAutoload)) {
        $vendorAutoload = __DIR__ . '/luxuryprueba-master/vendor/autoload.php';
    }
    if (!file_exists($vendorAutoload)) {
        $vendorAutoload = __DIR__ . '/../luxuryprueba-master/vendor/autoload.php';
    }
    if (!defined('STRIPE_SECRET_KEY') || STRIPE_SECRET_KEY === '') {
        die('Falta configurar STRIPE_SECRET_KEY.');
    }

    function stripeApiRequest(string $method, string $path): array {
        $ch = curl_init('https://api.stripe.com' . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => STRIPE_SECRET_KEY . ':',
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $decoded = json_decode((string)$response, true);
        if ($response === false || $curlError) {
            throw new RuntimeException($curlError ?: 'Error de red al contactar Stripe');
        }
        if ($status < 200 || $status >= 300) {
            $message = $decoded['error']['message'] ?? ('Stripe respondió con estado ' . $status);
            throw new RuntimeException($message);
        }
        if (!is_array($decoded)) {
            throw new RuntimeException('Respuesta inválida de Stripe');
        }
        return $decoded;
    }

    try {
        $session = stripeApiRequest('GET', '/v1/checkout/sessions/' . rawurlencode($sessionId));

        if ($session && ($session['payment_status'] ?? '') === 'paid') {
            $orderId = $session['metadata']['order_id'] ?? null;
            if ($orderId) {
                $db = getDB();
                $db->prepare("UPDATE orders SET status = 'paid', updated_at = NOW() WHERE id = ?")
                    ->execute([$orderId]);

                $userId = $_SESSION['user_id'] ?? null;
                if ($userId) {
                    $db->prepare('DELETE FROM cart WHERE user_id = ?')->execute([$userId]);
                } else {
                    $db->prepare('DELETE FROM cart WHERE session_id = ?')->execute([session_id()]);
                }

                $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
                $stmt->execute([$orderId]);
                $orderDetails = $stmt->fetch();
            }
            $paymentStatus = 'Pago exitoso';
            $message = 'Tu pago se registró correctamente. Tu pedido ya está en proceso.';
        } else {
            $paymentStatus = 'Pago pendiente';
            $message = 'El pago aún no se ha confirmado.';
        }
    } catch (Throwable $e) {
        $paymentStatus = 'Error en verificación de pago';
        $message = 'No se pudo verificar el pago: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago exitoso — BLACK CLOTHES</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="background:black;color:white;text-align:center;padding:100px 20px">
    <h1>✅ <?= htmlspecialchars($paymentStatus) ?></h1>
    <p><?= nl2br(htmlspecialchars($message)) ?></p>
    <?php if ($orderDetails): ?>
        <p>Pedido: <strong><?= htmlspecialchars($orderDetails['order_number']) ?></strong></p>
        <p>Total: <strong>$<?= number_format((float)$orderDetails['total'], 2) ?></strong></p>
    <?php endif; ?>
    <a href="index" style="color:gold">Volver</a>
</body>
</html>