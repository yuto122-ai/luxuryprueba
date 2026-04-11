<?php require_once 'php/config.php'; ?>
<script src="https://js.stripe.com/v3/"></script>
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
        .checkout-mobile-steps {
            display: none;
            gap: 10px;
            margin-bottom: 18px;
        }
        .checkout-step-btn {
            flex: 1;
            padding: 12px;
            border: 1px solid rgba(255,255,255,.12);
            background: transparent;
            color: var(--gray-light);
            font-size: .68rem;
            letter-spacing: .14em;
            text-transform: uppercase;
            cursor: pointer;
            transition: var(--transition);
        }
        .checkout-step-btn.active {
            border-color: var(--gold);
            color: var(--gold);
            background: rgba(201,168,76,.08);
        }
        .order-summary-item { display:flex; justify-content:space-between; align-items:center; padding:14px 0; border-bottom:1px solid rgba(255,255,255,.04); }
        .order-summary-total { display:flex; justify-content:space-between; align-items:center; padding:20px 0 0; margin-top:8px; }
        .order-type-btn {
            flex:1; padding:16px; background:none; border:1px solid rgba(255,255,255,.1);
            color:var(--gray); font-family:var(--font-sans); font-size:.72rem; letter-spacing:.15em;
            text-transform:uppercase; cursor:pointer; transition:var(--transition); text-align:center;
        }
        .order-type-btn.active { border-color:var(--gold); color:var(--gold); background:rgba(201,168,76,.06); }
        .order-type-btn:disabled {
            opacity:.55;
            cursor:not-allowed;
            filter:grayscale(.2);
        }
        .order-type-note {
            margin-top:12px;
            font-size:.68rem;
            color:var(--gray);
            letter-spacing:.08em;
            min-height:18px;
        }
        .checkout-trust-copy {
            margin-top:14px;
            font-size:.69rem;
            color:var(--gray-light);
            text-align:center;
            line-height:1.7;
            letter-spacing:.04em;
        }
        .checkout-optional-toggle {
            display:none;
            width:100%;
            justify-content:center;
            font-size:.66rem;
            padding:10px 14px;
            margin:6px 0 14px;
        }
        .checkout-optional-fields { display:block; }
        .mobile-next-step,
        .mobile-back-step { display:none; }
        .mobile-checkout-bar {
            display: none;
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9500;
            background: linear-gradient(180deg, rgba(11,11,11,.98), rgba(0,0,0,.98));
            border-top: 1px solid rgba(201,168,76,.25);
            padding: 10px 12px calc(10px + env(safe-area-inset-bottom));
        }
        .mobile-checkout-bar .wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .mobile-checkout-total {
            min-width: 110px;
            color: var(--gold);
            font-family: var(--font-serif);
            font-size: 1.35rem;
            text-align: center;
        }
        @media(max-width:900px) {
            .checkout-grid { grid-template-columns:1fr; }
            main { padding-bottom: 130px; }
            body.checkout-step-summary .mobile-checkout-bar { display: block; }
            .checkout-box { padding:26px 18px; }
            .checkout-mobile-steps { display:flex; }
            .checkout-optional-toggle { display:inline-flex; }
            .checkout-optional-fields { display:none; }
            .checkout-optional-fields.open { display:block; }
            .mobile-next-step,
            .mobile-back-step { display:inline-flex; width:100%; justify-content:center; }
            body.checkout-step-shipping #checkout-summary-column { display:none; }
            body.checkout-step-summary #checkout-form-column { display:none; }
        }
    </style>
</head>
<body class="checkout-page checkout-step-shipping">
<div class="page-loader"><div class="loader-text">BLACK <span>CLOTHES</span></div></div>

<header id="main-header">
    <nav>
        <button class="nav-toggle" id="mobile-nav-toggle" aria-label="Abrir menú">
            <i class="fas fa-bars"></i>
        </button>
        <a href="index" class="logo">BLACK <span>CLOTHES</span></a>
        <ul class="nav-links">
            <li><a href="index#mayoreo">Mayoreo</a></li>
            <li><a href="index#individual">Individual</a></li>
        </ul>
        <div class="nav-actions">
            <?php if(isLoggedIn()): ?>
                <a href="<?= isAdmin() ? 'admin/' : 'checkout' ?>" class="nav-icon"><i class="fas fa-user"></i></a>
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
        <li><a href="index#mayoreo">Mayoreo</a></li>
        <li><a href="index#individual">Individual</a></li>
        <li><a href="checkout">Checkout</a></li>
        <?php if(isLoggedIn()): ?>
            <?php if(isAdmin()): ?><li><a href="admin/">Panel Admin</a></li><?php endif; ?>
            <li><a href="#" onclick="logout(); return false;">Cerrar sesión</a></li>
        <?php else: ?>
            <li><a href="login">Iniciar sesión</a></li>
        <?php endif; ?>
    </ul>
