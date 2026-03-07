<?php require_once 'php/config.php';
if(isLoggedIn()){ header("Location:index.php"); exit; }
?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Login — BLACK CLOTHES</title>

<link rel="stylesheet" href="css/style.css">

<style>

body{
background:#000;
font-family:Arial;
}

.auth-container{
height:100vh;
display:flex;
justify-content:center;
align-items:center;
}

.auth-box{
width:420px;
background:#111;
padding:40px;
border-radius:8px;
}

.form-input{
width:100%;
padding:12px;
margin-top:10px;
background:#000;
border:1px solid #333;
color:#fff;
}

.btn-auth{
width:100%;
margin-top:20px;
padding:12px;
background:#c9a84c;
border:none;
cursor:pointer;
}

</style>

</head>

<body>

<div class="auth-container">

<div class="auth-box">

<h2 style="text-align:center;color:white">BLACK CLOTHES</h2>

<input id="login-email" class="form-input" type="email" placeholder="Correo">

<input id="login-password" class="form-input" type="password" placeholder="Contraseña">

<button class="btn-auth" onclick="doLogin()">Entrar</button>

</div>

</div>

<script>

async function doLogin(){

let email=document.getElementById("login-email").value
let password=document.getElementById("login-password").value

const res=await fetch("api/auth.php",{

method:"POST",
headers:{'Content-Type':'application/json'},

body:JSON.stringify({
action:"login",
email,
password
})

})

const data=await res.json()

if(data.success){

window.location.href=data.redirect

}else{

alert(data.message)

}

}

</script>

</body>
</html>