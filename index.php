<?php require_once 'php/config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BLACK CLOTHES — Playeras Negras Premium</title>
    <meta name="description" content="Black Clothes — Venta de playeras negras premium de algodón y poliéster. Venta al mayoreo e individual.">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .material-tabs { display:flex; gap:16px; margin-bottom:40px; flex-wrap:wrap; }
        .material-tab {
            padding:10px 28px; background:none; border:1px solid rgba(255,255,255,0.1);
            color:var(--gray); font-family:var(--font-sans); font-size:.68rem;
            letter-spacing:.2em; text-transform:uppercase; cursor:none; transition:var(--transition);
        }
        .material-tab.active, .material-tab:hover {
            border-color:var(--gold); color:var(--gold); background:rgba(201,168,76,.06);
        }
        .wholesale-band {
            background:linear-gradient(135deg, #0a0a0a 0%, #111 50%, #0a0a0a 100%);
            border-top:1px solid rgba(201,168,76,.2);
            border-bottom:1px solid rgba(201,168,76,.2);
            padding:80px 60px;
            margin:0;
        }
        .wholesale-pack-card {
            background:var(--dark2); border:1px solid rgba(201,168,76,.15);
            padding:40px; position:relative; overflow:hidden;
            transition:var(--transition);
        }
        .wholesale-pack-card:hover {
            border-color:rgba(201,168,76,.5);
            box-shadow:var(--shadow-gold);
        }
        .wholesale-pack-card::before {
            content:''; position:absolute; top:0; left:0; right:0; height:2px;
            background:linear-gradient(90deg, transparent, var(--gold), transparent);
        }
        .pack-material-icon {
            width:50px; height:50px; border:1px solid rgba(201,168,76,.3);
            display:flex; align-items:center; justify-content:center;
            font-size:1.4rem; margin-bottom:20px; color:var(--gold);
        }
        .pack-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:24px; }
        .min-qty-badge {
            display:inline-block; background:rgba(201,168,76,.1);
            border:1px solid rgba(201,168,76,.3);
            color:var(--gold); padding:4px 12px;
            font-size:.62rem; letter-spacing:.2em; text-transform:uppercase;
            margin-bottom:16px;
        }
        .price-per-unit { font-family:var(--font-serif); font-size:2.2rem; color:var(--white); }
        .price-per-unit-label { font-size:.62rem; color:var(--gray); letter-spacing:.2em; text-transform:uppercase; }
        .hero-scroll-hint {
            position:absolute; bottom:40px; left:50%; transform:translateX(-50%);
            display:flex; flex-direction:column; align-items:center; gap:8px;
            color:var(--gray); font-size:.62rem; letter-spacing:.3em; text-transform:uppercase;
            animation:bounce 2s ease-in-out infinite;
            z-index:2;
        }
        @keyframes bounce {
            0%,100% { transform:translateX(-50%) translateY(0); }
            50% { transform:translateX(-50%) translateY(8px); }
        }
        .floating-particle {
            position:absolute; width:2px; height:2px; background:var(--gold);
            border-radius:50%; opacity:0; animation:particle 8s ease-in-out infinite;
        }
        @keyframes particle {
            0% { opacity:0; transform:translateY(0) scale(0); }
            10% { opacity:1; }
            90% { opacity:0.5; }
            100% { opacity:0; transform:translateY(-100vh) scale(1); }
        }
    </style>
</head>
<body>

<!-- LOADER -->
<div class="page-loader">
    <div class="loader-text">BLACK <span>CLOTHES</span></div>
</div>

