<?php require_once 'php/config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — BLACK CLOTHES</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .checkout-grid { display:grid; grid-template-columns:1fr 420px; gap:40px; }
        .checkout-box { background:var(--dark2); border:1px solid rgba(255,255,255,.05); padding:40px; }
        .checkout-box h3 { font-family:var(--font-serif); font-size:1.4rem; margin-bottom:28px; padding-bottom:16px; border-bottom:1px solid rgba(255,255,255,.06); }
        .order-summary-item { display:flex; justify-content:space-between; align-items:center; padding:14px 0; border-bottom:1px solid rgba(255,255,255,.04); }
        .order-summary-total { display:flex; justify-content:space-between; align-items:center; padding:20px 0 0; margin-top:8px; }
        .order-type-btn {
            flex:1; padding:16px; background:none; border:1px solid rgba(255,255,255,.1);
            color:var(--gray); font-family:var(--font-sans); font-size:.72rem; letter-spacing:.15em;
            text-transform:uppercase; cursor:pointer; transition:var(--transition); text-align:center;
        }
        .order-type-btn.active { border-color:var(--gold); color:var(--gold); background:rgba(201,168,76,.06); }
        @media(max-width:900px) { .checkout-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="page-loader"><div class="loader-text">BLACK <span>CLOTHES</span></div></div>

<header id="main-header">
    <nav>
        <a href="index.php" class="logo">BLACK <span>CLOTHES</span></a>
        <ul class="nav-links">
            <li><a href="index.php#mayoreo">Mayoreo</a></li>
            <li><a href="index.php#individual">Individual</a></li>
        </ul>
        <div class="nav-actions">
            <?php if(isLoggedIn()): ?>
                <a href="profile.php" class="nav-icon"><i class="fas fa-user"></i></a>
            <?php else: ?>
                <a href="login.php" class="nav-icon"><i class="fas fa-user"></i></a>
            <?php endif; ?>
            <button class="nav-icon" data-open-cart style="position:relative">
                <i class="fas fa-shopping-bag"></i>
                <span class="cart-badge" style="display:none">0</span>
            </button>
        </div>
    </nav>
</header>

<div class="cart-overlay" id="cart-overlay"></div>
<aside class="cart-sidebar" id="cart-sidebar">
    <div class="cart-header">
        <h3>Mi Carrito</h3>
        <button class="cart-close" id="cart-close"><i class="fas fa-times"></i></button>
    </div>
    <div class="cart-items" id="cart-items-container"></div>
    <div class="cart-footer">
        <div class="cart-total">
            <span class="cart-total-label">Total</span>
            <span class="cart-total-amount" id="cart-total-amount">$0.00</span>
        </div>
        <a href="checkout.php" class="btn btn-gold" style="width:100%;justify-content:center">Checkout <i class="fas fa-arrow-right"></i></a>
    </div>
</aside>

<main style="padding-top:120px;min-height:100vh;background:var(--black)">
    <div style="max-width:1200px;margin:0 auto;padding:0 40px 80px">
        
        <div style="margin-bottom:40px">
            <span class="section-tag">✦ Finalizar Compra ✦</span>
            <h1 style="font-family:var(--font-serif);font-size:2.5rem;margin-top:8px">Checkout</h1>
        </div>

        <?php if(!isLoggedIn()): ?>
        <div class="alert alert-info" style="margin-bottom:30px">
            <i class="fas fa-info-circle"></i> 
            <a href="login.php" style="color:var(--gold)">Inicia sesión</a> para guardar tu historial de pedidos y recibir actualizaciones.
        </div>
        <?php endif; ?>

        <div id="checkout-alert"></div>

        <div class="checkout-grid">
            <!-- FORM -->
            <div>
                <!-- Order type -->
                <div class="checkout-box" style="margin-bottom:24px">
                    <h3><i class="fas fa-layer-group" style="color:var(--gold);margin-right:12px"></i>Tipo de Pedido</h3>
                    <div style="display:flex;gap:16px">
                        <button class="order-type-btn active" id="type-individual" onclick="setOrderType('individual')">
                            <i class="fas fa-tshirt" style="display:block;font-size:1.5rem;margin-bottom:8px;color:var(--gold)"></i>
                            Individual<br><small style="font-size:.65rem;color:var(--gray)">Precio normal</small>
                        </button>
                        <button class="order-type-btn" id="type-wholesale" onclick="setOrderType('wholesale')">
                            <i class="fas fa-boxes" style="display:block;font-size:1.5rem;margin-bottom:8px;color:var(--gold)"></i>
                            Mayoreo<br><small style="font-size:.65rem;color:var(--gray)">Precio especial</small>
                        </button>
                    </div>
                </div>

                <!-- Shipping info -->
                <div class="checkout-box">
                    <h3><i class="fas fa-map-marker-alt" style="color:var(--gold);margin-right:12px"></i>Datos de Envío</h3>
                    <div class="form-group">
                        <label class="form-label">Nombre completo *</label>
                        <input type="text" class="form-input" id="shipping-name" placeholder="Nombre del destinatario"
                            value="<?= isLoggedIn() ? htmlspecialchars($_SESSION['user_name'] ?? '') : '' ?>">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                        <div class="form-group">
                            <label class="form-label">Teléfono *</label>
                            <input type="tel" class="form-input" id="shipping-phone" placeholder="55 1234 5678">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-input" id="shipping-email" placeholder="para confirmación"
                                value="<?= isLoggedIn() ? htmlspecialchars($_SESSION['user_email'] ?? '') : '' ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Dirección completa *</label>
                        <textarea class="form-input" id="shipping-address" rows="3" placeholder="Calle, número, colonia, ciudad, estado, CP"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notas adicionales</label>
                        <textarea class="form-input" id="order-notes" rows="2" placeholder="Instrucciones especiales de entrega, referencias..."></textarea>
                    </div>
                </div>
            </div>

            <!-- SUMMARY -->
            <div>
                <div class="checkout-box" style="position:sticky;top:100px">
                    <h3><i class="fas fa-receipt" style="color:var(--gold);margin-right:12px"></i>Resumen del Pedido</h3>
                    <div id="checkout-items">
                        <div style="text-align:center;padding:20px;color:var(--gray)">
                            <i class="fas fa-spinner fa-spin"></i> Cargando carrito...
                        </div>
                    </div>
                    <div style="border-top:1px solid rgba(255,255,255,.08);margin-top:16px;padding-top:16px">
                        <div class="order-summary-item" style="border:none;padding:8px 0">
                            <span style="font-size:.78rem;color:var(--gray)">Subtotal</span>
                            <span id="checkout-subtotal">$0.00</span>
                        </div>
                        <div class="order-summary-item" style="border:none;padding:8px 0">
                            <span style="font-size:.78rem;color:var(--gray)">Envío</span>
                            <span id="checkout-shipping" style="color:var(--gold)">$150.00</span>
                        </div>
                        <div class="order-summary-total" style="border-top:1px solid rgba(201,168,76,.2)">
                            <span style="font-size:.82rem;letter-spacing:.15em;text-transform:uppercase">Total</span>
                            <span id="checkout-total" style="font-family:var(--font-serif);font-size:2rem;color:var(--gold)">$0.00</span>
                        </div>
                    </div>
                    <button class="btn btn-gold" onclick="placeOrder()" id="place-order-btn" style="width:100%;justify-content:center;margin-top:24px;font-size:.78rem">
                        <i class="fas fa-lock"></i> Confirmar Pedido
                    </button>
                    <div style="margin-top:16px;font-size:.68rem;color:var(--gray);text-align:center;line-height:1.8">
                        <i class="fas fa-shield-alt" style="color:var(--gold)"></i> Pedido seguro<br>
                        <i class="fas fa-truck" style="color:var(--gold)"></i> Envío gratis en compras +$1,500<br>
                        <i class="fab fa-telegram" style="color:var(--gold)"></i> Confirmación por Telegram
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="js/main.js"></script>
<script>
let orderType = 'individual';
let cartData = null;

function setOrderType(type) {
    orderType = type;
    document.getElementById('type-individual').classList.toggle('active', type === 'individual');
    document.getElementById('type-wholesale').classList.toggle('active', type === 'wholesale');
    renderSummary();
}

async function loadCheckoutCart() {
    try {
        const res = await fetch('api/cart.php?action=get');
        cartData = await res.json();
        renderSummary();
    } catch(e) {}
}

function renderSummary() {
    if (!cartData || !cartData.items) return;
    const container = document.getElementById('checkout-items');
    
    if (cartData.items.length === 0) {
        container.innerHTML = '<div style="text-align:center;padding:30px;color:var(--gray)">Carrito vacío — <a href="index.php" style="color:var(--gold)">ir a la tienda</a></div>';
        return;
    }

    let subtotal = 0;
    container.innerHTML = cartData.items.map(item => {
        const price = parseFloat(item.price);
        const lineTotal = price * item.quantity;
        subtotal += lineTotal;
        return `
        <div class="order-summary-item">
            <div style="display:flex;gap:12px;align-items:center">
                <img src="${item.image}" style="width:50px;height:62px;object-fit:cover;background:var(--dark3)" alt="${item.name}">
                <div>
                    <p style="font-size:.82rem">${item.name}</p>
                    <p style="font-size:.68rem;color:var(--gray)">${item.size ? 'Talla ' + item.size : 'Única'} × ${item.quantity}</p>
                </div>
            </div>
            <span style="color:var(--gold);font-size:.9rem">$${lineTotal.toFixed(2)}</span>
        </div>`;
    }).join('');

    const shipping = subtotal >= 1500 ? 0 : 150;
    const total = subtotal + shipping;

    document.getElementById('checkout-subtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('checkout-shipping').textContent = shipping === 0 ? 'GRATIS' : '$' + shipping.toFixed(2);
    document.getElementById('checkout-total').textContent = '$' + total.toFixed(2);
}

async function placeOrder() {
    const name = document.getElementById('shipping-name').value.trim();
    const phone = document.getElementById('shipping-phone').value.trim();
    const address = document.getElementById('shipping-address').value.trim();
    const notes = document.getElementById('order-notes').value.trim();

    if (!name || !address) {
        document.getElementById('checkout-alert').innerHTML = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Completa los datos de envío requeridos</div>';
        window.scrollTo({top:0,behavior:'smooth'});
        return;
    }

    const btn = document.getElementById('place-order-btn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    btn.disabled = true;

    try {
        const res = await fetch('api/orders.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({
                action:'place', order_type:orderType,
                shipping_name:name, shipping_phone:phone,
                shipping_address:address, notes
            })
        });
        const data = await res.json();

        if (data.success) {
            document.getElementById('checkout-alert').innerHTML = `
                <div class="alert alert-success" style="font-size:.88rem">
                    <i class="fas fa-check-circle"></i> ¡Pedido <strong>#${data.order_number}</strong> realizado exitosamente!<br>
                    <small>Total: $${data.total.toFixed(2)} — Te contactaremos por WhatsApp/Telegram para coordinar pago y envío.</small>
                </div>`;
            window.scrollTo({top:0,behavior:'smooth'});
            document.getElementById('checkout-items').innerHTML = '';
            document.querySelector('.cart-badge').style.display = 'none';
            btn.innerHTML = '<i class="fas fa-check"></i> ¡Pedido Realizado!';
            btn.style.background = 'var(--green)';
        } else {
            document.getElementById('checkout-alert').innerHTML = `<div class="alert alert-error">${data.message}</div>`;
            if (data.message.includes('Inicia sesión')) {
                setTimeout(() => window.location.href = 'login.php', 2000);
            }
            btn.innerHTML = '<i class="fas fa-lock"></i> Confirmar Pedido';
            btn.disabled = false;
        }
    } catch(e) {
        document.getElementById('checkout-alert').innerHTML = '<div class="alert alert-error">Error de conexión</div>';
        btn.innerHTML = '<i class="fas fa-lock"></i> Confirmar Pedido';
        btn.disabled = false;
    }
}

loadCheckoutCart();
</script>
</body>
</html>