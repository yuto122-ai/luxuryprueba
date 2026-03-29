<?php 
require_once 'php/config.php';

$cssVersion = @filemtime(__DIR__ . '/css/style.css') ?: time();
$jsVersion  = @filemtime(__DIR__ . '/js/main.js') ?: time();

$db = getDB();
$allProds = $db->query("
    SELECT p.id, p.name, p.description, p.material, p.price_individual, p.price_wholesale, 
           p.min_wholesale_qty, p.sale_type,
           (SELECT image_path FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image,
           (SELECT GROUP_CONCAT(image_path ORDER BY sort_order SEPARATOR '|') FROM product_images WHERE product_id = p.id) as all_images
    FROM products p WHERE p.active = 1
")->fetchAll(PDO::FETCH_ASSOC);

$allVariants = $db->query("
SELECT id, product_id, size, price
FROM product_variants
ORDER BY FIELD(size,'XS','S','M','L','XL','XXL','XXXL')
")->fetchAll(PDO::FETCH_ASSOC);

$allColors = $db->query("SELECT c.id,c.name,c.hex,c.extra_price,c.image_path FROM colors c WHERE c.active=1 ORDER BY c.id")->fetchAll(PDO::FETCH_ASSOC);
$pcStmt = $db->prepare("SELECT pc.color_id, pc.extra_price, pc.image_path, c.name, c.hex
    FROM product_colors pc
    JOIN (
        SELECT color_id, MAX(id) AS max_id
        FROM product_colors
        WHERE product_id = ?
        GROUP BY color_id
    ) latest ON latest.max_id = pc.id
    JOIN colors c ON pc.color_id = c.id
    WHERE c.active = 1
    ORDER BY c.id");

$variantMap = [];

foreach ($allVariants as $v) {
    $variantMap[$v['product_id']][] = [
        'id' => $v['id'],
        'size' => $v['size'],
        'price' => (float)($v['price'] ?? 0)
    ];
}

$productsMap = [];

foreach ($allProds as $p) {
    $p['sizes'] = $variantMap[$p['id']] ?? [];

    $pcStmt->execute([$p['id']]);
    $prodColors = $pcStmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($prodColors)) {
        $prodColors = $allColors;
    }
    $p['colors'] = $prodColors;

    $productsMap[$p['id']] = $p;
}

$colors = $allColors;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BLACK CLOTHES</title>
    <link rel="stylesheet" href="css/style.css?v=<?= $cssVersion ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .material-tabs{display:flex;gap:16px;margin-bottom:40px;flex-wrap:wrap}
        .material-tab{padding:10px 28px;background:none;border:1px solid rgba(255,255,255,.1);color:var(--gray);font-family:var(--font-sans);font-size:.68rem;letter-spacing:.2em;text-transform:uppercase;cursor:pointer;transition:var(--transition)}
        .material-tab.active,.material-tab:hover{border-color:var(--gold);color:var(--gold);background:rgba(201,168,76,.06)}
        .wholesale-band{background:linear-gradient(135deg,#0a0a0a 0%,#111 50%,#0a0a0a 100%);border-top:1px solid rgba(201,168,76,.2);border-bottom:1px solid rgba(201,168,76,.2);padding:80px 60px;margin:0}
        .wholesale-pack-card{background:var(--dark2);border:1px solid rgba(201,168,76,.15);padding:40px;position:relative;overflow:hidden;transition:var(--transition)}
        .wholesale-pack-card:hover{border-color:rgba(201,168,76,.5);box-shadow:var(--shadow-gold)}
        .wholesale-pack-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--gold),transparent)}
        .pack-material-icon{width:50px;height:50px;border:1px solid rgba(201,168,76,.3);display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:20px;color:var(--gold)}
        .pack-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:24px}
        .min-qty-badge{display:inline-block;background:rgba(201,168,76,.1);border:1px solid rgba(201,168,76,.3);color:var(--gold);padding:4px 12px;font-size:.62rem;letter-spacing:.2em;text-transform:uppercase;margin-bottom:16px}
        .price-per-unit{font-family:var(--font-serif);font-size:2.2rem;color:var(--white)}
        .price-per-unit-label{font-size:.62rem;color:var(--gray);letter-spacing:.2em;text-transform:uppercase}
        .hero-scroll-hint{position:absolute;bottom:40px;left:50%;transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;gap:8px;color:var(--gray);font-size:.62rem;letter-spacing:.3em;text-transform:uppercase;animation:bounce 2s ease-in-out infinite;z-index:2}
        @keyframes bounce{0%,100%{transform:translateX(-50%) translateY(0)}50%{transform:translateX(-50%) translateY(8px)}}
        .floating-particle{position:absolute;width:2px;height:2px;background:var(--gold);border-radius:50%;opacity:0;animation:particle 8s ease-in-out infinite}
        @keyframes particle{0%{opacity:0;transform:translateY(0) scale(0)}10%{opacity:1}90%{opacity:.5}100%{opacity:0;transform:translateY(-100vh) scale(1)}}

        /* ===== FIX TALLAS: z-index + pointer-events + cursor:pointer ===== */
        .viewer-sizes{display:flex;flex-wrap:wrap;gap:8px;margin:8px 0 4px;position:relative;z-index:10002}
        .viewer-size-btn{
            padding:9px 16px;border:1px solid rgba(255,255,255,.18);
            background:transparent;color:#bbb;font-size:.72rem;letter-spacing:.12em;
            cursor:pointer;          /* FIX: NO cursor:none */
            transition:all .18s;margin:0 4px;
            position:relative;z-index:10002;
            pointer-events:auto;     /* FIX: forzar recibir clicks */
            user-select:none
        }
        .viewer-size-btn:hover{border-color:var(--gold);color:var(--gold)}
        .viewer-size-btn.selected{border-color:var(--gold);color:var(--gold);background:rgba(201,168,76,.18)}
        .viewer-size-btn:disabled{opacity:.3;cursor:not-allowed;pointer-events:none}
        /* viewer-info sobre el cursor */
        .viewer-info{padding:30px 0;display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:start;position:relative;z-index:10001}
        .viewer-main-panel{display:flex;flex-direction:column;gap:12px;max-width:430px;width:100%}
        .viewer-side-panel{display:flex;flex-direction:column;gap:12px;align-items:flex-start}
        .viewer-section-label{font-size:.62rem;letter-spacing:.25em;text-transform:uppercase;color:var(--gray);margin-bottom:8px}
        .viewer-size-block{display:flex;flex-direction:column;align-items:flex-start;gap:2px}
        .viewer-size-block .viewer-sizes{justify-content:flex-start}
        .viewer-actions{display:flex;flex-direction:column;align-items:stretch;gap:8px;margin-top:2px}
        .viewer-actions .btn{width:min(100%,380px)!important}
        .viewer-360{align-items:flex-start;overflow-y:auto;padding:max(0px, env(safe-area-inset-top)) 20px calc(24px + max(env(safe-area-inset-bottom), 0px))}
        .viewer-container{margin:0 auto}
        .viewer-canvas{height:72vh;height:72dvh;max-height:none}
        .viewer-canvas .viewer-image{max-height:100%}
        @media (max-width: 900px) {
            .viewer-360{padding:max(0px, env(safe-area-inset-top)) 12px calc(16px + max(env(safe-area-inset-bottom), 0px))}
            .viewer-canvas{height:36vh;height:36dvh;min-height:210px}
            .viewer-info{grid-template-columns:1fr;gap:14px}
            .viewer-main-panel{order:1;gap:10px;max-width:100%}
            .viewer-side-panel{order:2;gap:8px;max-width:100%;padding-top:10px;border-top:1px solid rgba(255,255,255,.08)}
            .viewer-size-btn{min-width:52px;min-height:46px;font-size:.78rem;padding:10px 14px}
            .colors{gap:12px}
            .color-circle{width:40px;height:40px}
        }
        @media (max-width: 480px) {
            .viewer-canvas{height:34vh;height:34dvh;min-height:190px}
            .viewer-title{font-size:1.25rem}
            .viewer-desc{font-size:.76rem}
        }
        @media (max-height: 760px) and (max-width: 900px) {
            .viewer-canvas{height:32vh;height:32dvh;min-height:170px}
            .viewer-info{gap:10px}
            .viewer-actions{gap:6px}
        }
    </style>
    <script>
    const PRODUCTS_DATA = <?= json_encode($productsMap, JSON_HEX_TAG) ?>;
    </script>
</head>
<body>

<div class="page-loader"><div class="loader-text">BLACK <span>CLOTHES</span></div></div>

<header id="main-header">
    <nav>
        <button class="nav-toggle" id="mobile-nav-toggle" aria-label="Abrir menú">
            <i class="fas fa-bars"></i>
        </button>
        <a href="index" class="logo">BLACK <span>CLOTHES</span></a>
        <ul class="nav-links">
            <li><a href="#mayoreo">Mayoreo</a></li>
            <li><a href="#individual">Individual</a></li>
            <li><a href="catalogo.html">Catálogo</a></li>
            <?php if(isLoggedIn()): ?>
                <li><a href="checkout">Mis Pedidos</a></li>
                <?php if(isAdmin()): ?><li><a href="admin/" style="color:var(--gold)"><i class="fas fa-cog"></i> Admin</a></li><?php endif; ?>
            <?php endif; ?>
        </ul>
        <div class="nav-actions">
            <?php if(isLoggedIn()): ?>
                <a href="<?= isAdmin() ? 'admin/' : 'checkout' ?>" class="nav-icon"><i class="fas fa-user"></i></a>
                <button class="nav-icon" onclick="logout()"><i class="fas fa-sign-out-alt"></i></button>
            <?php else: ?>
                <a href="login" class="nav-icon"><i class="fas fa-user"></i></a>
            <?php endif; ?>
            <button class="nav-icon" data-open-cart style="position:relative">
                <i class="fas fa-shopping-bag"></i>
                <span class="cart-badge" style="display:none">0</span>
            </button>
        </div>
    </nav>
</header>

<div class="mobile-nav-overlay" id="mobile-nav-overlay"></div>
<aside class="mobile-nav-panel" id="mobile-nav-panel" aria-hidden="true">
    <div class="mobile-nav-header">
        <a href="index" class="logo">BLACK <span>CLOTHES</span></a>
        <button class="mobile-nav-close" id="mobile-nav-close" aria-label="Cerrar menú">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <ul class="mobile-nav-links">
        <li><a href="#mayoreo">Mayoreo</a></li>
        <li><a href="#individual">Individual</a></li>
        <li><a href="catalogo.html">Catálogo</a></li>
        <?php if(isLoggedIn()): ?>
            <li><a href="checkout">Mis Pedidos</a></li>
            <?php if(isAdmin()): ?><li><a href="admin/">Panel Admin</a></li><?php endif; ?>
            <li><a href="#" onclick="logout(); return false;">Cerrar sesión</a></li>
        <?php else: ?>
            <li><a href="login">Iniciar sesión</a></li>
        <?php endif; ?>
    </ul>
</aside>

<div class="cart-overlay" id="cart-overlay"></div>
<aside class="cart-sidebar" id="cart-sidebar">
    <div class="cart-header"><h3>Mi Carrito</h3><button class="cart-close" id="cart-close"><i class="fas fa-times"></i></button></div>
    <div class="cart-items" id="cart-items-container">
        <div class="cart-empty"><div class="cart-empty-icon">🛒</div><p style="font-size:.85rem;color:#888">Tu carrito está vacío</p></div>
    </div>
    <div class="cart-footer">
        <div class="cart-total"><span class="cart-total-label">Total</span><span class="cart-total-amount" id="cart-total-amount">$0.00</span></div>
        <a href="checkout" class="btn btn-gold" style="width:100%;justify-content:center">Proceder al Pago <i class="fas fa-arrow-right"></i></a>
        <div style="margin-top:12px;text-align:center;font-size:.68rem;color:var(--gray);letter-spacing:.1em"><i class="fas fa-truck" style="color:var(--gold)"></i>&nbsp;Envío gratis +$1,500</div>
    </div>
</aside>

<button class="mobile-cart-cta" data-open-cart>
    <i class="fas fa-shopping-bag"></i>
    Ver carrito
    <span class="cart-badge" style="display:none">0</span>
</button>

<button class="mobile-viewer-cta" id="mobile-viewer-cta" type="button">
    <i class="fas fa-shopping-bag"></i>
    Agregar al carrito
</button>

<!-- 360 VIEWER — id="viewer-container" necesario para stopPropagation -->
<div class="viewer-360" id="viewer-360">
    <div class="viewer-container" id="viewer-container">
        <div class="viewer-canvas" id="viewer-canvas">
            <img id="viewer-image" class="viewer-image" src="assets/placeholder.jpg" alt="Vista 360°">
        </div>
        <div class="viewer-info">
            <div class="viewer-main-panel">
                <p style="font-size:.62rem;letter-spacing:.3em;text-transform:uppercase;color:var(--gold);margin-bottom:8px" id="viewer-material">100% Algodón</p>
                <h2 class="viewer-title" id="viewer-title">Cargando...</h2>
                <p class="viewer-desc" id="viewer-desc"></p>
                <div style="margin-top:20px"><span style="font-family:var(--font-serif);font-size:2rem;color:var(--gold)" id="viewer-price">$0.00</span></div>
                <div class="viewer-size-block">
                    <p class="viewer-section-label" style="margin-bottom:14px">Selecciona Talla</p>
                <div class="viewer-sizes" id="viewer-sizes"></div>
                </div>
                <div class="viewer-actions">
                    <button id="viewer-add-cart" class="btn btn-gold" style="width:100%;justify-content:center">
                        <i class="fas fa-shopping-bag"></i> Añadir al Carrito
                    </button>
                    <button type="button" class="btn btn-outline" style="width:100%;justify-content:center" onclick="close360Viewer()">
                        <i class="fas fa-arrow-left"></i> Volver a tienda
                    </button>
                </div>
            </div>
            <div class="viewer-side-panel">
                <div>
                    <p class="viewer-section-label">Color</p>
                    <div class="colors" id="viewer-colors">
                        <?php foreach ($colors as $color): ?>
                        <div class="color-circle" data-extra="<?= $color['extra_price'] ?>" data-image="<?= $color['image_path'] ?>" style="background:<?= $color['hex'] ?>"></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="hero">
    <div class="hero-bg"></div><div class="hero-grid"></div>
    <?php for($i=0;$i<20;$i++): ?>
    <div class="floating-particle" style="left:<?= rand(0,100) ?>%;animation-delay:<?= rand(0,8) ?>s;animation-duration:<?= rand(6,12) ?>s"></div>
    <?php endfor; ?>
    <div class="hero-content">
        <span class="hero-tag">✦ La oscuridad hecha moda ✦</span>
        <h1 class="hero-title">BLACK<br><em>CLOTHES</em></h1>
        <p class="hero-sub">Playeras negras · Algodón & Poliéster · Mayoreo & Menudeo</p>
        <div class="hero-ctas">
            <a href="#mayoreo" class="btn btn-gold"><i class="fas fa-boxes"></i> Ver Mayoreo</a>
            <a href="#individual" class="btn btn-outline"><i class="fas fa-tshirt"></i> Tienda Individual</a>
        </div>
    </div>
    <div class="hero-scroll-hint"><span>Descubrir</span><i class="fas fa-chevron-down"></i></div>
</section>

<div style="background:var(--dark2);border-top:1px solid rgba(201,168,76,.1);border-bottom:1px solid rgba(201,168,76,.1)">
    <div class="home-stats-grid" style="max-width:1600px;margin:0 auto;display:grid;grid-template-columns:repeat(4,1fr);padding:0 60px">
        <?php foreach([['fa-boxes','Desde 12 piezas','Mayoreo accesible'],['fa-shipping-fast','Envío gratis','+$1,500 en compras'],['fa-tshirt','Algodón & Poliéster','Dos materiales premium'],['fa-shield-alt','Calidad garantizada','Satisfacción 100%']] as $stat): ?>
        <div class="home-stat-item" style="padding:28px 20px;display:flex;align-items:center;gap:16px;border-right:1px solid rgba(255,255,255,.04)">
            <i class="fas <?= $stat[0] ?>" style="font-size:1.4rem;color:var(--gold)"></i>
            <div><p style="font-size:.78rem;font-weight:500"><?= $stat[1] ?></p><p style="font-size:.68rem;color:var(--gray);letter-spacing:.1em"><?= $stat[2] ?></p></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="wholesale-band" id="mayoreo">
    <div style="max-width:1600px;margin:0 auto">
        <div class="section-header reveal">
            <span class="section-tag">✦ Compra en Grande ✦</span>
            <h2 class="section-title">Venta al <em style="font-style:italic;color:var(--gold)">Mayoreo</em></h2>
            <div class="section-divider"></div>
            <p style="color:var(--gray);font-size:.88rem;margin-top:20px;max-width:500px;margin-left:auto;margin-right:auto">Precios especiales para compras desde 12 piezas.</p>
        </div>
        <?php
        $stmt = $db->prepare("SELECT p.*,(SELECT image_path FROM product_images WHERE product_id=p.id AND is_main=1 LIMIT 1) as main_image FROM products p WHERE p.sale_type IN ('wholesale','both') AND p.active=1 ORDER BY p.featured DESC,p.id");
        $stmt->execute(); $wholesaleProducts = $stmt->fetchAll();
        ?>
        <div class="pack-grid">
            <?php foreach($wholesaleProducts as $prod): ?>
            <?php $img=$prod['main_image']?'uploads/products/'.$prod['main_image']:'assets/placeholder.jpg'; ?>
            <div class="wholesale-pack-card">
                <div class="pack-material-icon"><?= $prod['material']==='cotton'?'🌿':'⚡' ?></div>
                <div class="min-qty-badge">Mín. <?= $prod['min_wholesale_qty'] ?> piezas</div>
                <h3 style="font-family:var(--font-serif);font-size:1.4rem;margin-bottom:8px"><?= htmlspecialchars($prod['name']) ?></h3>
                <p style="font-size:.8rem;color:var(--gray);margin-bottom:20px"><?= htmlspecialchars(substr($prod['description']??'',0,100)) ?>...</p>
                <div class="price-per-unit-label">Precio por pieza</div>
                <div class="price-per-unit">$<?= number_format($prod['price_wholesale'],2) ?></div>
                <p style="font-size:.68rem;color:var(--gray);margin-top:4px">Total aprox: $<?= number_format($prod['price_wholesale']*$prod['min_wholesale_qty'],2) ?> / paquete</p>
                <div style="display:flex;gap:12px;margin-top:24px">
                    <button class="btn btn-gold" onclick="open360Viewer(<?= $prod['id'] ?>)" style="flex:1;justify-content:center;font-size:.65rem"><i class="fas fa-eye"></i> Ver producto</button>
                    <button class="btn btn-outline" onclick="addToCart(<?= $prod['id'] ?>,null,<?= $prod['min_wholesale_qty'] ?>)" style="flex:1;justify-content:center;font-size:.65rem"><i class="fas fa-boxes"></i> Pedir Mayoreo</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-top:60px" class="reveal">
            <?php foreach([['fa-percentage','Precios preferenciales','Hasta 40% más económico'],['fa-palette','Surtido de tallas','Sin costo extra'],['fa-handshake','Atención personalizada','Asesor dedicado'],['fa-redo','Reorden rápida','Repite con un clic']] as $feat): ?>
            <div class="wholesale-feature"><div class="wholesale-feature-icon"><i class="fas <?= $feat[0] ?>"></i></div><h4><?= $feat[1] ?></h4><p><?= $feat[2] ?></p></div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<section id="individual" style="background:var(--dark)">
    <div class="section-header reveal">
        <span class="section-tag">✦ Colección Completa ✦</span>
        <h2 class="section-title">Tienda <em style="font-style:italic;color:var(--gold)">Individual</em></h2>
        <div class="section-divider"></div>
    </div>
    <div class="material-tabs reveal" style="justify-content:center">
        <button class="material-tab active" data-filter="all">Todos</button>
        <button class="material-tab" data-filter="cotton">Algodón</button>
        <button class="material-tab" data-filter="polyester">Poliéster</button>
        <button class="material-tab" data-filter="featured">Destacados</button>
    </div>
    <?php
    $stmt=$db->prepare("SELECT p.*,(SELECT image_path FROM product_images WHERE product_id=p.id AND is_main=1 LIMIT 1) as main_image FROM products p WHERE p.sale_type IN ('individual','both') AND p.active=1 ORDER BY p.featured DESC,p.id");
    $stmt->execute(); $individualProducts=$stmt->fetchAll();
    ?>
    <div class="products-grid" id="products-grid">
        <?php foreach($individualProducts as $prod): ?>
        <?php $img=$prod['main_image']?'uploads/products/'.$prod['main_image']:'assets/placeholder.jpg'; ?>
        <div class="product-card" data-material="<?= $prod['material'] ?>" data-featured="<?= $prod['featured']?'true':'false' ?>">
            <div class="product-img-wrap">
                 <img src="<?= $img ?>" alt="<?= htmlspecialchars($prod['name']) ?>" loading="lazy" onclick="open360Viewer(<?= $prod['id'] ?>)" style="cursor:pointer"
                     onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22520%22%3E%3Crect width=%22100%25%22 height=%22100%25%22 fill=%22%23131313%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 fill=%22%23888888%22 font-family=%22Arial%22 font-size=%2220%22%3EImagen no disponible%3C/text%3E%3C/svg%3E';">
                <div class="product-badges">
                    <span class="badge badge-<?= $prod['material']==='cotton'?'cotton':'poly' ?>"><?= $prod['material']==='cotton'?'Algodón':'Poliéster' ?></span>
                    <?php if($prod['featured']): ?><span class="badge badge-sale">★ Destacado</span><?php endif; ?>
                </div>
                <div class="product-actions">
                    <button class="btn btn-gold" onclick="open360Viewer(<?= $prod['id'] ?>)" style="flex:1;justify-content:center;font-size:.62rem;padding:10px"><i class="fas fa-eye"></i> Ver producto</button>
                </div>
            </div>
            <div class="product-info">
                <p class="product-material"><?= $prod['material']==='cotton'?'100% Algodón':'100% Poliéster' ?></p>
                <h3 class="product-name"><?= htmlspecialchars($prod['name']) ?></h3>
                <div class="product-prices">
                    <?php if($prod['price_individual']): ?><span class="price-individual">$<?= number_format($prod['price_individual'],2) ?></span><?php endif; ?>
                    <?php if($prod['price_wholesale']&&$prod['sale_type']==='both'): ?><span class="price-wholesale">Mayoreo: $<?= number_format($prod['price_wholesale'],2) ?></span><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<div style="background:linear-gradient(135deg,#0a0a0a,#111,#0a0a0a);border-top:1px solid rgba(201,168,76,.1);border-bottom:1px solid rgba(201,168,76,.1);padding:80px 60px">
    <div style="max-width:1600px;margin:0 auto;text-align:center" class="reveal">
        <p style="font-family:var(--font-serif);font-size:clamp(1.5rem,3vw,2.5rem);font-weight:300;line-height:1.6;color:var(--cream)">"El negro no es solo un color.<br><em style="color:var(--gold)">Es una declaración.</em>"</p>
        <div class="section-divider" style="margin-top:30px"></div>
    </div>
</div>

<footer>
    <div class="footer-grid">
        <div class="footer-brand">
            <h3>BLACK <span>CLOTHES</span></h3>
            <p>Playeras negras de alta calidad en algodón y poliéster.</p>
            <div style="display:flex;gap:16px;margin-top:24px">
                <?php foreach(['fab fa-instagram','fab fa-facebook','fab fa-whatsapp','fab fa-tiktok'] as $icon): ?>
                <a href="https://www.instagram.com/luxury._clts?igsh=MTM2eDU3MG4wMHdzcg==" style="width:40px;height:40px;border:1px solid rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;color:var(--gray);font-size:.9rem;text-decoration:none;transition:var(--transition)" onmouseover="this.style.borderColor='var(--gold)';this.style.color='var(--gold)'" onmouseout="this.style.borderColor='rgba(255,255,255,.1)';this.style.color='var(--gray)'"><i class="<?= $icon ?>"></i></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="footer-col"><h4>Tienda</h4><ul><li><a href="#mayoreo">Venta Mayoreo</a></li><li><a href="#individual">Tienda Individual</a></li><li><a href="#individual">Catálogo</a></li><li><a href="login">Mi Cuenta</a></li></ul></div>
        <div class="footer-col"><h4>Productos</h4><ul><li><a href="#">Playeras Algodón</a></li><li><a href="#">Playeras Poliéster</a></li><li><a href="#">Packs Mayoreo</a></li><li><a href="#">Novedades</a></li></ul></div>
        <div class="footer-col"><h4>Contacto</h4><ul><li><a href="mailto:contacto@blackclothes.mx"><i class="fas fa-envelope" style="width:16px;color:var(--gold)"></i> contacto@blackclothes.mx</a></li><li><a href="https://wa.me/5216601751837" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp" style="width:16px;color:var(--gold)"></i> WhatsApp</a></li></ul></div>
    </div>
    <div class="footer-bottom">
        <p>© <?= date('Y') ?> Black Clothes. Todos los derechos reservados.</p>
        <p style="color:var(--gold);font-size:.68rem;letter-spacing:.2em">MADE IN MÉXICO 🖤</p>
    </div>
</footer>

<script src="js/main.js?v=<?= $jsVersion ?>"></script>
<script>
// ─────────────────────────────────────────────────────────────────────────────
// OVERRIDE de open360Viewer: main.js define su propia versión que sobreescribe
// la del HTML. Este bloque corre DESPUÉS de main.js y vuelve a sobreescribir
// con la versión correcta que usa PRODUCTS_DATA y stopPropagation.
// ─────────────────────────────────────────────────────────────────────────────
window.open360Viewer = function(productId) {
    var prod = PRODUCTS_DATA[productId];
    if (!prod) { console.error('Producto no encontrado:', productId); return; }
    var mobileViewerCta = document.getElementById('mobile-viewer-cta');

    document.getElementById('viewer-title').textContent    = prod.name;
    document.getElementById('viewer-desc').textContent     = prod.description ? prod.description.substring(0,120)+'…' : '';
    document.getElementById('viewer-material').textContent = prod.material==='cotton'?'100% Algodón':prod.material==='polyester'?'100% Poliéster':'Material Mixto';
    var basePrice = parseFloat(prod.price_individual || prod.price_wholesale || 0);

    var priceEl = document.getElementById('viewer-price');
    priceEl.dataset.base = basePrice;
    priceEl.textContent = '$' + basePrice.toFixed(2);

    // ===== PRECIO DINÁMICO =====
    let selectedSizePrice = null;
    let extraColor = 0;

    function updateViewerPrice() {
        let base = parseFloat(priceEl.dataset.base || 0);
        let sizeBase = selectedSizePrice !== null ? selectedSizePrice : base;
        let total = sizeBase + extraColor;
        priceEl.textContent = "$" + total.toFixed(2);
        syncMobileViewerCta();
    }

    function syncMobileViewerCta() {
        if (!mobileViewerCta) return;
        var requiresSize = prod.sizes && prod.sizes.length > 0;
        var hasSize = !!sizesEl.dataset.selected;
        if (requiresSize && !hasSize) {
            mobileViewerCta.innerHTML = '<i class="fas fa-ruler-combined"></i> Selecciona talla';
            return;
        }
        mobileViewerCta.innerHTML = '<i class="fas fa-shopping-bag"></i> Agregar · ' + priceEl.textContent;
    }

    var imgEl = document.getElementById('viewer-image');
    imgEl.onerror = function() {
        this.onerror = null;
        this.src = 'data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22900%22 height=%22640%22%3E%3Crect width=%22100%25%22 height=%22100%25%22 fill=%22%23111111%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 fill=%22%23888888%22 font-family=%22Arial%22 font-size=%2230%22%3EImagen no disponible%3C/text%3E%3C/svg%3E';
    };
    imgEl.src = prod.main_image ? 'uploads/products/'+prod.main_image : 'assets/placeholder.jpg';
    imgEl.alt = prod.name;

    // ── Botones de talla ──────────────────────────────────────────────────────
    var sizesEl = document.getElementById('viewer-sizes');
    sizesEl.innerHTML = '';
    delete sizesEl.dataset.selected;

    if (prod.sizes && prod.sizes.length > 0) {
        prod.sizes.forEach(function(sz) {
            var btn = document.createElement('button');
            btn.type      = 'button';
            btn.className = 'viewer-size-btn';
            btn.textContent = sz.size;

            // *** FIX PRINCIPAL ***
            // stopPropagation: evita que el click llegue al div#viewer-360
            // que tiene un listener que CIERRA el viewer al detectar clicks.
    btn.addEventListener('click', function(e) {
        e.stopPropagation();

        sizesEl.querySelectorAll('.viewer-size-btn').forEach(function(b){
        b.classList.remove('selected');
    });

        btn.classList.add('selected');
        sizesEl.dataset.selected = sz.id;

        // Usa precio real por talla desde DB si existe.
        var variantPrice = parseFloat(sz.price || 0);
        selectedSizePrice = variantPrice > 0 ? variantPrice : null;

        updateViewerPrice();
    });

            sizesEl.appendChild(btn);
        });
    } else {
        sizesEl.innerHTML = '<span style="font-size:.75rem;color:#888;letter-spacing:.1em">Talla única</span>';
        sizesEl.dataset.selected = 'unica';
    }
    syncMobileViewerCta();

    // ===== COLORES =====
    var colorsEl = document.getElementById('viewer-colors');
    function getColorImageSrc(imagePath) {
        if (!imagePath) return '';
        var p = String(imagePath).trim();
        if (!p) return '';
        if (p.startsWith('http://') || p.startsWith('https://') || p.startsWith('/')) {
            return p + (p.indexOf('?') === -1 ? '?v=' + Date.now() : '&v=' + Date.now());
        }
        if (p.startsWith('uploads/')) {
            return p + (p.indexOf('?') === -1 ? '?v=' + Date.now() : '&v=' + Date.now());
        }
        return 'uploads/product_colors/' + p + '?v=' + Date.now();
    }

    if (prod.colors && prod.colors.length > 0) {
        colorsEl.innerHTML = prod.colors.map(function(c){
            var border = (c.hex === '#ffffff' || c.hex.toLowerCase() === 'white') ? 'border:1px solid #000;' : '';
            return '<div class="color-circle" data-extra="'+ (parseFloat(c.extra_price||0).toFixed(2)) +'" data-image="'+ (c.image_path||'') +'" style="background:'+ c.hex +';'+ border +'"></div>';
        }).join('');
    }

    setTimeout(() => {
        var circles = document.querySelectorAll('#viewer-colors .color-circle');
        if (circles.length > 0) {
            circles.forEach(function(c){
                c.onclick = function(e) {
                    e.stopPropagation();
                    circles.forEach(function(x){ x.classList.remove('active'); });
                    this.classList.add('active');

                    extraColor = parseFloat(this.dataset.extra || 0);
                    updateViewerPrice();

                    var image = this.dataset.image;
                    if (image) {
                        var nextSrc = getColorImageSrc(image);
                        if (nextSrc) document.getElementById('viewer-image').src = nextSrc;
                    }
                };
            });

            // set first color selected
            circles[0].classList.add('active');
            extraColor = parseFloat(circles[0].dataset.extra || 0);
            updateViewerPrice();
            var firstImage = circles[0].dataset.image;
            if (firstImage) {
                var firstSrc = getColorImageSrc(firstImage);
                if (firstSrc) document.getElementById('viewer-image').src = firstSrc;
            }
        }
    }, 10);

    // ── Botón añadir al carrito ───────────────────────────────────────────────
    // Clonar para limpiar listeners anteriores
    var oldBtn = document.getElementById('viewer-add-cart');
    var addBtn = oldBtn.cloneNode(true);
    oldBtn.parentNode.replaceChild(addBtn, oldBtn);

    addBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        var sel = sizesEl.dataset.selected;
        if (prod.sizes && prod.sizes.length > 0 && !sel) {
            if (typeof showToast==='function') showToast('Selecciona una talla','error');
            else alert('Por favor selecciona una talla');
            return;
        }
        var qty = prod.sale_type==='wholesale' ? (prod.min_wholesale_qty||1) : 1;
        if (typeof addToCart==='function') addToCart(productId, parseInt(sel)||null, qty);
        window.close360Viewer();
    });

    // ── Mostrar viewer ────────────────────────────────────────────────────────
    var v360 = document.getElementById('viewer-360');
    v360.classList.add('active');
    document.body.classList.add('viewer-active');
    if (typeof window.__syncBodyScrollLock === 'function') {
        window.__syncBodyScrollLock();
    }
    if (typeof window.__pushUiState === 'function') {
        window.__pushUiState('viewer');
    }

};