</aside>

<button class="mobile-cart-cta" data-open-cart>
    <i class="fas fa-shopping-bag"></i>
    Ver carrito
    <span class="cart-badge" style="display:none">0</span>
</button>

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
        <a href="checkout" class="btn btn-gold" style="width:100%;justify-content:center">Checkout <i class="fas fa-arrow-right"></i></a>
    </div>
</aside>

<main style="padding-top:120px;min-height:100vh;background:var(--black)">
    <div style="max-width:1200px;margin:0 auto;padding:0 40px 80px">
        
        <div style="margin-bottom:40px">
            <span class="section-tag">✦ Finalizar Compra ✦</span>
            <h1 style="font-family:var(--font-serif);font-size:2.5rem;margin-top:8px">Checkout</h1>
        </div>

        <div class="checkout-mobile-steps">
            <button type="button" class="checkout-step-btn active" id="step-btn-shipping" onclick="setCheckoutStep('shipping')">1. Datos</button>
            <button type="button" class="checkout-step-btn" id="step-btn-summary" onclick="setCheckoutStep('summary')">2. Resumen</button>
        </div>

        <?php if(!isLoggedIn()): ?>
        <div class="alert alert-info" style="margin-bottom:30px">
            <i class="fas fa-info-circle"></i> 
            <a href="login" style="color:var(--gold)">Inicia sesión</a> para guardar tu historial de pedidos y recibir actualizaciones.
        </div>
        <?php endif; ?>

        <div id="checkout-alert"></div>

        <div class="checkout-grid">
            <!-- FORM -->
            <div id="checkout-form-column">
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
                    <div class="order-type-note" id="order-type-note"></div>
                </div>

                <!-- Shipping info -->
                <div class="checkout-box">
                    <h3><i class="fas fa-map-marker-alt" style="color:var(--gold);margin-right:12px"></i>Datos de Envío</h3>
                    <div class="form-group">
                        <label class="form-label">Nombre completo *</label>
                        <input type="text" class="form-input" id="shipping-name" placeholder="Nombre del destinatario" autocomplete="name"
                            value="<?= isLoggedIn() ? htmlspecialchars($_SESSION['user_name'] ?? '') : '' ?>">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                        <div class="form-group">
                            <label class="form-label">Teléfono *</label>
                            <input type="tel" class="form-input" id="shipping-phone" placeholder="55 1234 5678" autocomplete="tel">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Dirección completa *</label>
                            <input type="text" class="form-input" id="shipping-address" placeholder="Calle, número, colonia, ciudad, estado, CP" autocomplete="street-address">
                        </div>
                    </div>

                    <button type="button" class="btn btn-dark checkout-optional-toggle" id="checkout-optional-toggle" onclick="toggleOptionalFields()">
                        <i class="fas fa-plus"></i> Agregar datos opcionales
                    </button>

                    <div id="checkout-optional-fields" class="checkout-optional-fields">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-input" id="shipping-email" placeholder="para confirmación" autocomplete="email"
                                value="<?= isLoggedIn() ? htmlspecialchars($_SESSION['user_email'] ?? '') : '' ?>">
                        </div>
                    </div>
                    <div id="checkout-optional-fields-2" class="checkout-optional-fields">
                        <div class="form-group">
                            <label class="form-label">Notas adicionales</label>
                            <textarea class="form-input" id="order-notes" rows="2" placeholder="Instrucciones especiales de entrega, referencias..."></textarea>
                        </div>
                    </div>
                    <button type="button" class="btn btn-gold mobile-next-step" onclick="setCheckoutStep('summary')" style="margin-top:8px">
                        <i class="fas fa-arrow-right"></i> Continuar al resumen
                    </button>
                </div>
            </div>

            <!-- SUMMARY -->
            <div id="checkout-summary-column">
                <div class="checkout-box" style="position:sticky;top:100px">
                    <h3><i class="fas fa-receipt" style="color:var(--gold);margin-right:12px"></i>Resumen del Pedido</h3>
                    <button type="button" class="btn btn-dark mobile-back-step" onclick="setCheckoutStep('shipping')" style="margin-bottom:14px">
                        <i class="fas fa-arrow-left"></i> Volver a datos
                    </button>
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
                    <div class="checkout-trust-copy">
                        Compra protegida, confirmación por WhatsApp/Telegram y soporte humano inmediato.
                    </div>
                    <button class="btn btn-gold" onclick="placeOrder()" id="place-order-btn" style="width:100%;justify-content:center;margin-top:24px;font-size:.78rem">
                        <i class="fas fa-lock"></i> Ir al pago
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

