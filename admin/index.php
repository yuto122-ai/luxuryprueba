<?php
require_once '../php/config.php';
requireAdmin();
$db = getDB();

$totalOrders   = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$totalRevenue  = $db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$totalProducts = $db->query("SELECT COUNT(*) FROM products WHERE active = 1")->fetchColumn();
$totalUsers    = $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();

$recentOrders = $db->query("
    SELECT o.*, u.name as customer_name, u.email as customer_email
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — BLACK CLOTHES</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { overflow-x:hidden; }
        .admin-header-top {
            background:var(--dark2); border:1px solid rgba(255,255,255,.04);
            padding:20px 30px; display:flex; align-items:center; justify-content:space-between;
            margin-bottom:36px;
        }
        .quick-actions { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:36px; }
        .revenue-card {
            background:linear-gradient(135deg,#0a0a0a,#111);
            border:1px solid rgba(201,168,76,.3);
            border-bottom:3px solid var(--gold);
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-logo">
            <h2>BLACK <span style="color:var(--gold)">CLOTHES</span></h2>
            <small>Panel de Administración</small>
        </div>
        <nav class="admin-nav">
            <a href="index.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="orders.php"><i class="fas fa-receipt"></i> Pedidos</a>
            <a href="products.php"><i class="fas fa-tshirt"></i> Productos</a>
            <a href="users.php"><i class="fas fa-users"></i> Clientes</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Configuración</a>
            <hr style="border-color:rgba(255,255,255,.05);margin:20px 0">
            <a href="../index.php"><i class="fas fa-store"></i> Ver Tienda</a>
            <!-- BUG FIX: href="#" para que onclick maneje el logout sin navegar a la API -->
            <a href="#" onclick="logout(event)"><i class="fas fa-sign-out-alt"></i> Salir</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-header-top">
            <div>
                <h1 style="font-family:var(--font-serif);font-size:1.6rem;font-weight:300">
                    Bienvenido, <?= htmlspecialchars($_SESSION['user_name']) ?>
                </h1>
                <p style="font-size:.72rem;color:var(--gray);margin-top:4px">
                    <?= date('l, d \d\e F Y') ?>
                </p>
            </div>
            <div style="display:flex;gap:12px">
                <a href="orders.php" class="btn btn-outline" style="font-size:.65rem;padding:10px 20px">
                    <i class="fas fa-bell"></i> <?= $pendingOrders ?> pendientes
                </a>
                <a href="products.php?action=add" class="btn btn-gold" style="font-size:.65rem;padding:10px 20px">
                    <i class="fas fa-plus"></i> Nuevo Producto
                </a>
            </div>
        </div>

        <!-- STAT CARDS -->
        <div class="stat-cards">
            <div class="stat-card revenue-card">
                <div class="stat-value">$<?= number_format($totalRevenue, 0) ?></div>
                <div class="stat-label"><i class="fas fa-dollar-sign"></i> Ingresos Totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $totalOrders ?></div>
                <div class="stat-label"><i class="fas fa-receipt"></i> Total Pedidos</div>
            </div>
            <div class="stat-card"
                style="border-bottom:2px solid <?= $pendingOrders > 0 ? '#ffc800' : 'var(--gold)' ?>">
                <div class="stat-value"
                    style="color:<?= $pendingOrders > 0 ? '#ffc800' : 'var(--white)' ?>">
                    <?= $pendingOrders ?>
                </div>
                <div class="stat-label"><i class="fas fa-clock"></i> Pedidos Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $totalUsers ?></div>
                <div class="stat-label"><i class="fas fa-users"></i> Clientes</div>
            </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="quick-actions">
            <a href="products.php" class="btn btn-dark">
                <i class="fas fa-tshirt"></i> Gestionar Productos (<?= $totalProducts ?>)
            </a>
            <a href="orders.php?status=pending" class="btn btn-dark">
                <i class="fas fa-clock"></i> Ver Pendientes
            </a>
            <a href="settings.php" class="btn btn-dark">
                <i class="fab fa-telegram"></i> Config Telegram
            </a>
        </div>

        <!-- RECENT ORDERS -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
            <h2 style="font-family:var(--font-serif);font-size:1.4rem;font-weight:300">Últimos Pedidos</h2>
            <a href="orders.php"
               style="font-size:.7rem;color:var(--gold);letter-spacing:.2em;text-decoration:none;text-transform:uppercase">
                Ver todos <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Pedido</th><th>Cliente</th><th>Tipo</th><th>Total</th>
                    <th>Estado</th><th>Telegram</th><th>Fecha</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentOrders)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;color:var(--gray);padding:40px">
                        No hay pedidos aún
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($recentOrders as $order): ?>
                <tr>
                    <td><strong style="color:var(--gold)"><?= htmlspecialchars($order['order_number']) ?></strong></td>
                    <td>
                        <div style="font-size:.85rem">
                            <?= htmlspecialchars($order['customer_name'] ?? $order['shipping_name']) ?>
                        </div>
                        <div style="font-size:.7rem;color:var(--gray)">
                            <?= htmlspecialchars($order['shipping_phone'] ?? '') ?>
                        </div>
                    </td>
                    <td>
                        <span style="font-size:.65rem;letter-spacing:.15em;text-transform:uppercase;
                            color:<?= $order['order_type'] === 'wholesale' ? 'var(--gold)' : 'var(--gray-light)' ?>">
                            <?= $order['order_type'] === 'wholesale' ? '🏭 Mayoreo' : '🛍 Individual' ?>
                        </span>
                    </td>
                    <td style="font-weight:500;color:var(--gold)">$<?= number_format($order['total'], 2) ?></td>
                    <td>
                        <span class="status-badge status-<?= $order['status'] ?>">
                            <?= ucfirst($order['status']) ?>
                        </span>
                    </td>
                    <td style="text-align:center">
                        <?= $order['telegram_sent']
                            ? '<span style="color:#4acc80"><i class="fas fa-check"></i></span>'
                            : '<span style="color:var(--gray)"><i class="fas fa-minus"></i></span>' ?>
                    </td>
                    <td style="font-size:.78rem;color:var(--gray)">
                        <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                    </td>
                    <td>
                        <a href="orders.php?view=<?= $order['id'] ?>"
                           class="btn btn-dark" style="font-size:.6rem;padding:6px 14px">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </main>
</div>

<script src="../js/main.js"></script>
<script>
async function logout(e) {
    e.preventDefault();
    const res  = await fetch('../api/auth.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ action: 'logout' })
    });
    const data = await res.json();
    window.location.href = data.redirect;
}
</script>
</body>
</html>
