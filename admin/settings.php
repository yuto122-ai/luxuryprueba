<?php
require_once '../php/config.php';
requireAdmin();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $configFile = '../php/config.php';
    $content = file_get_contents($configFile);
    
    $botToken = sanitize($_POST['telegram_token']);
    $chatId = sanitize($_POST['telegram_chat_id']);
    
    $content = preg_replace("/define\('TELEGRAM_BOT_TOKEN', '.*?'\)/", "define('TELEGRAM_BOT_TOKEN', '$botToken')", $content);
    $content = preg_replace("/define\('TELEGRAM_CHAT_ID', '.*?'\)/", "define('TELEGRAM_CHAT_ID', '$chatId')", $content);
    
    if (file_put_contents($configFile, $content)) {
        $message = ['type'=>'success','text'=>'Configuración guardada correctamente'];
    } else {
        $message = ['type'=>'error','text'=>'Error: No se pudo escribir el archivo de configuración. Verifica permisos.'];
    }
}

// Test telegram
if (isset($_GET['test_telegram'])) {
    $result = sendTelegramMessage("🖤 <b>BLACK CLOTHES</b>\n\n✅ Conexión de Telegram configurada correctamente.\n\n¡Los pedidos llegarán aquí automáticamente!");
    $message = $result 
        ? ['type'=>'success','text'=>'✓ Mensaje de prueba enviado exitosamente a Telegram']
        : ['type'=>'error','text'=>'✗ Error al enviar a Telegram. Verifica tu Bot Token y Chat ID.'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración — Admin BLACK CLOTHES</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-logo">
            <h2>BLACK <span style="color:var(--gold)">CLOTHES</span></h2>
            <small>Panel de Administración</small>
        </div>
        <nav class="admin-nav">
            <a href="index.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="orders.php"><i class="fas fa-receipt"></i> Pedidos</a>
            <a href="products.php"><i class="fas fa-tshirt"></i> Productos</a>
            <a href="users.php"><i class="fas fa-users"></i> Clientes</a>
            <a href="settings.php" class="active"><i class="fas fa-cog"></i> Configuración</a>
            <hr style="border-color:rgba(255,255,255,.05);margin:20px 0">
            <a href="../index.php"><i class="fas fa-store"></i> Ver Tienda</a>
            <a href="#" onclick="logout(event)"><i class="fas fa-sign-out-alt"></i> Salir</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <h1>Configuración</h1>
        </div>

        <?php if($message): ?>
        <div class="alert alert-<?= $message['type'] ?>" style="margin-bottom:24px"><?= $message['text'] ?></div>
        <?php endif; ?>

        <!-- TELEGRAM CONFIG -->
        <div style="background:var(--dark2);border:1px solid rgba(201,168,76,.15);padding:40px;max-width:700px;margin-bottom:30px">
            <h2 style="font-family:var(--font-serif);font-size:1.4rem;margin-bottom:8px">
                <i class="fab fa-telegram" style="color:var(--gold);margin-right:12px"></i>
                Bot de Telegram
            </h2>
            <p style="color:var(--gray);font-size:.82rem;margin-bottom:28px">
                Configura tu bot de Telegram para recibir notificaciones de pedidos en tiempo real.
            </p>

            <div style="background:rgba(201,168,76,.05);border:1px solid rgba(201,168,76,.1);padding:24px;margin-bottom:28px">
                <h3 style="font-size:.75rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:16px">
                    <i class="fas fa-book"></i> Instrucciones de Configuración
                </h3>
                <ol style="font-size:.82rem;color:var(--gray);line-height:2;padding-left:20px">
                    <li>Abre Telegram y busca <strong style="color:var(--white)">@BotFather</strong></li>
                    <li>Envía <code style="background:var(--dark3);padding:2px 8px;color:var(--gold)">/newbot</code> y sigue las instrucciones</li>
                    <li>Copia el <strong style="color:var(--white)">Bot Token</strong> que te dará BotFather</li>
                    <li>Inicia una conversación con tu bot o agrégalo a un grupo</li>
                    <li>Obtén tu <strong style="color:var(--white)">Chat ID</strong> usando <code style="background:var(--dark3);padding:2px 8px;color:var(--gold)">@userinfobot</code> o la API de Telegram</li>
                    <li>Pega ambos valores abajo y guarda</li>
                    <li>Haz clic en "Probar Conexión" para verificar</li>
                </ol>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-key" style="color:var(--gold);margin-right:6px"></i>
                        Bot Token
                    </label>
                    <input type="text" name="telegram_token" class="form-input"
                        placeholder="1234567890:ABCDefGhIJKlmNoPQRsTUVwxyZ"
                        value="<?= TELEGRAM_BOT_TOKEN === 'YOUR_BOT_TOKEN_HERE' ? '' : htmlspecialchars(TELEGRAM_BOT_TOKEN) ?>">
                    <p style="font-size:.68rem;color:var(--gray);margin-top:6px">Proporcionado por @BotFather en Telegram</p>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-hashtag" style="color:var(--gold);margin-right:6px"></i>
                        Chat ID
                    </label>
                    <input type="text" name="telegram_chat_id" class="form-input"
                        placeholder="-1001234567890 o 123456789"
                        value="<?= TELEGRAM_CHAT_ID === 'YOUR_CHAT_ID_HERE' ? '' : htmlspecialchars(TELEGRAM_CHAT_ID) ?>">
                    <p style="font-size:.68rem;color:var(--gray);margin-top:6px">Tu Chat ID personal o el ID del grupo donde recibirás notificaciones</p>
                </div>
                <div style="display:flex;gap:16px;margin-top:28px">
                    <button type="submit" name="save_settings" value="1" class="btn btn-gold">
                        <i class="fas fa-save"></i> Guardar Configuración
                    </button>
                    <a href="?test_telegram=1" class="btn btn-outline">
                        <i class="fab fa-telegram"></i> Probar Conexión
                    </a>
                </div>
            </form>
        </div>

        <!-- Current Status -->
        <div style="background:var(--dark2);border:1px solid rgba(255,255,255,.05);padding:28px;max-width:700px">
            <h3 style="font-size:.7rem;letter-spacing:.25em;text-transform:uppercase;color:var(--gold);margin-bottom:16px">Estado Actual</h3>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
                <?php if(TELEGRAM_BOT_TOKEN !== 'YOUR_BOT_TOKEN_HERE'): ?>
                <span style="color:#4acc80"><i class="fas fa-check-circle"></i></span>
                <span style="font-size:.85rem">Bot Token configurado</span>
                <?php else: ?>
                <span style="color:#ff6666"><i class="fas fa-times-circle"></i></span>
                <span style="font-size:.85rem;color:var(--gray)">Bot Token no configurado</span>
                <?php endif; ?>
            </div>
            <div style="display:flex;align-items:center;gap:12px">
                <?php if(TELEGRAM_CHAT_ID !== 'YOUR_CHAT_ID_HERE'): ?>
                <span style="color:#4acc80"><i class="fas fa-check-circle"></i></span>
                <span style="font-size:.85rem">Chat ID configurado</span>
                <?php else: ?>
                <span style="color:#ff6666"><i class="fas fa-times-circle"></i></span>
                <span style="font-size:.85rem;color:var(--gray)">Chat ID no configurado</span>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<script src="../js/main.js"></script>
<script>async function logout(e){e.preventDefault();const r=await fetch('../api/auth.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'logout'})});const d=await r.json();window.location.href=d.redirect;}</script>
</body>
</html>
