<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

/*
===========================
BASE DE DATOS
===========================
*/

define('DB_HOST','localhost');
define('DB_USER','root');
define('DB_PASS','');
define('DB_NAME','black_clothes');

function getDB(){

    static $pdo;

    if(!$pdo){

        try{

        $pdo = new PDO(
            "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
            ]
        );

        }catch(PDOException $e){

        die("Error conexión DB: ".$e->getMessage());

        }

    }

    return $pdo;

}


/*
===========================
FUNCIONES GENERALES
===========================
*/

function sanitize($text){
    return htmlspecialchars(strip_tags(trim($text)));
}

function isLoggedIn(){
    return isset($_SESSION['user_id']);
}

function isAdmin(){
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function generateOrderNumber(){
    return "BC".date("Ymd").rand(1000,9999);
}


/*
===========================
TELEGRAM BOT CONFIG
===========================
*/

define('TELEGRAM_BOT_TOKEN','8561040825:AAHYeCtG-8jhhBvF2usI-RQ-d1vSX7xOkUM');
define('TELEGRAM_CHAT_ID','-1003203782708');


function sendTelegramMessage($message){

$token = TELEGRAM_BOT_TOKEN;
$chat_id = TELEGRAM_CHAT_ID;

$url = "https://api.telegram.org/bot".$token."/sendMessage";

$data = [
'chat_id' => $chat_id,
'text' => $message
];

$options = [
'http' => [
'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
'method'  => 'POST',
'content' => http_build_query($data),
'timeout' => 10
]
];

$context = stream_context_create($options);

/* el @ evita que errores de telegram rompan la tienda */

$result = @file_get_contents($url,false,$context);

return $result;

}