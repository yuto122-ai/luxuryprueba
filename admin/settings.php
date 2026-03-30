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

// Handle colors
$db = getDB();
$editColor = null;
if (isset($_GET['edit_color'])) {
    $id = (int)$_GET['edit_color'];
    $editColor = $db->prepare("SELECT * FROM colors WHERE id = ?")->execute([$id])->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_color'])) {
    $id = (int)$_POST['id'];
    $name = sanitize($_POST['color_name']);
    $hex = $_POST['color_hex'];
    $extra = (float)$_POST['color_extra'];
    $db->prepare("UPDATE colors SET name = ?, hex = ?, extra_price = ? WHERE id = ?")->execute([$name, $hex, $extra, $id]);
    // Handle image upload
    if (!empty($_FILES['color_image']['name'])) {
        $uploadDir = '../uploads/colors/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = pathinfo($_FILES['color_image']['name'], PATHINFO_EXTENSION);
        $newName = 'color_' . $id . '.' . $ext;
        move_uploaded_file($_FILES['color_image']['tmp_name'], $uploadDir . $newName);
        $db->prepare("UPDATE colors SET image_path = ? WHERE id = ?")->execute([$newName, $id]);
    }
    $message = ['type'=>'success','text'=>'Color actualizado'];
    header('Location: settings.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_color'])) {
    $name = sanitize($_POST['color_name']);
    $hex = $_POST['color_hex'];
    $extra = (float)$_POST['color_extra'];
    $stmt = $db->prepare("INSERT INTO colors (name, hex, extra_price) VALUES (?, ?, ?)");
    $stmt->execute([$name, $hex, $extra]);
    $newId = $db->lastInsertId();
    // Handle image
    if (!empty($_FILES['add_color_image']['name'])) {
        $uploadDir = '../uploads/colors/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = pathinfo($_FILES['color_image']['name'], PATHINFO_EXTENSION);
        $newName = 'color_' . $newId . '.' . $ext;
        move_uploaded_file($_FILES['add_color_image']['tmp_name'], $uploadDir . $newName);
        $db->prepare("UPDATE colors SET image_path = ? WHERE id = ?")->execute([$newName, $newId]);
    }
    $message = ['type'=>'success','text'=>'Color añadido correctamente'];
}

if (isset($_GET['delete_color'])) {
    $id = (int)$_GET['delete_color'];
    $db->prepare("DELETE FROM colors WHERE id = ?")->execute([$id]);
    $message = ['type'=>'success','text'=>'Color eliminado'];
}

$colors = $db->query("SELECT * FROM colors ORDER BY id")->fetchAll();