<!-- HEADER -->
<header id="main-header">
    <nav>
        <a href="index.php" class="logo">BLACK <span>CLOTHES</span></a>
        <ul class="nav-links">
            <li><a href="#mayoreo">Mayoreo</a></li>
            <li><a href="#individual">Individual</a></li>
            <li><a href="catalog.php">Catálogo</a></li>
            <?php if(isLoggedIn()): ?>
                <li><a href="orders.php">Mis Pedidos</a></li>
                <?php if(isAdmin()): ?>
                    <li><a href="admin/" style="color:var(--gold)"><i class="fas fa-cog"></i> Admin</a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
        <div class="nav-actions">
            <?php if(isLoggedIn()): ?>
                <a href="profile.php" class="nav-icon" title="Mi cuenta"><i class="fas fa-user"></i></a>
                <button class="nav-icon" onclick="logout()" title="Salir"><i class="fas fa-sign-out-alt"></i></button>
            <?php else: ?>
                <a href="login.php" class="nav-icon" title="Iniciar sesión"><i class="fas fa-user"></i></a>
            <?php endif; ?>
            <button class="nav-icon" data-open-cart title="Carrito" style="position:relative">
                <i class="fas fa-shopping-bag"></i>
                <span class="cart-badge" style="display:none">0</span>
            </button>
        </div>
    </nav>
</header>

<!-- CART SIDEBAR -->
<div class="cart-overlay" id="cart-overlay"></div>
<aside class="cart-sidebar" id="cart-sidebar">
    <div class="cart-header">
        <h3>Mi Carrito</h3>
        <button class="cart-close" id="cart-close"><i class="fas fa-times"></i></button>
    </div>
    <div class="cart-items" id="cart-items-container">
        <div class="cart-empty">
            <div class="cart-empty-icon">🛒</div>
            <p style="font-size:.85rem;color:#888">Tu carrito está vacío</p>
        </div>
    </div>
    <div class="cart-footer">
        <div class="cart-total">
            <span class="cart-total-label">Total</span>
            <span class="cart-total-amount" id="cart-total-amount">$0.00</span>
        </div>
        <a href="checkout.php" class="btn btn-gold" style="width:100%;justify-content:center">
            Proceder al Pago <i class="fas fa-arrow-right"></i>
        </a>
        <div style="margin-top:12px;text-align:center;font-size:.68rem;color:var(--gray);letter-spacing:.1em">
            <i class="fas fa-truck" style="color:var(--gold)"></i> &nbsp;Envío gratis en compras +$1,500
        </div>
    </div>
</aside>

<!-- 360 VIEWER -->
<div class="viewer-360" id="viewer-360">
    <div class="viewer-container">
        <button class="viewer-close" onclick="close360Viewer()">✕ Cerrar</button>
        <div class="viewer-canvas" id="viewer-canvas">
            <img id="viewer-image" class="viewer-image" src="assets/placeholder.jpg" alt="Vista 360°">
            <div class="viewer-hint"><i class="fas fa-arrows-left-right"></i> Arrastra para girar</div>
        </div>
        <div class="viewer-info">
            <div>
                <p style="font-size:.62rem;letter-spacing:.3em;text-transform:uppercase;color:var(--gold);margin-bottom:8px" id="viewer-material">100% Algodón</p>
                <h2 class="viewer-title" id="viewer-title">Cargando...</h2>
                <p class="viewer-desc" id="viewer-desc"></p>
                <div style="margin-top:20px">
                    <span style="font-family:var(--font-serif);font-size:2rem;color:var(--gold)" id="viewer-price">$0.00</span>
                </div>
            </div>
            <div>
                <p style="font-size:.62rem;letter-spacing:.25em;text-transform:uppercase;color:var(--gray);margin-bottom:14px">Selecciona Talla</p>
                <div class="viewer-sizes" id="viewer-sizes"></div>
                <button id="viewer-add-cart" class="btn btn-gold" style="width:100%;justify-content:center;margin-top:24px">
                    <i class="fas fa-shopping-bag"></i> Añadir al Carrito
                </button>
            </div>
        </div>
    </div>
</div>

<!-- HERO -->
<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-grid"></div>

    <!-- Floating particles -->
    <?php for($i=0;$i<20;$i++): ?>
    <div class="floating-particle" style="left:<?= rand(0,100) ?>%;animation-delay:<?= rand(0,8) ?>s;animation-duration:<?= rand(6,12) ?>s"></div>
    <?php endfor; ?>

    <div class="hero-content">
        <span class="hero-tag">✦ La oscuridad hecha moda ✦</span>
        <h1 class="hero-title">BLACK<br><em>CLOTHES</em></h1>
        <p class="hero-sub">Playeras negras · Algodón & Poliéster · Mayoreo & Menudeo</p>
        <div class="hero-ctas">
            <a href="#mayoreo" class="btn btn-gold">
                <i class="fas fa-boxes"></i> Ver Mayoreo
            </a>
            <a href="#individual" class="btn btn-outline">
                <i class="fas fa-tshirt"></i> Tienda Individual
            </a>
        </div>
    </div>

    <div class="hero-scroll-hint">
        <span>Descubrir</span>
        <i class="fas fa-chevron-down"></i>
    </div>
