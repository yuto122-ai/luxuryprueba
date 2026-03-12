<?php
require_once '../php/config.php';
requireAdmin();
$db = getDB();

$message = '';
$action  = $_GET['action'] ?? 'list';

// ── FORM SUBMISSIONS ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'save_product') {
        $id              = (int)($_POST['id'] ?? 0);
        $name            = sanitize($_POST['name']);
        $slug            = strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($name)));
        $description     = sanitize($_POST['description']);
        $material        = $_POST['material'];
        $saleType        = $_POST['sale_type'];
        $priceIndividual = !empty($_POST['price_individual']) ? (float)$_POST['price_individual'] : null;
        $priceWholesale  = !empty($_POST['price_wholesale'])  ? (float)$_POST['price_wholesale']  : null;
        $minWholesaleQty = (int)($_POST['min_wholesale_qty'] ?? 12);
        $stock           = (int)($_POST['stock'] ?? 0);
        $featured        = isset($_POST['featured']) ? 1 : 0;
        $categoryId      = (int)($_POST['category_id'] ?? 1);
        $selectedSizes   = isset($_POST['sizes']) ? (array)$_POST['sizes'] : [];

        $maxPrice = 10000;
        $error    = false;

        if ($priceIndividual !== null && $priceIndividual > $maxPrice) {
            $message = ['type' => 'error', 'text' => 'El precio individual no puede ser mayor a $10,000'];
            $action  = $id ? 'edit' : 'add';
            $error   = true;
        }
        // BUG 15 FIX: validación de mayoreo deduplicada — solo una vez
        if (!$error && $priceWholesale !== null && $priceWholesale > $maxPrice) {
            $message = ['type' => 'error', 'text' => 'El precio mayoreo no puede ser mayor a $10,000'];
            $action  = $id ? 'edit' : 'add';
            $error   = true;
        }

        if (!$error) {
            if ($id) {
                $db->prepare("UPDATE products SET name=?,slug=?,description=?,material=?,category_id=?,
                    sale_type=?,price_individual=?,price_wholesale=?,min_wholesale_qty=?,stock=?,featured=?
                    WHERE id=?")
                   ->execute([$name,$slug,$description,$material,$categoryId,$saleType,
                              $priceIndividual,$priceWholesale,$minWholesaleQty,$stock,$featured,$id]);

                $db->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$id]);
                foreach ($selectedSizes as $sz) {
                    if (in_array($sz, ['XS','S','M','L','XL','XXL','XXXL'])) {
                        $db->prepare("INSERT INTO product_variants (product_id, size, stock) VALUES (?,?,?)")
                           ->execute([$id, $sz, 0]);
                    }
                }
                $message = ['type' => 'success', 'text' => 'Producto actualizado correctamente'];

            } else {
                $db->prepare("INSERT INTO products (name,slug,description,material,category_id,sale_type,
                    price_individual,price_wholesale,min_wholesale_qty,stock,featured) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                   ->execute([$name,$slug,$description,$material,$categoryId,$saleType,
                              $priceIndividual,$priceWholesale,$minWholesaleQty,$stock,$featured]);
                $newId = $db->lastInsertId();

                foreach ($selectedSizes as $sz) {
                    if (in_array($sz, ['XS','S','M','L','XL','XXL','XXXL'])) {
                        $db->prepare("INSERT INTO product_variants (product_id, size, stock) VALUES (?,?,?)")
                           ->execute([$newId, $sz, 0]);
                    }
                }

                if (!empty($_FILES['images']['name'][0])) {
                    $uploadDir = '../uploads/products/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $isMain = true;
                    foreach ($_FILES['images']['tmp_name'] as $i => $tmpName) {
                        if (!$tmpName) continue;
                        $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
                        if (!in_array($ext, ['jpg','jpeg','png','webp'])) continue;
                        $filename = 'product_' . $newId . '_' . time() . '_' . $i . '.' . $ext;
                        move_uploaded_file($tmpName, $uploadDir . $filename);
                        $db->prepare("INSERT INTO product_images (product_id,image_path,is_main,sort_order) VALUES (?,?,?,?)")
                           ->execute([$newId, $filename, $isMain ? 1 : 0, $i]);
                        $isMain = false;
                    }
                }
                $message = ['type' => 'success', 'text' => 'Producto creado correctamente'];
            }
            $action = 'list';
        }
    }

    if ($postAction === 'delete') {
        $id = (int)$_POST['id'];
        $db->prepare("UPDATE products SET active = 0 WHERE id = ?")->execute([$id]);
        $message = ['type' => 'success', 'text' => 'Producto eliminado'];
        $action  = 'list';
    }
}

