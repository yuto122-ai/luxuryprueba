<?php
require_once '../php/config.php';
requireAdmin();
$db = getDB();

$users = $db->query("
    SELECT u.*, 
        COUNT(o.id) as order_count,
        COALESCE(SUM(o.total),0) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id AND o.status != 'cancelled'
    WHERE u.role = 'customer'
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes — Admin BLACK CLOTHES</title>
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
            <a href="users.php" class="active"><i class="fas fa-users"></i> Clientes</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Configuración</a>
            <hr style="border-color:rgba(255,255,255,.05);margin:20px 0">
            <a href="../index.php"><i class="fas fa-store"></i> Ver Tienda</a>
            <a href="#" onclick="logout(event)"><i class="fas fa-sign-out-alt"></i> Salir</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <h1>Clientes Registrados</h1>
            <span style="color:var(--gray);font-size:.8rem"><?= count($users) ?> clientes</span>
        </div>

        <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Pedidos</th>
                    <th>Total Gastado</th>
                    <th>Registrado</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($users)): ?>
                <tr><td colspan="7" style="text-align:center;color:var(--gray);padding:40px">No hay clientes registrados</td></tr>
                <?php else: ?>
                <?php foreach($users as $u): ?>
                <tr>
                    <td style="color:var(--gray)"><?= $u['id'] ?></td>
                    <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                    <td style="color:var(--gray)"><?= htmlspecialchars($u['email']) ?></td>
                    <td style="color:var(--gray)"><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                    <td style="text-align:center">
                        <a href="orders.php" style="color:var(--gold);text-decoration:none"><?= $u['order_count'] ?></a>
                    </td>
                    <td style="color:var(--gold);font-weight:500">$<?= number_format($u['total_spent'], 2) ?></td>
                    <td style="font-size:.78rem;color:var(--gray)"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </main>
</div>
<script src="../js/main.js"></script>
<script>async function logout(e){e.preventDefault();const r=await fetch('../api/auth.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'logout'})});const d=await r.json();window.location.href=d.redirect;}</script>
</body>
</html>