// ── StopPropagation permanente en viewer-container ───────────────────────────
// Se registra UNA sola vez aquí — no dentro de open360Viewer.
// Esto evita que los clicks en tallas, imagen o botón cierren el viewer.
document.addEventListener('DOMContentLoaded', function() {
    var vContainer = document.getElementById('viewer-container');
    var v360 = document.getElementById('viewer-360');

    // Evita cerrar el viewer por click en el fondo.
    // Asi no hay cierres inesperados ni "salto hacia atras" al hacer click fuera del producto.
    if (v360) {
        v360.addEventListener('click', function(e) {
            if (e.target === v360) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);
    }

    if (vContainer) {
        vContainer.addEventListener('click', function(e) { e.stopPropagation(); });
    }
    // El cierre del viewer queda solo en el boton "✕ Cerrar".
});

// ── Filter tabs ───────────────────────────────────────────────────────────────
document.querySelectorAll('.material-tab').forEach(function(tab){
    tab.addEventListener('click', function(){
        document.querySelectorAll('.material-tab').forEach(function(t){t.classList.remove('active');});
        tab.classList.add('active');
        var filter = tab.dataset.filter;
        document.querySelectorAll('.product-card').forEach(function(card){
            var show = filter==='all'||card.dataset.material===filter||(filter==='featured'&&card.dataset.featured==='true');
            card.style.display=show?'block':'none';
            card.style.animation=show?'fadeInUp .4s ease both':'none';
        });
    });
});

async function logout(){
    var res  = await fetch('api/auth.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'logout'})});
    var data = await res.json();
    if(data.redirect) window.location.href=data.redirect;
}
</script>
</body>
</html>