// Handle images
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['login_image'])) {
    $uploadDir = '../uploads/login/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $file = $_FILES['login_image'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newName = 'login_bg.' . $ext;
        move_uploaded_file($file['tmp_name'], $uploadDir . $newName);
        $message = ['type'=>'success','text'=>'Imagen subida correctamente'];
    } else {
        $message = ['type'=>'error','text'=>'Error al subir la imagen'];
    }
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
            <a href="index"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="orders"><i class="fas fa-receipt"></i> Pedidos</a>
            <a href="products"><i class="fas fa-tshirt"></i> Productos</a>
            <a href="users"><i class="fas fa-users"></i> Clientes</a>
            <a href="settings" class="active"><i class="fas fa-cog"></i> Configuración</a>
            <hr style="border-color:rgba(255,255,255,.05);margin:20px 0">
            <a href="../index"><i class="fas fa-store"></i> Ver Tienda</a>
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

        <!-- COLORS MANAGEMENT -->
        <div style="background:var(--dark2);border:1px solid rgba(201,168,76,.15);padding:40px;max-width:700px;margin-bottom:30px">
            <h2 style="font-family:var(--font-serif);font-size:1.4rem;margin-bottom:8px">
                <i class="fas fa-palette" style="color:var(--gold);margin-right:12px"></i>
                Gestión de Colores
            </h2>
            <p style="color:var(--gray);font-size:.82rem;margin-bottom:28px">
                Administra los colores disponibles para los productos en el visor 360.
            </p>

            <form method="POST" enctype="multipart/form-data" style="margin-bottom:28px">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:16px;align-items:end">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="color_name" class="form-input" required>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Código Hex</label>
                        <input type="color" name="color_hex" class="form-input" required>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Extra ($)</label>
                        <input type="number" step="0.01" name="color_extra" class="form-input" value="0">
                    </div>
                    <button type="submit" name="add_color" class="btn btn-gold">
                        <i class="fas fa-plus"></i> Añadir
                    </button>
                </div>
                <div class="form-group" style="margin-top:16px">
                    <label class="form-label">Imagen (opcional)</label>
                    <input type="file" name="add_color_image" class="form-input" accept="image/*">
                </div>
            </form>

            <?php if ($editColor): ?>
            <div style="margin-bottom:28px">
                <h3>Editar Color</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $editColor['id'] ?>">
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;align-items:end">
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="color_name" class="form-input" value="<?= htmlspecialchars($editColor['name']) ?>" required>
                        </div>
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Código Hex</label>
                            <input type="color" name="color_hex" class="form-input" value="<?= $editColor['hex'] ?>" required>
                        </div>
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Extra ($)</label>
                            <input type="number" step="0.01" name="color_extra" class="form-input" value="<?= $editColor['extra_price'] ?>" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top:16px">
                        <label class="form-label">Imagen (opcional)</label>
                        <input type="file" name="color_image" class="form-input" accept="image/*">
                    </div>
                    <div style="display:flex;gap:16px;margin-top:16px">
                        <button type="submit" name="edit_color" class="btn btn-gold">Actualizar</button>
                        <a href="settings.php" class="btn btn-outline">Cancelar</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div style="background:rgba(201,168,76,.05);border:1px solid rgba(201,168,76,.1);padding:24px">
                <h3 style="font-size:.75rem;letter-spacing:.25em;text-transform:uppercase;color:var(--gold);margin-bottom:16px">
                    Colores Actuales
                </h3>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px">
                    <?php foreach ($colors as $color): ?>
                    <div style="background:var(--dark3);border:1px solid rgba(255,255,255,.05);padding:12px;text-align:center">
                        <div style="width:40px;height:40px;border-radius:50%;background:<?= $color['hex'] ?>;margin:0 auto 8px;border:<?= $color['hex'] == '#ffffff' ? '1px solid #000' : 'none' ?>"></div>
                        <p style="font-size:.8rem;color:var(--white)"><?= htmlspecialchars($color['name']) ?></p>
                        <p style="font-size:.7rem;color:var(--gray)">Extra: $<?= number_format($color['extra_price'], 2) ?></p>
                        <?php if ($color['image_path']): ?>
                        <p style="font-size:.7rem;color:var(--gray)">Imagen: Sí</p>
                        <?php endif; ?>
                        <div style="display:flex;gap:8px;margin-top:8px">
                            <a href="?edit_color=<?= $color['id'] ?>" style="color:var(--gold);font-size:.7rem">Editar</a>
                            <a href="?delete_color=<?= $color['id'] ?>" onclick="return confirm('¿Eliminar este color?')" style="color:#ff6666;font-size:.7rem">Eliminar</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- LOGIN IMAGES -->
        <div style="background:var(--dark2);border:1px solid rgba(201,168,76,.15);padding:40px;max-width:700px;margin-bottom:30px">
            <h2 style="font-family:var(--font-serif);font-size:1.4rem;margin-bottom:8px">
                <i class="fas fa-images" style="color:var(--gold);margin-right:12px"></i>
                Imágenes de Login
            </h2>
            <p style="color:var(--gray);font-size:.82rem;margin-bottom:28px">
                Sube imágenes para personalizar la página de login.
            </p>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Imagen de Fondo</label>
                    <input type="file" name="login_image" class="form-input" accept="image/*">
                </div>
                <button type="submit" class="btn btn-gold">
                    <i class="fas fa-upload"></i> Subir Imagen
                </button>
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
<script>async function logout(e){e.preventDefault();const r=await fetch('../api/auth.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'logout'})});const d=await r.json();window.location.href='../'+(d.redirect||'login');}</script>
</body>
</html>
