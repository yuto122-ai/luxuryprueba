<?php require_once 'php/config.php';
if(isLoggedIn()) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — BLACK CLOTHES</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .auth-visual {
            position:fixed; top:0; left:0; right:0; bottom:0; z-index:0;
            background: radial-gradient(ellipse at 30% 50%, rgba(201,168,76,.08) 0%, transparent 50%),
                        radial-gradient(ellipse at 70% 20%, rgba(201,168,76,.05) 0%, transparent 40%),
                        #000;
        }
        .auth-page { position:relative; z-index:1; }
        .auth-form { animation:fadeInUp .4s ease both; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        @media(max-width:480px) { .form-row { grid-template-columns:1fr; } }
        .divider {
            display:flex; align-items:center; gap:16px; margin:20px 0;
            color:var(--gray); font-size:.7rem; letter-spacing:.2em; text-transform:uppercase;
        }
        .divider::before, .divider::after {
            content:''; flex:1; height:1px; background:rgba(255,255,255,.08);
        }
    </style>
</head>
<body>
<div class="auth-visual"></div>

<div class="auth-page">
    <div style="position:absolute;top:30px;left:40px">
        <a href="index.php" style="color:var(--gray);text-decoration:none;font-size:.72rem;letter-spacing:.2em;text-transform:uppercase;display:flex;align-items:center;gap:8px;transition:var(--transition)"
           onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='var(--gray)'">
            <i class="fas fa-arrow-left"></i> Regresar
        </a>
    </div>

    <div class="auth-box">
        <div class="auth-logo">
            <h1>BLACK <span>CLOTHES</span></h1>
            <p style="font-size:.72rem;letter-spacing:.2em;color:var(--gray);text-transform:uppercase;margin-top:8px">Tu cuenta exclusiva</p>
        </div>

        <div class="auth-tabs">
            <button class="auth-tab active" data-tab="login-form">Iniciar Sesión</button>
            <button class="auth-tab" data-tab="register-form">Crear Cuenta</button>
        </div>

        <!-- LOGIN FORM -->
        <div class="auth-form" id="login-form">
            <div id="login-alert"></div>
            <div class="form-group">
                <label class="form-label">Correo electrónico</label>
                <input type="email" class="form-input" id="login-email" placeholder="tu@email.com" required>
            </div>
            <div class="form-group">
                <label class="form-label">Contraseña</label>
                <div style="position:relative">
                    <input type="password" class="form-input" id="login-password" placeholder="••••••••" required style="padding-right:48px">
                    <button type="button" onclick="togglePass('login-password', this)" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--gray);cursor:none;font-size:.9rem">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <button class="btn btn-gold" onclick="doLogin()" style="width:100%;justify-content:center;margin-top:8px" id="login-btn">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
            <div class="divider">o</div>
            <p style="text-align:center;font-size:.78rem;color:var(--gray)">
                ¿No tienes cuenta? 
                <button class="auth-tab" data-tab="register-form" onclick="switchTab('register-form')" style="background:none;border:none;color:var(--gold);cursor:none;font-size:.78rem;letter-spacing:0;padding:0;text-transform:none">Regístrate</button>
            </p>
        </div>

        <!-- REGISTER FORM -->
        <div class="auth-form" id="register-form" style="display:none">
            <div id="register-alert"></div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nombre completo *</label>
                    <input type="text" class="form-input" id="reg-name" placeholder="Tu nombre" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="tel" class="form-input" id="reg-phone" placeholder="55 1234 5678">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Correo electrónico *</label>
                <input type="email" class="form-input" id="reg-email" placeholder="tu@email.com" required>
            </div>
            <div class="form-group">
                <label class="form-label">Contraseña *</label>
                <div style="position:relative">
                    <input type="password" class="form-input" id="reg-password" placeholder="Mínimo 6 caracteres" required style="padding-right:48px">
                    <button type="button" onclick="togglePass('reg-password', this)" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--gray);cursor:none;font-size:.9rem">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <button class="btn btn-gold" onclick="doRegister()" style="width:100%;justify-content:center;margin-top:8px" id="register-btn">
                <i class="fas fa-user-plus"></i> Crear Cuenta
            </button>
        </div>
    </div>
</div>

<script src="js/main.js"></script>
<script>
function togglePass(fieldId, btn) {
    const field = document.getElementById(fieldId);
    const icon = btn.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text'; icon.className = 'fas fa-eye-slash';
    } else {
        field.type = 'password'; icon.className = 'fas fa-eye';
    }
}

function switchTab(tabId) {
    document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.auth-form').forEach(f => f.style.display = 'none');
    const form = document.getElementById(tabId);
    if (form) form.style.display = 'block';
    document.querySelectorAll('[data-tab="'+tabId+'"]').forEach(t => t.classList.add('active'));
}

function showAlert(containerId, message, type) {
    const el = document.getElementById(containerId);
    el.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    setTimeout(() => el.innerHTML = '', 5000);
}

async function doLogin() {
    const btn = document.getElementById('login-btn');
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;
    if (!email || !password) { showAlert('login-alert','Completa todos los campos','error'); return; }
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Entrando...';
    btn.disabled = true;
    
    try {
        const res = await fetch('api/auth.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({action:'login', email, password})
        });
        const data = await res.json();
        if (data.success) {
            showAlert('login-alert', data.message, 'success');
            setTimeout(() => window.location.href = data.redirect, 800);
        } else {
            showAlert('login-alert', data.message, 'error');
            btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Entrar';
            btn.disabled = false;
        }
    } catch(e) {
        showAlert('login-alert','Error de conexión','error');
        btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Entrar';
        btn.disabled = false;
    }
}

async function doRegister() {
    const btn = document.getElementById('register-btn');
    const name = document.getElementById('reg-name').value;
    const email = document.getElementById('reg-email').value;
    const password = document.getElementById('reg-password').value;
    const phone = document.getElementById('reg-phone').value;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando cuenta...';
    btn.disabled = true;
    
    try {
        const res = await fetch('api/auth.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({action:'register', name, email, password, phone})
        });
        const data = await res.json();
        if (data.success) {
            showAlert('register-alert', data.message, 'success');
            setTimeout(() => window.location.href = data.redirect, 800);
        } else {
            showAlert('register-alert', data.message, 'error');
            btn.innerHTML = '<i class="fas fa-user-plus"></i> Crear Cuenta';
            btn.disabled = false;
        }
    } catch(e) {
        showAlert('register-alert','Error de conexión','error');
        btn.innerHTML = '<i class="fas fa-user-plus"></i> Crear Cuenta';
        btn.disabled = false;
    }
}

// Enter key triggers login
document.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
        const loginForm = document.getElementById('login-form');
        const regForm = document.getElementById('register-form');
        if (loginForm.style.display !== 'none') doLogin();
        else if (regForm.style.display !== 'none') doRegister();
    }
});
</script>
</body>
</html>
