// BLACK CLOTHES — Main JS

document.addEventListener('DOMContentLoaded', () => {

    // ====== PAGE LOADER ======
    const loader = document.querySelector('.page-loader');
    if (loader) {
        setTimeout(() => loader.classList.add('hidden'), 1200);
    }

    // ====== CUSTOM CURSOR ======
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
        followerY += (mouseY - followerY) *.12;
        follower.style.left = followerX + 'px';
        follower.style.top = followerY + 'px';
        requestAnimationFrame(animateFollower);
    }
    animateFollower();

    document.querySelectorAll('a, button, .product-card, [data-cursor]').forEach(el => {
        el.addEventListener('mouseenter', () => { cursor.classList.add('active'); follower.classList.add('active'); });
        el.addEventListener('mouseleave', () => { cursor.classList.remove('active'); follower.classList.remove('active'); });
    });

    // ====== HEADER SCROLL ======
    const header = document.querySelector('header');
    if (header) {
        window.addEventListener('scroll', () => {
            header.classList.toggle('scrolled', window.scrollY > 50);
        });
    }

    // ====== SCROLL REVEAL ======
    const reveals = document.querySelectorAll('.reveal');
    const revealObserver = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('visible');
                revealObserver.unobserve(e.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
    reveals.forEach(el => revealObserver.observe(el));

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
        document.body.style.overflow = 'hidden';
        loadCart();
    }
    function closeCart() {
        if (cartSidebar) cartSidebar.classList.remove('open');
        if (cartOverlay) cartOverlay.classList.remove('active');
        document.body.style.overflow = '';
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
    window.open360Viewer = function(productId) {
        fetch(`api/product360.php?id=${productId}`)
            .then(r => r.json())
            .then(data => {
                if (!data.product) return;
                initViewer(data);
            });
    };

    function initViewer(data) {
        const viewer = document.getElementById('viewer-360');
        if (!viewer) return;
        viewer.classList.add('active');
        document.body.style.overflow = 'hidden';

        const product = data.product;
        const images = data.images && data.images.length > 0 ? data.images : ['assets/placeholder.jpg'];

        viewer.querySelector('#viewer-title').textContent = product.name;
        viewer.querySelector('#viewer-desc').textContent = product.description;
        viewer.querySelector('#viewer-price').textContent = product.price_individual
            ? '$' + parseFloat(product.price_individual).toFixed(2)
            : '$' + parseFloat(product.price_wholesale).toFixed(2);
        viewer.querySelector('#viewer-material').textContent = product.material === 'cotton' ? '100% Algodón' : product.material === 'polyester' ? '100% Poliéster' : 'Mixto';

        // Sizes
        const sizesContainer = viewer.querySelector('#viewer-sizes');
        const variants = data.variants || [];
        sizesContainer.innerHTML = variants.map(v => `
            <button class="size-btn" 
                onclick="selectSize(this, ${v.id})" 
                data-stock="${v.stock}"
                ${v.stock === 0 ? 'disabled style="opacity:.3"' : ''}>
                ${v.size}
            </button>
        `).join('');

        // 360 Image rotation logic
        const canvas = viewer.querySelector('#viewer-canvas');
        const img = viewer.querySelector('#viewer-image');
        let currentFrame = 0;
        let isDragging = false, startX = 0, lastX = 0;
        const totalFrames = images.length;

        if (images.length > 0) {
            img.src = images[0];
        }

        function setFrame(f) {
            currentFrame = ((f % totalFrames) + totalFrames) % totalFrames;
            img.style.opacity = '0.7';
            img.src = images[currentFrame];
            img.onload = () => { img.style.opacity = '1'; };
        }

        canvas.onmousedown = e => { isDragging = true; startX = e.clientX; canvas.style.cursor = 'grabbing'; };
        canvas.onmousemove = e => {
            if (!isDragging) return;
            const diff = e.clientX - lastX;
            if (Math.abs(diff) > 20) {
                setFrame(currentFrame + (diff > 0 ? -1 : 1));
                lastX = e.clientX;
            }
            lastX = lastX || e.clientX;
        };
        canvas.onmouseup = () => { isDragging = false; canvas.style.cursor = 'ew-resize'; lastX = 0; };
        canvas.onmouseleave = () => { isDragging = false; lastX = 0; };

        // Touch support
        canvas.ontouchstart = e => { isDragging = true; startX = e.touches[0].clientX; lastX = startX; };
        canvas.ontouchmove = e => {
            if (!isDragging) return;
            const diff = e.touches[0].clientX - lastX;
            if (Math.abs(diff) > 15) {
                setFrame(currentFrame + (diff > 0 ? -1 : 1));
                lastX = e.touches[0].clientX;
            }
        };
        canvas.ontouchend = () => { isDragging = false; };

        // Auto-rotate hint animation
        if (totalFrames > 1) {
            let autoFrame = 0;
            const autoInterval = setInterval(() => {
                if (!isDragging) setFrame(++autoFrame);
                if (autoFrame >= 3) clearInterval(autoInterval);
            }, 300);
        }

        viewer.querySelector('#viewer-add-cart').onclick = () => {
            const selectedSize = viewer.querySelector('.size-btn.active');
            const variantId = selectedSize ? selectedSize.dataset.variantId : null;
            if (variants.length > 0 && !selectedSize) {
                showToast('Selecciona una talla', 'error');
                return;
            }
            addToCart(product.id, variantId);
            close360Viewer();
        };
    }

    window.close360Viewer = function() {
        const viewer = document.getElementById('viewer-360');
        if (viewer) viewer.classList.remove('active');
        document.body.style.overflow = '';
    };

    window.selectSize = function(btn, variantId) {
        document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        btn.dataset.variantId = variantId;
    };

    // Close viewer on overlay click
    const viewer360 = document.getElementById('viewer-360');
    if (viewer360) {
        viewer360.addEventListener('click', e => {
            if (e.target === viewer360) close360Viewer();
        });
    }

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
        const badge = document.querySelector('.cart-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }
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
