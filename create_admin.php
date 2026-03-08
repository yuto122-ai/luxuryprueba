<?php

require_once 'php/config.php';

$db = getDB();

$name = "Admin Nuevo";
$email = "admin2@blackclothes.mx";
$password = "admin123";

$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $db->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)");

$stmt->execute([
    $name,
    $email,
    $hash,
    'admin'
]);

echo "Admin creado correctamente";

?>