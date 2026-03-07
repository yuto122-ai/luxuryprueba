<?php
require_once '../php/config.php';
requireAdmin();
$db = getDB();

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$status, $orderId]);
    
    // Re-send Telegram if needed
    if ($_POST['resend_telegram'] ?? false) {
        $order = $db->prepare("SELECT o.*, u.name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?")->execute([$orderId]);
        // simplified resend
    }
    header('Location: orders.php?updated=1');
    exit;
}

$statusFilter = $_GET['status'] ?? 'all';
$viewId = (int)($_GET['view'] ?? 0);

$where = $statusFilter !== 'all' ? "WHERE o.status = " . $db->quote($statusFilter) : '';
$orders = $db->query("
    SELECT o.*, u.name as customer_name
    FROM orders o LEFT JOIN users u ON o.user_id = u.id
    $where
    ORDER BY o.created_at DESC
")->fetchAll();

// View single order
$viewOrder = null;
$viewItems = [];
if ($viewId) {
    $viewOrder = $db->prepare("SELECT o.*, u.name as customer_name, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?")->execute([$viewId]) ? null : null;
    $stmt = $db->prepare("SELECT o.*, u.name as customer_name, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->execute([$viewId]);
    $viewOrder = $stmt->fetch();
    $stmt2 = $db->prepare("SELECT oi.*, p.material FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $stmt2->execute([$viewId]);
    $viewItems = $stmt2->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos — Admin BLACK CLOTHES</title>
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
            <a href="orders.php" class="active"><i class="fas fa-receipt"></i> Pedidos</a>
            <a href="products.php"><i class="fas fa-tshirt"></i> Productos</a>
            <a href="users.php"><i class="fas fa-users"></i> Clientes</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Configuración</a>
            <hr style="border-color:rgba(255,255,255,.05);margin:20px 0">
            <a href="../index.php"><i class="fas fa-store"></i> Ver Tienda</a>
            <a href="#" onclick="logout(event)"><i class="fas fa-sign-out-alt"></i> Salir</a>
        </nav>
    </aside>

    <main class="admin-main">
        <?php if(isset($_GET['updated'])): ?>
        <div class="alert alert-success"><i class="fas fa-check"></i> Estado actualizado correctamente</div>
        <?php endif; ?>

        <?php if($viewOrder): ?>
        <!-- SINGLE ORDER VIEW -->
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:30px">
            <a href="orders.php" class="btn btn-dark" style="font-size:.65rem;padding:8px 16px"><i class="fas fa-arrow-left"></i> Volver</a>
            <h1 style="font-family:var(--font-serif);font-size:1.8rem;font-weight:300">
                Pedido <span style="color:var(--gold)">#<?= htmlspecialchars($viewOrder['order_number']) ?></span>
            </h1>
            <span class="status-badge status-<?= $viewOrder['status'] ?>"><?= ucfirst($viewOrder['status']) ?></span>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px">
            <div style="background:var(--dark2);border:1px solid rgba(255,255,255,.05);padding:28px">
                <h3 style="font-size:.7rem;letter-spacing:.25em;text-transform:uppercase;color:var(--gold);margin-bottom:16px">Cliente & Envío</h3>
                <p><strong><?= htmlspecialchars($viewOrder['shipping_name']) ?></strong></p>
                <p style="color:var(--gray);font-size:.85rem"><?= htmlspecialchars($viewOrder['shipping_phone']) ?></p>
                <?php if($viewOrder['customer_name']): ?><p style="color:var(--gray);font-size:.82rem">Cuenta: <?= htmlspecialchars($viewOrder['customer_name']) ?></p><?php endif; ?>
                <p style="color:var(--gray-light);font-size:.85rem;margin-top:12px"><?= nl2br(htmlspecialchars($viewOrder['shipping_address'])) ?></p>
                <?php if($viewOrder['notes']): ?><p style="color:var(--gray);font-size:.8rem;margin-top:8px;font-style:italic">"<?= htmlspecialchars($viewOrder['notes']) ?>"</p><?php endif; ?>
            </div>
            <div style="background:var(--dark2);border:1px solid rgba(255,255,255,.05);padding:28px">
                <h3 style="font-size:.7rem;letter-spacing:.25em;text-transform:uppercase;color:var(--gold);margin-bottom:16px">Detalles del Pedido</h3>
                <p>Tipo: <strong><?= $viewOrder['order_type'] === 'wholesale' ? '🏭 Mayoreo' : '🛍 Individual' ?></strong></p>
                <p>Subtotal: $<?= number_format($viewOrder['subtotal'], 2) ?></p>
                <p>Envío: <?= $viewOrder['shipping'] > 0 ? '$' . number_format($viewOrder['shipping'], 2) : 'GRATIS' ?></p>
                <p style="font-family:var(--font-serif);font-size:1.5rem;color:var(--gold);margin-top:12px">Total: $<?= number_format($viewOrder['total'], 2) ?></p>
                <p style="font-size:.75rem;color:var(--gray);margin-top:8px"><?= date('d/m/Y H:i', strtotime($viewOrder['created_at'])) ?></p>
                <p style="font-size:.72rem;margin-top:6px">
                    Telegram: <?= $viewOrder['telegram_sent'] ? '<span style="color:#4acc80"><i class="fas fa-check"></i> Enviado</span>' : '<span style="color:#ff6666"><i class="fas fa-times"></i> No enviado</span>' ?>
                </p>
            </div>
        </div>

        <!-- Items -->
        <div style="background:var(--dark2);border:1px solid rgba(255,255,255,.05);padding:28px;margin-bottom:24px">
            <h3 style="font-size:.7rem;letter-spacing:.25em;text-transform:uppercase;color:var(--gold);margin-bottom:20px">Productos Ordenados</h3>
            <table class="data-table">
                <thead><tr><th>Producto</th><th>Material</th><th>Talla</th><th>Cant.</th><th>P. Unit.</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php foreach($viewItems as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td style="color:var(--gold)"><?= $item['material'] === 'cotton' ? 'Algodón' : 'Poliéster' ?></td>
                    <td><?= $item['size'] ?: 'Única' ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>$<?= number_format($item['unit_price'], 2) ?></td>
                    <td style="color:var(--gold)">$<?= number_format($item['subtotal'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Update status -->
        <div style="background:var(--dark2);border:1px solid rgba(201,168,76,.15);padding:28px">
            <h3 style="font-size:.7rem;letter-spacing:.25em;text-transform:uppercase;color:var(--gold);margin-bottom:20px">Actualizar Estado</h3>
            <form method="POST" style="display:flex;gap:16px;flex-wrap:wrap;align-items:end">
                <input type="hidden" name="order_id" value="<?= $viewId ?>">
                <div>
                    <label class="form-label">Nuevo Estado</label>
                    <select name="status" class="form-input" style="min-width:200px">
                        <?php foreach(['pending','confirmed','processing','shipped','delivered','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $viewOrder['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="update_status" value="1" class="btn btn-gold">
                    <i class="fas fa-save"></i> Guardar Estado
                </button>
            </form>
        </div>

        <?php else: ?>
        <!-- ORDERS LIST -->
        <div class="admin-header">
            <h1>Pedidos</h1>
        </div>

        <!-- Filter tabs -->
        <div style="display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap">
            <?php foreach(['all'=>'Todos','pending'=>'Pendientes','confirmed'=>'Confirmados','processing'=>'En Proceso','shipped'=>'Enviados','delivered'=>'Entregados','cancelled'=>'Cancelados'] as $s => $label): ?>
            <a href="?status=<?= $s ?>" 
               style="padding:8px 18px;font-size:.68rem;letter-spacing:.15em;text-transform:uppercase;text-decoration:none;border:1px solid rgba(255,255,255,.1);color:var(--gray);transition:var(--transition);<?= $statusFilter === $s ? 'border-color:var(--gold);color:var(--gold);background:rgba(201,168,76,.06)' : '' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nº Pedido</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Telegram</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($orders)): ?>
                <tr><td colspan="8" style="text-align:center;color:var(--gray);padding:40px">No hay pedidos <?= $statusFilter !== 'all' ? '"' . $statusFilter . '"' : '' ?></td></tr>
                <?php else: ?>
                <?php foreach($orders as $o): ?>
                <tr>
                    <td><strong style="color:var(--gold)"><?= htmlspecialchars($o['order_number']) ?></strong></td>
                    <td>
                        <div><?= htmlspecialchars($o['customer_name'] ?? $o['shipping_name']) ?></div>
                        <div style="font-size:.72rem;color:var(--gray)"><?= htmlspecialchars($o['shipping_phone']) ?></div>
                    </td>
                    <td><span style="font-size:.65rem;color:<?= $o['order_type'] === 'wholesale' ? 'var(--gold)' : 'var(--gray)' ?>"><?= $o['order_type'] === 'wholesale' ? '🏭 Mayoreo' : '🛍 Individual' ?></span></td>
                    <td style="color:var(--gold);font-weight:500">$<?= number_format($o['total'], 2) ?></td>
                    <td><span class="status-badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                    <td style="text-align:center"><?= $o['telegram_sent'] ? '<span style="color:#4acc80">✓</span>' : '<span style="color:#444">—</span>' ?></td>
                    <td style="font-size:.78rem;color:var(--gray)"><?= date('d/m/y H:i', strtotime($o['created_at'])) ?></td>
                    <td><a href="?view=<?= $o['id'] ?>" class="btn btn-dark" style="font-size:.6rem;padding:6px 14px"><i class="fas fa-eye"></i> Ver</a></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </main>
</div>
<script src="../js/main.js"></script>
<script>async function logout(e){e.preventDefault();const r=await fetch('../api/auth.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'logout'})});const d=await r.json();window.location.href=d.redirect;}</script>
</body>
</html>
