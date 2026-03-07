<?php

require_once '../php/config.php';

header('Content-Type: application/json');

try{

$input = json_decode(file_get_contents('php://input'), true);

if(!$input){
echo json_encode(['success'=>false,'message'=>'Datos inválidos']);
exit;
}

$action = $input['action'] ?? '';

if($action !== 'place'){
echo json_encode(['success'=>false,'message'=>'Acción inválida']);
exit;
}

if(!isLoggedIn()){
echo json_encode(['success'=>false,'message'=>'Debes iniciar sesión']);
exit;
}

$db = getDB();
$userId = $_SESSION['user_id'];


// =================
// OBTENER CARRITO
// =================

$stmt = $db->prepare("
SELECT c.*,p.name,p.material,p.price_individual
FROM cart c
JOIN products p ON c.product_id=p.id
WHERE c.user_id=?
");

$stmt->execute([$userId]);

$items = $stmt->fetchAll();

if(!$items){
echo json_encode(['success'=>false,'message'=>'Carrito vacío']);
exit;
}


// =================
// DATOS ENVIO
// =================

$name = sanitize($input['shipping_name'] ?? '');
$phone = sanitize($input['shipping_phone'] ?? '');
$address = sanitize($input['shipping_address'] ?? '');
$notes = sanitize($input['notes'] ?? '');

if(!$name || !$phone || !$address){
echo json_encode(['success'=>false,'message'=>'Completa los datos de envío']);
exit;
}


// =================
// TOTAL
// =================

$total = 0;

foreach($items as $item){

$price = $item['price_individual'];
$total += $price * $item['quantity'];

}


// =================
// NUMERO ORDEN
// =================

$orderNumber = generateOrderNumber();


// =================
// GUARDAR ORDEN
// =================

$stmt = $db->prepare("
INSERT INTO orders (user_id,order_number,total)
VALUES (?,?,?)
");

$stmt->execute([$userId,$orderNumber,$total]);


// =================
// LISTA PRODUCTOS
// =================

$productList = "";

foreach($items as $item){

$productList .= "• ".$item['name']." x".$item['quantity']."\n";

}


// =================
// MENSAJE TELEGRAM
// =================

$message = "🖤 NUEVO PEDIDO — BLACK CLOTHES\n\n";

$message .= "📦 Pedido: #".$orderNumber."\n\n";

$message .= "👤 Cliente: ".$name."\n";
$message .= "📞 Teléfono: ".$phone."\n";
$message .= "📍 Dirección: ".$address."\n\n";

$message .= "🛒 Productos:\n".$productList."\n";

$message .= "💰 Total: $".$total."\n";

if($notes){
$message .= "\n📝 Notas: ".$notes;
}


// =================
// ENVIAR TELEGRAM
// =================

try{
sendTelegramMessage($message);
}catch(Exception $e){
}


// =================
// LIMPIAR CARRITO
// =================

$stmt = $db->prepare("DELETE FROM cart WHERE user_id=?");
$stmt->execute([$userId]);


// =================
// RESPUESTA
// =================

echo json_encode([
'success'=>true,
'order_number'=>$orderNumber,
'total'=>$total
]);

}catch(Throwable $e){

echo json_encode([
'success'=>false,
'message'=>'Error servidor',
'error'=>$e->getMessage()
]);

}