<div class="mobile-checkout-bar">
    <div style="font-size:.66rem;color:var(--gray-light);text-align:center;margin-bottom:8px;letter-spacing:.08em">
        Pago seguro y seguimiento inmediato de tu pedido.
    </div>
    <div class="wrap">
        <div class="mobile-checkout-total" id="mobile-checkout-total">$0.00</div>
        <button class="btn btn-gold" id="mobile-place-order-btn" onclick="placeOrder()" style="flex:1;justify-content:center;padding:14px 18px;font-size:.72rem">
            <i class="fas fa-lock"></i> Pagar
        </button>
    </div>
</div>

<script src="js/main.js"></script>
<script>
let orderType = 'individual';
let cartData = null;
let isPlacingOrder = false;
let orderTypeLocked = false;

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function setCheckoutStep(step) {
    const body = document.body;
    const shippingBtn = document.getElementById('step-btn-shipping');
    const summaryBtn = document.getElementById('step-btn-summary');
    const isSummary = step === 'summary';

    body.classList.toggle('checkout-step-summary', isSummary);
    body.classList.toggle('checkout-step-shipping', !isSummary);
    if (shippingBtn) shippingBtn.classList.toggle('active', !isSummary);
    if (summaryBtn) summaryBtn.classList.toggle('active', isSummary);
}

function toggleOptionalFields() {
    const optionalA = document.getElementById('checkout-optional-fields');
    const optionalB = document.getElementById('checkout-optional-fields-2');
    const btn = document.getElementById('checkout-optional-toggle');
    if (!optionalA || !optionalB || !btn) return;

    const willOpen = !optionalA.classList.contains('open');
    optionalA.classList.toggle('open', willOpen);
    optionalB.classList.toggle('open', willOpen);
    btn.innerHTML = willOpen
        ? '<i class="fas fa-minus"></i> Ocultar datos opcionales'
        : '<i class="fas fa-plus"></i> Agregar datos opcionales';
}

function buildIdempotencyKey() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
        return window.crypto.randomUUID();
    }
    return 'ord-' + Date.now() + '-' + Math.random().toString(16).slice(2);
}

function setOrderType(type) {
    if (orderTypeLocked) return;
    orderType = type;
    document.getElementById('type-individual').classList.toggle('active', type === 'individual');
    document.getElementById('type-wholesale').classList.toggle('active', type === 'wholesale');
    renderSummary();
}

function applyOrderTypeRules(totalPieces) {
    const individualBtn = document.getElementById('type-individual');
    const wholesaleBtn = document.getElementById('type-wholesale');
    const noteEl = document.getElementById('order-type-note');

    if (totalPieces < 12) {
        orderTypeLocked = true;
        orderType = 'individual';
        individualBtn.classList.add('active');
        wholesaleBtn.classList.remove('active');
        individualBtn.disabled = true;
        wholesaleBtn.disabled = true;
        noteEl.textContent = 'Con menos de 12 piezas, el tipo de pedido se fija en Menudeo (Individual).';
        return;
    }

    orderTypeLocked = false;
    individualBtn.disabled = false;
    wholesaleBtn.disabled = false;
    noteEl.textContent = '';
}

async function loadCheckoutCart() {
    try {
        const res = await fetch('api/cart.php?action=get');
        cartData = await res.json();
        renderSummary();
    } catch (e) {}
}

