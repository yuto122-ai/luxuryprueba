<?php

require_once '../php/config.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$db = getDB();

switch($action){

case 'login':

$email = sanitize($input['email'] ?? '');
$password = $input['password'] ?? '';

if(!$email || !$password){
echo json_encode([
'success'=>false,
'message'=>'Completa todos los campos'
]);
exit;
}

$stmt = $db->prepare("SELECT * FROM users WHERE email=?");
$stmt->execute([$email]);

$user = $stmt->fetch();

if(!$user){
echo json_encode([
'success'=>false,
'message'=>'Usuario no encontrado'
]);
exit;
}

if(!password_verify($password,$user['password'])){
echo json_encode([
'success'=>false,
'message'=>'Contraseña incorrecta'
]);
exit;
}

$_SESSION['user_id']=$user['id'];
$_SESSION['user_name']=$user['name'];
$_SESSION['user_email']=$user['email'];
$_SESSION['user_role']=$user['role'];

echo json_encode([
'success'=>true,
'user'=>$user['name'],
'redirect'=>'index.php'
]);

exit;



case 'register':

$name = sanitize($input['name'] ?? '');
$email = sanitize($input['email'] ?? '');
$password = $input['password'] ?? '';

if(!$name || !$email || !$password){
echo json_encode([
'success'=>false,
'message'=>'Completa todos los campos'
]);
exit;
}

$check = $db->prepare("SELECT id FROM users WHERE email=?");
$check->execute([$email]);

if($check->fetch()){
echo json_encode([
'success'=>false,
'message'=>'Ese correo ya existe'
]);
exit;
}

$hash = password_hash($password,PASSWORD_BCRYPT);

$stmt = $db->prepare("INSERT INTO users(name,email,password,role) VALUES(?,?,?,?)");
$stmt->execute([$name,$email,$hash,'customer']);

$_SESSION['user_id']=$db->lastInsertId();
$_SESSION['user_name']=$name;
$_SESSION['user_email']=$email;
$_SESSION['user_role']='customer';

echo json_encode([
'success'=>true,
'user'=>$name,
'redirect'=>'index.php'
]);

exit;



case 'logout':

session_destroy();

echo json_encode([
'success'=>true,
'redirect'=>'login.php'
]);

exit;

default:

echo json_encode([
'success'=>false,
'message'=>'Acción inválida'
]);

exit;

}