// ── GET PRODUCT FOR EDIT ──────────────────────────────────────────────────────
$editProduct = null;
$editSizes   = [];
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $editProduct = $stmt->fetch();
    if ($editProduct) {
        $vstmt = $db->prepare("SELECT size FROM product_variants WHERE product_id = ?
            ORDER BY FIELD(size,'XS','S','M','L','XL','XXL','XXXL')");
        $vstmt->execute([$editProduct['id']]);
        $editSizes = $vstmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

$products = $db->query("
    SELECT p.*, c.name as category_name,
        (SELECT image_path FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image,
        (SELECT GROUP_CONCAT(size ORDER BY FIELD(size,'XS','S','M','L','XL','XXL','XXXL') SEPARATOR ',')
         FROM product_variants WHERE product_id = p.id) as sizes
    FROM products p LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.active = 1
    ORDER BY p.featured DESC, p.id DESC
")->fetchAll();

$categories = $db->query("SELECT * FROM categories ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos — Admin BLACK CLOTHES</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-form { background:var(--dark2); border:1px solid rgba(255,255,255,.05); padding:40px; max-width:860px; }
        .form-grid    { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .form-grid-3  { display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px; }
        .toggle-switch { display:flex; align-items:center; gap:12px; cursor:pointer; }
        .product-thumb { width:60px; height:75px; object-fit:cover; background:var(--dark3); }
        select.form-input { cursor:pointer; }
        .upload-zone {
            border:2px dashed rgba(201,168,76,.3); padding:40px; text-align:center;
            cursor:pointer; transition:var(--transition); background:rgba(201,168,76,.02);
        }
        .upload-zone:hover { border-color:var(--gold); background:rgba(201,168,76,.05); }
        .upload-zone input[type="file"] { display:none; }
        /* Tallas */
        .sizes-grid { display:flex; flex-wrap:wrap; gap:10px; margin-top:8px; }
        .size-btn {
            width:48px; height:48px; border:1px solid rgba(255,255,255,.12);
            background:transparent; color:var(--gray-light); font-size:.75rem;
            letter-spacing:.1em; cursor:pointer; transition:var(--transition);
            display:flex; align-items:center; justify-content:center;
            user-select:none;
        }
        .size-btn:hover  { border-color:var(--gold); color:var(--gold); }
        .size-btn.active { border-color:var(--gold); background:rgba(201,168,76,.12); color:var(--gold); }
        /* Precios dinámicos */
        .price-section { transition:opacity .2s; }
        .price-section.hidden { opacity:.3; pointer-events:none; }
        /* Size tags en lista */
        .size-tag {
            display:inline-block; padding:2px 8px; font-size:.62rem;
            border:1px solid rgba(201,168,76,.25); color:var(--gold);
            letter-spacing:.1em; margin:1px;
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
            <a href="index.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="orders.php"><i class="fas fa-receipt"></i> Pedidos</a>
            <a href="products.php" class="active"><i class="fas fa-tshirt"></i> Productos</a>
            <a href="users.php"><i class="fas fa-users"></i> Clientes</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Configuración</a>
            <hr style="border-color:rgba(255,255,255,.05);margin:20px 0">
            <a href="../index.php"><i class="fas fa-store"></i> Ver Tienda</a>
            <a href="#" onclick="logout(event)"><i class="fas fa-sign-out-alt"></i> Salir</a>
        </nav>
    </aside>

    <main class="admin-main">
        <?php if ($message): ?>
        <div class="alert alert-<?= $message['type'] ?>" style="margin-bottom:20px"><?= $message['text'] ?></div>
        <?php endif; ?>

        <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- ── FORMULARIO ── -->
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:30px">
            <a href="products.php" class="btn btn-dark" style="font-size:.65rem;padding:8px 16px">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <h1 style="font-family:var(--font-serif);font-size:1.8rem;font-weight:300">
                <?= $action === 'edit' ? 'Editar Producto' : 'Nuevo Producto' ?>
            </h1>
        </div>

        <form method="POST" enctype="multipart/form-data" class="product-form">
            <input type="hidden" name="action" value="save_product">
            <?php if ($editProduct): ?>
            <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">
            <?php endif; ?>

            <div class="form-grid" style="margin-bottom:20px">
                <div class="form-group">
                    <label class="form-label">Nombre del producto *</label>
                    <input type="text" name="name" class="form-input" required
                        value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Categoría</label>
                    <select name="category_id" class="form-input">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"
                            <?= ($editProduct['category_id'] ?? 1) == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:20px">
                <label class="form-label">Descripción</label>
                <textarea name="description" class="form-input" rows="3"><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
            </div>

            <div class="form-grid-3" style="margin-bottom:20px">
                <div class="form-group">
                    <label class="form-label">Material *</label>
                    <select name="material" class="form-input">
                        <option value="cotton"    <?= ($editProduct['material'] ?? '') === 'cotton'    ? 'selected' : '' ?>>Algodón</option>
                        <option value="polyester" <?= ($editProduct['material'] ?? '') === 'polyester' ? 'selected' : '' ?>>Poliéster</option>
                        <option value="mixed"     <?= ($editProduct['material'] ?? '') === 'mixed'     ? 'selected' : '' ?>>Mixto</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo de Venta</label>
                    <select name="sale_type" class="form-input" id="sale_type_select"
                        onchange="togglePrices(this.value)">
                        <option value="both"       <?= ($editProduct['sale_type'] ?? 'both') === 'both'       ? 'selected' : '' ?>>Individual + Mayoreo</option>
                        <option value="individual" <?= ($editProduct['sale_type'] ?? '')     === 'individual' ? 'selected' : '' ?>>Solo Individual</option>
                        <option value="wholesale"  <?= ($editProduct['sale_type'] ?? '')     === 'wholesale'  ? 'selected' : '' ?>>Solo Mayoreo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Stock Total</label>
                    <input type="number" name="stock" class="form-input" min="0"
                        value="<?= $editProduct['stock'] ?? 0 ?>">
                </div>
            </div>

            <div class="form-grid-3" style="margin-bottom:20px">
                <div class="form-group price-section" id="price_individual_wrap">
                    <label class="form-label">Precio Individual ($)</label>
                    <input type="number" name="price_individual" class="form-input" step="0.01" min="0"
                        value="<?= $editProduct['price_individual'] ?? '' ?>">
                </div>
                <div class="form-group price-section" id="price_wholesale_wrap">
                    <label class="form-label">Precio Mayoreo ($)</label>
                    <input type="number" name="price_wholesale" class="form-input" step="0.01" min="0"
                        value="<?= $editProduct['price_wholesale'] ?? '' ?>">
                </div>
                <div class="form-group price-section" id="min_wholesale_wrap">
                    <label class="form-label">Mín. Pzas. Mayoreo</label>
                    <input type="number" name="min_wholesale_qty" class="form-input" min="1"
                        value="<?= $editProduct['min_wholesale_qty'] ?? 12 ?>">
                </div>
            </div>

            <!-- TALLAS -->
            <!-- BUG 14 FIX: div.size-btn con input hidden + toggleSizeBtn() — sin label ni onclick en span -->
            <div class="form-group" style="margin-bottom:24px">
                <label class="form-label">Tallas Disponibles</label>
                <?php $allSizes = ['XS','S','M','L','XL','XXL','XXXL']; ?>
                <div class="sizes-grid">
                    <?php foreach ($allSizes as $sz):
                        $isActive = in_array($sz, $editSizes); ?>
                    <div class="size-btn <?= $isActive ? 'active' : '' ?>"
                         onclick="toggleSizeBtn(this)">
                        <input type="checkbox" name="sizes[]" value="<?= $sz ?>"
                            <?= $isActive ? 'checked' : '' ?> style="display:none">
                        <?= $sz ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p style="font-size:.68rem;color:var(--gray);margin-top:10px">
                    <i class="fas fa-info-circle" style="color:var(--gold)"></i>
                    Selecciona las tallas disponibles. Si no aplica, deja vacío.
                </p>
            </div>

            <div style="margin-bottom:24px">
                <label class="toggle-switch">
                    <input type="checkbox" name="featured" value="1"
                        <?= ($editProduct['featured'] ?? 0) ? 'checked' : '' ?>
                        style="width:18px;height:18px;accent-color:var(--gold)">
                    <span style="font-size:.75rem;letter-spacing:.15em;text-transform:uppercase">
                        Producto Destacado
                    </span>
                </label>
            </div>

            <?php if (!$editProduct): ?>
            <div class="form-group" style="margin-bottom:24px">
                <label class="form-label">Imágenes del Producto (múltiples para vista 360°)</label>
                <div class="upload-zone" onclick="document.getElementById('img-upload').click()">
                    <i class="fas fa-cloud-upload-alt" style="font-size:2rem;color:var(--gold);display:block;margin-bottom:12px"></i>
                    <p style="font-size:.82rem">Haz clic para seleccionar imágenes</p>
                    <p style="font-size:.72rem;color:var(--gray);margin-top:6px">
                        Para vista 360°, sube múltiples ángulos en orden (0°, 45°, 90°…)
                    </p>
                    <p id="files-selected" style="color:var(--gold);font-size:.78rem;margin-top:10px"></p>
                    <input type="file" id="img-upload" name="images[]"
                        accept="image/*" multiple onchange="showSelected(this)">
                </div>
            </div>
            <?php endif; ?>

            <div style="display:flex;gap:16px">
                <button type="submit" class="btn btn-gold">
                    <i class="fas fa-save"></i> Guardar Producto
                </button>
                <a href="products.php" class="btn btn-outline">Cancelar</a>
            </div>
        </form>

        <?php else: ?>
        <!-- ── LISTA ── -->
        <div class="admin-header">
            <h1>Productos</h1>
            <a href="products.php?action=add" class="btn btn-gold">
                <i class="fas fa-plus"></i> Nuevo Producto
            </a>
        </div>

        <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Imagen</th><th>Nombre</th><th>Material</th><th>Tipo Venta</th>
                    <th>Tallas</th><th>Precio Ind.</th><th>Precio May.</th>
                    <th>Stock</th><th>Destacado</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                <tr><td colspan="10" style="text-align:center;color:var(--gray);padding:40px">No hay productos</td></tr>
                <?php else: ?>
                <?php foreach ($products as $prod): ?>
                <tr>
                    <td>
                        <?php if ($prod['main_image']): ?>
                        <img src="../uploads/products/<?= htmlspecialchars($prod['main_image']) ?>"
                            class="product-thumb" alt="">
                        <?php else: ?>
                        <div class="product-thumb" style="display:flex;align-items:center;justify-content:center;color:var(--gray)">
                            <i class="fas fa-tshirt"></i>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($prod['name']) ?></strong>
                        <div style="font-size:.72rem;color:var(--gray)"><?= htmlspecialchars($prod['category_name'] ?? '') ?></div>
                    </td>
                    <td><span style="color:var(--gold)">
                        <?= $prod['material'] === 'cotton' ? 'Algodón' : ($prod['material'] === 'polyester' ? 'Poliéster' : 'Mixto') ?>
                    </span></td>
                    <td style="font-size:.75rem;color:var(--gray)"><?= ucfirst($prod['sale_type']) ?></td>
                    <td>
                        <?php if (!empty($prod['sizes'])): ?>
                            <?php foreach (explode(',', $prod['sizes']) as $sz): ?>
                            <span class="size-tag"><?= trim($sz) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span style="color:var(--gray);font-size:.72rem">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $prod['price_individual'] ? '$' . number_format($prod['price_individual'], 2) : '—' ?></td>
                    <td><?= $prod['price_wholesale']  ? '$' . number_format($prod['price_wholesale'],  2) : '—' ?></td>
                    <td><?= $prod['stock'] ?></td>
                    <td style="text-align:center">
                        <?= $prod['featured'] ? '<span style="color:var(--gold)">★</span>' : '—' ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:8px">
                            <a href="?action=edit&id=<?= $prod['id'] ?>"
                               class="btn btn-dark" style="font-size:.6rem;padding:6px 12px">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" style="display:inline"
                                onsubmit="return confirm('¿Eliminar este producto?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $prod['id'] ?>">
                                <button type="submit" class="btn btn-dark"
                                    style="font-size:.6rem;padding:6px 12px;border-color:rgba(204,51,51,.3);color:#ff6666">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
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
<script>
// BUG 14 FIX: toggle limpio con div.size-btn — un solo click, sin doble cancelación
function toggleSizeBtn(div) {
    div.classList.toggle('active');
    const cb = div.querySelector('input[type="checkbox"]');
    if (cb) cb.checked = div.classList.contains('active');
}

function togglePrices(type) {
    const ind = document.getElementById('price_individual_wrap');
    const may = document.getElementById('price_wholesale_wrap');
    const min = document.getElementById('min_wholesale_wrap');
    if (!ind) return;
    ind.classList.toggle('hidden', type === 'wholesale');
    may.classList.toggle('hidden', type === 'individual');
    min.classList.toggle('hidden', type === 'individual');
}
(function () {
    const sel = document.getElementById('sale_type_select');
    if (sel) togglePrices(sel.value);
})();

function showSelected(input) {
    const el = document.getElementById('files-selected');
    if (el) el.textContent = input.files.length + ' archivo(s) seleccionado(s)';
}

async function logout(e) {
    e.preventDefault();
    const r = await fetch('../api/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'logout' })
    });
    const d = await r.json();
    window.location.href = d.redirect;
}
</script>
</body>
</html>