function renderSummary() {
    if (!cartData || !cartData.items) return;
    const container = document.getElementById('checkout-items');
    const mobileTotalEl = document.getElementById('mobile-checkout-total');

    const resolveItemPrice = (item) => {
        const wholesale = parseFloat(item.price_wholesale || 0);
        const variant = parseFloat(item.variant_price || 0);
        const individual = parseFloat(item.price_individual || 0);

        if (orderType === 'wholesale' && wholesale > 0) return wholesale;
        if (variant > 0) return variant;
        if (individual > 0) return individual;
        return parseFloat(item.price || 0);
    };

    if (cartData.items.length === 0) {
        container.innerHTML = '<div style="text-align:center;padding:30px;color:var(--gray)">Carrito vacío — <a href="index" style="color:var(--gold)">ir a la tienda</a></div>';
        if (mobileTotalEl) mobileTotalEl.textContent = '$0.00';
        return;
    }

    let subtotal = 0;
    const totalPieces = cartData.items.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
    applyOrderTypeRules(totalPieces);

    container.innerHTML = cartData.items.map(item => {
        const price = resolveItemPrice(item);
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
    if (mobileTotalEl) mobileTotalEl.textContent = '$' + total.toFixed(2);
}

async function placeOrder() {
    if (isPlacingOrder) return;

    const name = document.getElementById('shipping-name').value.trim();
    const phone = document.getElementById('shipping-phone').value.trim();
    const address = document.getElementById('shipping-address').value.trim();
    const notes = document.getElementById('order-notes').value.trim();

    if (!name || !address) {
        document.getElementById('checkout-alert').innerHTML = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Completa los datos de envío requeridos</div>';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

    const btn = document.getElementById('place-order-btn');
    const mobileBtn = document.getElementById('mobile-place-order-btn');
    isPlacingOrder = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    btn.disabled = true;
    if (mobileBtn) {
        mobileBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        mobileBtn.disabled = true;
    }

    const idempotencyKey = buildIdempotencyKey();

    try {
        const res = await fetch('api/create-checkout-session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Idempotency-Key': idempotencyKey
            },
            body: JSON.stringify({
                action: 'create_checkout_session',
                order_type: orderType,
                shipping_name: name,
                shipping_phone: phone,
                shipping_address: address,
                notes,
                idempotency_key: idempotencyKey
            })
        });

        const data = await res.json();
        if (!data.success || !data.id) {
            const messageText = data.error || data.message || 'No se pudo iniciar el pago';
            document.getElementById('checkout-alert').innerHTML = `<div class="alert alert-error">${escapeHtml(messageText)}</div>`;
            btn.innerHTML = '<i class="fas fa-lock"></i> Ir al pago';
            btn.disabled = false;
            if (mobileBtn) {
                mobileBtn.innerHTML = '<i class="fas fa-lock"></i> Pagar';
                mobileBtn.disabled = false;
            }
            isPlacingOrder = false;
            return;
        }

        const stripeKey = '<?= htmlspecialchars(STRIPE_PUBLISHABLE_KEY, ENT_QUOTES) ?>';
        if (!stripeKey) {
            document.getElementById('checkout-alert').innerHTML = '<div class="alert alert-error">Falta configurar la clave pública de Stripe.</div>';
            btn.innerHTML = '<i class="fas fa-lock"></i> Ir al pago';
            btn.disabled = false;
            if (mobileBtn) {
                mobileBtn.innerHTML = '<i class="fas fa-lock"></i> Pagar';
                mobileBtn.disabled = false;
            }
            isPlacingOrder = false;
            return;
        }

        const stripe = Stripe(stripeKey);
        const result = await stripe.redirectToCheckout({ sessionId: data.id });
        if (result.error) {
            document.getElementById('checkout-alert').innerHTML = `<div class="alert alert-error">${escapeHtml(result.error.message || 'No se pudo abrir Stripe')}</div>`;
            btn.innerHTML = '<i class="fas fa-lock"></i> Ir al pago';
            btn.disabled = false;
            if (mobileBtn) {
                mobileBtn.innerHTML = '<i class="fas fa-lock"></i> Pagar';
                mobileBtn.disabled = false;
            }
            isPlacingOrder = false;
        }
    } catch (e) {
        document.getElementById('checkout-alert').innerHTML = '<div class="alert alert-error">Error de conexión</div>';
        btn.innerHTML = '<i class="fas fa-lock"></i> Ir al pago';
        btn.disabled = false;
        if (mobileBtn) {
            mobileBtn.innerHTML = '<i class="fas fa-lock"></i> Pagar';
            mobileBtn.disabled = false;
        }
        isPlacingOrder = false;
    }
}

loadCheckoutCart();
</script>
</body>
</html>