<?php require_once 'php/config.php';
if(isLoggedIn()){ header("Location:index"); exit; }
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
cursor:auto;
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
position:relative;
}

.auth-close{
position:absolute;
top:12px;
right:12px;
width:32px;
height:32px;
display:flex;
justify-content:center;
align-items:center;
text-decoration:none;
color:#5f5f5f;
font-size:22px;
line-height:1;
border:1px solid rgba(255,255,255,.07);
background:#111;
border-radius:4px;
cursor:pointer;
transition:all .2s ease;
}

.auth-close:hover{
color:#8a8a8a;
border-color:rgba(255,255,255,.14);
background:#111;
}

.auth-panel{
display:none;
}

.auth-panel.active{
display:block;
}

.switch-btn{
width:100%;
margin-top:12px;
padding:10px;
background:transparent;
border:1px solid #333;
color:#fff;
cursor:pointer;
}

.form-input{
width:100%;
padding:12px;
margin-top:10px;
background:#000;
border:1px solid #333;
color:#fff;
caret-color:#fff;
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

<a href="index" class="auth-close" aria-label="Volver al inicio" title="Volver al inicio">&times;</a>

<h2 style="text-align:center;color:white">BLACK CLOTHES</h2>

<div id="login-panel" class="auth-panel active">

<input id="login-email" class="form-input" type="email" placeholder="Correo">

<input id="login-password" class="form-input" type="password" placeholder="Contraseña">

<button class="btn-auth" onclick="doLogin()">Entrar</button>

<button id="btn-register" class="switch-btn" onclick="switchMode('register')">Registrarse</button>

</div>

<div id="register-panel" class="auth-panel">

<input id="register-name" class="form-input" type="text" placeholder="Nombre completo">

<input id="register-email" class="form-input" type="email" placeholder="Correo">

<input id="register-password" class="form-input" type="password" placeholder="Contraseña">

<button class="btn-auth" onclick="doRegister()">Crear cuenta</button>

<button class="switch-btn" onclick="switchMode('login')">Ya tengo cuenta</button>

</div>

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

function switchMode(mode){

const loginPanel=document.getElementById('login-panel')
const registerPanel=document.getElementById('register-panel')

if(mode==='register'){
loginPanel.classList.remove('active')
registerPanel.classList.add('active')
}else{
registerPanel.classList.remove('active')
loginPanel.classList.add('active')
}

}

async function doRegister(){

let name=document.getElementById("register-name").value
let email=document.getElementById("register-email").value
let password=document.getElementById("register-password").value

if(!name || !email || !password){
alert("Para registrarte completa nombre, correo y contraseña")
return
}

const res=await fetch("api/auth.php",{
method:"POST",
headers:{'Content-Type':'application/json'},
body:JSON.stringify({
action:"register",
name,
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