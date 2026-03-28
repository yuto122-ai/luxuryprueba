// BLACK CLOTHES — Main JS

document.addEventListener('DOMContentLoaded', () => {

    // Enable progressive-enhancement styles only when JS actually runs.
    document.documentElement.classList.add('js');

    // ====== PAGE LOADER ======
    const loader = document.querySelector('.page-loader');
    if (loader) {
        setTimeout(() => loader.classList.add('hidden'), 1200);
    }

    // ====== CUSTOM CURSOR / TOUCH POP ======
    const isTouchLike = window.matchMedia('(pointer: coarse)').matches
        || 'ontouchstart' in window
        || navigator.maxTouchPoints > 0;

    if (isTouchLike) {
        document.addEventListener('touchstart', (e) => {
            const t = e.touches && e.touches[0];
            if (!t) return;

            const pop = document.createElement('div');
            pop.className = 'tap-pop';
            pop.style.left = t.clientX + 'px';
            pop.style.top = t.clientY + 'px';
            document.body.appendChild(pop);
            pop.addEventListener('animationend', () => pop.remove(), { once: true });
        }, { passive: true });
    } else {
        const cursor = document.createElement('div');
        cursor.className = 'cursor';
        const follower = document.createElement('div');
        follower.className = 'cursor-follower';
        document.body.appendChild(cursor);
        document.body.appendChild(follower);

        let mouseX = 0, mouseY = 0, followerX = 0, followerY = 0;

        document.addEventListener('mousemove', e => {
            mouseX = e.clientX; mouseY = e.clientY;
            cursor.style.left = mouseX + 'px';
            cursor.style.top = mouseY + 'px';
        });

        function animateFollower() {
            followerX += (mouseX - followerX) * 0.12;
            followerY += (mouseY - followerY) * .12;
            follower.style.left = followerX + 'px';
            follower.style.top = followerY + 'px';
            requestAnimationFrame(animateFollower);
        }
        animateFollower();

        document.querySelectorAll('a, button, .product-card, [data-cursor]').forEach(el => {
            el.addEventListener('mouseenter', () => { cursor.classList.add('active'); follower.classList.add('active'); });
            el.addEventListener('mouseleave', () => { cursor.classList.remove('active'); follower.classList.remove('active'); });
        });
    }

    // ====== HEADER SCROLL ======
    const header = document.querySelector('header');
    if (header) {
        window.addEventListener('scroll', () => {
            header.classList.toggle('scrolled', window.scrollY > 50);
        });
    }

    // ====== UI HISTORY (ANDROID/WEB BACK) ======
    function getCurrentUiState() {
        return (history.state && history.state.ui) ? history.state.ui : null;
    }

    window.__pushUiState = function(ui) {
        if (!ui || getCurrentUiState() === ui) return;
        const nextState = Object.assign({}, history.state || {}, { ui });
        history.pushState(nextState, '');
    };

    function popUiState(ui) {
        if (getCurrentUiState() !== ui) return false;
        history.back();
        return true;
    }

    let scrollLockY = 0;
    let isBodyLocked = false;

    function lockBodyScroll() {
        if (isBodyLocked) return;
        scrollLockY = window.scrollY || document.documentElement.scrollTop || 0;
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollLockY}px`;
        document.body.style.left = '0';
        document.body.style.right = '0';
        document.body.style.width = '100%';
        isBodyLocked = true;
    }

    function unlockBodyScroll() {
        if (!isBodyLocked) return;
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.left = '';
        document.body.style.right = '';
        document.body.style.width = '';
        window.scrollTo(0, scrollLockY);
        isBodyLocked = false;
    }

    function syncBodyScrollLock() {
        const hasOverlayOpen = document.body.classList.contains('mobile-nav-open')
            || document.body.classList.contains('cart-open')
            || document.body.classList.contains('viewer-active');
        if (hasOverlayOpen) lockBodyScroll();
        else unlockBodyScroll();
    }

    window.__syncBodyScrollLock = syncBodyScrollLock;

    // ====== MOBILE NAV ======
    const mobileNavToggle = document.getElementById('mobile-nav-toggle');
    const mobileNavClose = document.getElementById('mobile-nav-close');
    const mobileNavOverlay = document.getElementById('mobile-nav-overlay');
    const mobileNavPanel = document.getElementById('mobile-nav-panel');

    function openMobileNav() {
        if (!mobileNavPanel) return;
        document.body.classList.add('mobile-nav-open');
        mobileNavPanel.setAttribute('aria-hidden', 'false');
        window.__pushUiState('mobile-nav');
        syncBodyScrollLock();
    }

    function closeMobileNav(fromHistory = false) {
        if (!mobileNavPanel) return;
        if (!fromHistory && popUiState('mobile-nav')) return;
        document.body.classList.remove('mobile-nav-open');
        mobileNavPanel.setAttribute('aria-hidden', 'true');
        syncBodyScrollLock();
    }

    if (mobileNavToggle) mobileNavToggle.addEventListener('click', openMobileNav);
    if (mobileNavClose) mobileNavClose.addEventListener('click', closeMobileNav);
    if (mobileNavOverlay) mobileNavOverlay.addEventListener('click', closeMobileNav);

    document.querySelectorAll('.mobile-nav-links a').forEach(link => {
        link.addEventListener('click', closeMobileNav);
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeMobileNav();
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) closeMobileNav();
    });

    // ====== SCROLL REVEAL ======
    const reveals = document.querySelectorAll('.reveal');
    if (isTouchLike) {
        // On some mobile browsers, IntersectionObserver + animated hidden state can fail,
        // leaving sections invisible but still clickable.
        reveals.forEach(el => el.classList.add('visible'));
    } else if ('IntersectionObserver' in window) {
        const revealObserver = new IntersectionObserver(entries => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.classList.add('visible');
                    revealObserver.unobserve(e.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
        reveals.forEach(el => revealObserver.observe(el));
    } else {
        // Older mobile browsers: show content immediately instead of leaving it hidden.
        reveals.forEach(el => el.classList.add('visible'));
    }

    // ====== AUTH TABS ======
    const tabs = document.querySelectorAll('.auth-tab');
    const tabContents = document.querySelectorAll('.auth-form');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tab;
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.style.display = 'none');
            tab.classList.add('active');
            const form = document.getElementById(target);
            if (form) form.style.display = 'block';
        });
    });

    // ====== CART ======
    const cartSidebar = document.getElementById('cart-sidebar');
    const cartOverlay = document.getElementById('cart-overlay');
    const cartBtns = document.querySelectorAll('[data-open-cart]');
    const cartClose = document.getElementById('cart-close');

    function openCart() {
        if (cartSidebar) cartSidebar.classList.add('open');
        if (cartOverlay) cartOverlay.classList.add('active');
        document.body.classList.add('cart-open');
        window.__pushUiState('cart');
        syncBodyScrollLock();
        loadCart();
    }
    function closeCart(fromHistory = false) {
        if (cartSidebar) cartSidebar.classList.remove('open');
        if (cartOverlay) cartOverlay.classList.remove('active');
        document.body.classList.remove('cart-open');
        syncBodyScrollLock();
        if (!fromHistory) popUiState('cart');
    }

    cartBtns.forEach(b => b.addEventListener('click', openCart));
    if (cartClose) cartClose.addEventListener('click', closeCart);
    if (cartOverlay) cartOverlay.addEventListener('click', closeCart);

    // Load Cart via AJAX
    async function loadCart() {
        const container = document.getElementById('cart-items-container');
        const totalEl = document.getElementById('cart-total-amount');
        if (!container) return;

        container.innerHTML = '<div style="text-align:center;padding:40px;color:#888"><div style="font-size:2rem;margin-bottom:10px">⟳</div>Cargando...</div>';

        try {
            const res = await fetch('api/cart.php?action=get');
            const data = await res.json();

            if (!data.items || data.items.length === 0) {
                container.innerHTML = `
                    <div class="cart-empty">
                        <div class="cart-empty-icon">🛒</div>
                        <p style="font-size:.85rem;color:#888">Tu carrito está vacío</p>
                        <p style="font-size:.75rem;color:#555;margin-top:8px">Descubre nuestra colección</p>
                    </div>`;
                if (totalEl) totalEl.textContent = '$0.00';
                return;
            }

            container.innerHTML = data.items.map(item => `
                <div class="cart-item" id="cart-item-${item.id}">
                    <div class="cart-item-img">
                        <img src="${item.image || 'assets/placeholder.jpg'}" alt="${item.name}" loading="lazy">
                    </div>
                    <div>
                        <p class="cart-item-name">${item.name}</p>
                        <p class="cart-item-variant">Talla: ${item.size || 'Única'} · ${item.material}</p>
                        <div class="cart-item-qty">
                            <button class="qty-btn" onclick="updateCartQty(${item.id}, ${item.quantity - 1})">−</button>
                            <span>${item.quantity}</span>
                            <button class="qty-btn" onclick="updateCartQty(${item.id}, ${item.quantity + 1})">+</button>
                        </div>
                    </div>
                    <div style="text-align:right">
                        <p class="cart-item-price">$${(item.price * item.quantity).toFixed(2)}</p>
                        <button class="cart-item-remove" onclick="removeFromCart(${item.id})" title="Eliminar">✕</button>
                    </div>
                </div>
            `).join('');

            if (totalEl) totalEl.textContent = '$' + data.total.toFixed(2);
            updateCartBadge(data.count);
        } catch(e) {
            container.innerHTML = '<p style="color:#888;text-align:center;padding:20px">Error al cargar el carrito</p>';
        }
    }

    // Expose cart functions globally
    window.openCart = openCart;
    window.closeCart = closeCart;

    window.addToCart = async function(productId, variantId, qty = 1) {
        try {
            const res = await fetch('api/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'add', product_id: productId, variant_id: variantId, quantity: qty })
            });
            const data = await res.json();
            if (data.success) {
                updateCartBadge(data.count);
                showToast('Producto añadido al carrito', 'success');
                openCart();
            } else {
                showToast(data.message || 'Error al añadir', 'error');
            }
        } catch(e) {
            showToast('Error de conexión', 'error');
        }
    };

    window.updateCartQty = async function(itemId, qty) {
        if (qty <= 0) { removeFromCart(itemId); return; }
        try {
            await fetch('api/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'update', item_id: itemId, quantity: qty })
            });
            loadCart();
        } catch(e) {}
    };

    window.removeFromCart = async function(itemId) {
        try {
            await fetch('api/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'remove', item_id: itemId })
            });
            const el = document.getElementById('cart-item-' + itemId);
            if (el) { el.style.opacity = '0'; el.style.transform = 'translateX(20px)'; el.style.transition = '.3s'; setTimeout(() => loadCart(), 300); }
        } catch(e) {}
    };

    // ====== 360 VIEWER ======
    // open360Viewer es sobreescrito por index.php con PRODUCTS_DATA (mas eficiente, sin fetch)
    // Este es el fallback para paginas que no tienen PRODUCTS_DATA
    window.open360Viewer = window.open360Viewer || function(productId) {
        fetch('api/product360.php?id=' + productId)
            .then(r => r.json())
            .then(data => { if (data.product) console.log('viewer fallback:', data); });
    };

    // close360Viewer: usado por index.php y el boton cerrar
    window.close360Viewer = function(fromHistory = false) {
        const viewer = document.getElementById('viewer-360');
        if (!fromHistory && popUiState('viewer')) return;
        if (viewer) viewer.classList.remove('active');
        document.body.classList.remove('viewer-active');
        syncBodyScrollLock();
    };

    const mobileViewerCta = document.getElementById('mobile-viewer-cta');
    if (mobileViewerCta) {
        mobileViewerCta.addEventListener('click', () => {
            const addBtn = document.getElementById('viewer-add-cart');
            if (addBtn) addBtn.click();
        });
    }

    // Cerrar viewer solo si el click fue DIRECTAMENTE en el fondo negro (viewer-360)
    // NOT en viewer-container ni en ningun hijo
    const viewer360 = document.getElementById('viewer-360');
    if (viewer360) {
        viewer360.addEventListener('click', function(e) {
            if (e.target === viewer360) window.close360Viewer();
        });
    }

    window.addEventListener('popstate', () => {
        const ui = getCurrentUiState();

        if (document.body.classList.contains('mobile-nav-open') && ui !== 'mobile-nav') {
            closeMobileNav(true);
        }
        if (document.body.classList.contains('cart-open') && ui !== 'cart') {
            closeCart(true);
        }
        if (document.body.classList.contains('viewer-active') && ui !== 'viewer') {
            window.close360Viewer(true);
        }

        syncBodyScrollLock();
    });

        // ====== TOAST NOTIFICATIONS ======
    window.showToast = function(message, type = 'info') {
        const existing = document.querySelector('.toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.style.cssText = `
            position:fixed; bottom:30px; right:30px; z-index:99999;
            background:${type === 'success' ? '#1a3a2a' : type === 'error' ? '#3a1a1a' : '#1a1a2a'};
            border:1px solid ${type === 'success' ? '#2a8a4a' : type === 'error' ? '#cc3333' : '#c9a84c'};
            color:#fff; padding:16px 24px;
            font-size:.8rem; letter-spacing:.1em;
            min-width:280px; max-width:400px;
            transform:translateY(20px); opacity:0;
            transition:all .3s ease;
            box-shadow:0 10px 40px rgba(0,0,0,.5);
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => { toast.style.transform = 'translateY(0)'; toast.style.opacity = '1'; }, 10);
        setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 3500);
    };

    function updateCartBadge(count) {
        const badges = document.querySelectorAll('.cart-badge');
        badges.forEach(badge => {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        });
    }

    // ====== INITIAL CART COUNT ======
    fetch('api/cart.php?action=count')
        .then(r => r.json())
        .then(d => updateCartBadge(d.count || 0))
        .catch(() => {});

    // ====== SMOOTH SCROLL FOR ANCHORS ======
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const target = document.querySelector(a.getAttribute('href'));
            if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
        });
    });

});