</section>

<!-- STATS BAR -->
<div style="background:var(--dark2);border-top:1px solid rgba(201,168,76,.1);border-bottom:1px solid rgba(201,168,76,.1)">
    <div style="max-width:1600px;margin:0 auto;display:grid;grid-template-columns:repeat(4,1fr);padding:0 60px">
        <?php foreach([
            ['fas fa-boxes','Desde 12 piezas','Mayoreo accesible'],
            ['fas fa-shipping-fast','Envío gratis','+$1,500 en compras'],
            ['fas fa-tshirt','Algodón & Poliéster','Dos materiales premium'],
            ['fas fa-shield-alt','Calidad garantizada','Satisfacción 100%']
        ] as $stat): ?>
        <div style="padding:28px 20px;display:flex;align-items:center;gap:16px;border-right:1px solid rgba(255,255,255,.04)">
            <i class="fas <?= $stat[0] ?>" style="font-size:1.4rem;color:var(--gold)"></i>
            <div>
                <p style="font-size:.78rem;font-weight:500"><?= $stat[1] ?></p>
                <p style="font-size:.68rem;color:var(--gray);letter-spacing:.1em"><?= $stat[2] ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- MAYOREO SECTION -->
<div class="wholesale-band" id="mayoreo">
    <div style="max-width:1600px;margin:0 auto">
        <div class="section-header reveal">
            <span class="section-tag">✦ Compra en Grande ✦</span>
            <h2 class="section-title">Venta al <em style="font-style:italic;color:var(--gold)">Mayoreo</em></h2>
            <div class="section-divider"></div>
            <p style="color:var(--gray);font-size:.88rem;margin-top:20px;max-width:500px;margin-left:auto;margin-right:auto">
                Precios especiales para compras desde 12 piezas. Ideal para negocios, uniformes y revendedores.
            </p>
        </div>

        <?php
        $db = getDB();
        $stmt = $db->prepare("SELECT p.*, 
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image
            FROM products p 
            WHERE p.sale_type IN ('wholesale','both') AND p.active = 1
            ORDER BY p.featured DESC, p.id");
        $stmt->execute();
        $wholesaleProducts = $stmt->fetchAll();
        ?>

        <div class="pack-grid reveal">
            <?php foreach($wholesaleProducts as $prod): ?>
            <?php $img = $prod['main_image'] ? 'uploads/products/'.$prod['main_image'] : 'assets/placeholder.jpg'; ?>
            <div class="wholesale-pack-card">
                <div class="pack-material-icon">
                    <?= $prod['material'] === 'cotton' ? '🌿' : '⚡' ?>
                </div>
                <div class="min-qty-badge">Mín. <?= $prod['min_wholesale_qty'] ?> piezas</div>
                <h3 style="font-family:var(--font-serif);font-size:1.4rem;margin-bottom:8px"><?= htmlspecialchars($prod['name']) ?></h3>
                <p style="font-size:.8rem;color:var(--gray);margin-bottom:20px"><?= htmlspecialchars(substr($prod['description'], 0, 100)) ?>...</p>
                <div class="price-per-unit-label">Precio por pieza</div>
                <div class="price-per-unit">$<?= number_format($prod['price_wholesale'], 2) ?></div>
                <p style="font-size:.68rem;color:var(--gray);margin-top:4px">
                    Total aprox: $<?= number_format($prod['price_wholesale'] * $prod['min_wholesale_qty'], 2) ?> / paquete
                </p>
                <div style="display:flex;gap:12px;margin-top:24px">
                    <button class="btn btn-gold" onclick="open360Viewer(<?= $prod['id'] ?>)" style="flex:1;justify-content:center;font-size:.65rem">
                        <i class="fas fa-cube"></i> Ver 360°
                    </button>
                    <button class="btn btn-outline" onclick="addToCart(<?= $prod['id'] ?>, null, <?= $prod['min_wholesale_qty'] ?>)" style="flex:1;justify-content:center;font-size:.65rem">
                        <i class="fas fa-boxes"></i> Pedir Mayoreo
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Wholesale features -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-top:60px" class="reveal">
            <?php foreach([
                ['fas fa-percentage','Precios preferenciales','Hasta 40% más económico que menudeo'],
                ['fas fa-palette','Surtido de tallas','Incluye variedad de tallas sin costo extra'],
                ['fas fa-handshake','Atención personalizada','Asesor dedicado para clientes mayoristas'],
                ['fas fa-redo','Reorden rápida','Repite tu pedido con un clic'],
            ] as $feat): ?>
            <div class="wholesale-feature">
                <div class="wholesale-feature-icon"><i class="fas <?= $feat[0] ?>"></i></div>
                <h4><?= $feat[1] ?></h4>
                <p><?= $feat[2] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- INDIVIDUAL SECTION -->
<section id="individual" style="background:var(--dark)">
    <div class="section-header reveal">
        <span class="section-tag">✦ Colección Completa ✦</span>
        <h2 class="section-title">Tienda <em style="font-style:italic;color:var(--gold)">Individual</em></h2>
        <div class="section-divider"></div>
        <p style="color:var(--gray);font-size:.88rem;margin-top:20px;max-width:500px;margin-left:auto;margin-right:auto">
            Una pieza, mil posibilidades. Explora toda nuestra selección y encuentra tu playera perfecta.
        </p>
    </div>

    <!-- Filter tabs -->
    <div class="material-tabs reveal" style="justify-content:center">
        <button class="material-tab active" data-filter="all">Todos</button>
        <button class="material-tab" data-filter="cotton">Algodón</button>
        <button class="material-tab" data-filter="polyester">Poliéster</button>
        <button class="material-tab" data-filter="featured">Destacados</button>
    </div>

    <?php
    $stmt = $db->prepare("SELECT p.*, 
        (SELECT image_path FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image
        FROM products p 
        WHERE p.sale_type IN ('individual','both') AND p.active = 1
        ORDER BY p.featured DESC, p.id");
    $stmt->execute();
    $individualProducts = $stmt->fetchAll();
    ?>

    <div class="products-grid reveal" id="products-grid">
        <?php foreach($individualProducts as $prod): ?>
        <?php $img = $prod['main_image'] ? 'uploads/products/'.$prod['main_image'] : 'assets/placeholder.jpg'; ?>
        <div class="product-card" 
             data-material="<?= $prod['material'] ?>"
             data-featured="<?= $prod['featured'] ? 'true' : 'false' ?>">
            <div class="product-img-wrap">
                <img src="<?= $img ?>" alt="<?= htmlspecialchars($prod['name']) ?>" loading="lazy">
                <div class="product-badges">
                    <span class="badge badge-<?= $prod['material'] === 'cotton' ? 'cotton' : 'poly' ?>">
                        <?= $prod['material'] === 'cotton' ? 'Algodón' : 'Poliéster' ?>
                    </span>
                    <?php if($prod['featured']): ?>
                    <span class="badge badge-sale">★ Destacado</span>
                    <?php endif; ?>
                </div>
                <div class="product-actions">
                    <button class="btn btn-gold" onclick="open360Viewer(<?= $prod['id'] ?>)" style="flex:1;justify-content:center;font-size:.62rem;padding:10px">
                        <i class="fas fa-cube"></i> Vista 360°
                    </button>
                    <button class="btn btn-outline" onclick="quickAdd(<?= $prod['id'] ?>)" style="justify-content:center;padding:10px 14px">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="product-info">
                <p class="product-material"><?= $prod['material'] === 'cotton' ? '100% Algodón' : '100% Poliéster' ?></p>
                <h3 class="product-name"><?= htmlspecialchars($prod['name']) ?></h3>
                <div class="product-prices">
                    <?php if($prod['price_individual']): ?>
                    <span class="price-individual">$<?= number_format($prod['price_individual'], 2) ?></span>
                    <?php endif; ?>
                    <?php if($prod['price_wholesale'] && $prod['sale_type'] === 'both'): ?>
                    <span class="price-wholesale">Mayoreo: $<?= number_format($prod['price_wholesale'], 2) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ABOUT STRIP -->
<div style="background:linear-gradient(135deg,#0a0a0a,#111,#0a0a0a);border-top:1px solid rgba(201,168,76,.1);border-bottom:1px solid rgba(201,168,76,.1);padding:80px 60px">
    <div style="max-width:1600px;margin:0 auto;text-align:center" class="reveal">
        <p style="font-family:var(--font-serif);font-size:clamp(1.5rem,3vw,2.5rem);font-weight:300;line-height:1.6;color:var(--cream)">
            "El negro no es solo un color.<br>
            <em style="color:var(--gold)">Es una declaración.</em>"
        </p>
        <div class="section-divider" style="margin-top:30px"></div>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <div class="footer-grid">
        <div class="footer-brand">
            <h3>BLACK <span>CLOTHES</span></h3>
            <p>Playeras negras de alta calidad en algodón y poliéster. Venta al mayoreo e individual para toda la República Mexicana.</p>
            <div style="display:flex;gap:16px;margin-top:24px">
                <?php foreach(['fab fa-instagram','fab fa-facebook','fab fa-whatsapp','fab fa-tiktok'] as $icon): ?>
                <a href="#" style="width:40px;height:40px;border:1px solid rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;color:var(--gray);font-size:.9rem;text-decoration:none;transition:var(--transition)"
                   onmouseover="this.style.borderColor='var(--gold)';this.style.color='var(--gold)'"
                   onmouseout="this.style.borderColor='rgba(255,255,255,.1)';this.style.color='var(--gray)'">
                    <i class="<?= $icon ?>"></i>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="footer-col">
            <h4>Tienda</h4>
            <ul>
                <li><a href="#mayoreo">Venta Mayoreo</a></li>
                <li><a href="#individual">Tienda Individual</a></li>
                <li><a href="catalog.php">Catálogo Completo</a></li>
                <li><a href="login.php">Mi Cuenta</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Productos</h4>
            <ul>
                <li><a href="#">Playeras Algodón</a></li>
                <li><a href="#">Playeras Poliéster</a></li>
                <li><a href="#">Packs Mayoreo</a></li>
                <li><a href="#">Novedades</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Contacto</h4>
            <ul>
                <li><a href="mailto:contacto@blackclothes.mx"><i class="fas fa-envelope" style="width:16px;color:var(--gold)"></i> contacto@blackclothes.mx</a></li>
                <li><a href="https://wa.me/521XXXXXXXXXX"><i class="fab fa-whatsapp" style="width:16px;color:var(--gold)"></i> WhatsApp</a></li>
                <li><a href="#"><i class="fas fa-map-marker-alt" style="width:16px;color:var(--gold)"></i> México</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© <?= date('Y') ?> Black Clothes. Todos los derechos reservados.</p>
        <p style="color:var(--gold);font-size:.68rem;letter-spacing:.2em">MADE IN MÉXICO 🖤</p>
    </div>
</footer>

<script src="js/main.js"></script>
<script>
// Filter tabs
document.querySelectorAll('.material-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.material-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        const filter = tab.dataset.filter;
        document.querySelectorAll('.product-card').forEach(card => {
            const show = filter === 'all' 
                || card.dataset.material === filter 
                || (filter === 'featured' && card.dataset.featured === 'true');
            card.style.display = show ? 'block' : 'none';
            card.style.animation = show ? 'fadeInUp .4s ease both' : 'none';
        });
    });
});

// Quick add (no variant selection - opens 360 instead)
function quickAdd(productId) {
    open360Viewer(productId);
}

// Logout
async function logout() {
    const res = await fetch('api/auth.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'logout'})
    });
    const data = await res.json();
    if(data.redirect) window.location.href = data.redirect;
}
</script>
</body>
</html>
