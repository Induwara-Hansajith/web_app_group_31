// ─── store.js — ShopFlow Customer Frontend ────────────────────────────────────

const API = 'php/api.php';
let cart = JSON.parse(localStorage.getItem('sf_cart') || '[]');
let allProducts = [], allCategories = [], currentCat = null;

// ─── API Helper ───────────────────────────────────────────────────────────────
async function api(action, method = 'GET', body = null, params = {}) {
    const url = new URL(API, location.href);
    url.searchParams.set('action', action);
    Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch(url, opts);
    return res.json();
}

// ─── Cart Persistence ─────────────────────────────────────────────────────────
function saveCart() { localStorage.setItem('sf_cart', JSON.stringify(cart)); }

function addToCart(productId, productName) {
    const ex = cart.find(i => i.product_id === productId);
    if (ex) ex.qty++;
    else cart.push({ product_id: productId, qty: 1 });
    saveCart();
    updateCartBadge();
    showToast(`${productName} added to cart`);
}

function changeQty(productId, delta) {
    const item = cart.find(i => i.product_id === productId);
    if (!item) return;
    item.qty = Math.max(1, item.qty + delta);
    saveCart();
    renderCartItems();
}

function removeFromCart(productId) {
    cart = cart.filter(i => i.product_id !== productId);
    saveCart();
    renderCartItems();
    updateCartBadge();
}

function cartTotal() {
    return cart.reduce((sum, item) => {
        const p = allProducts.find(x => x.id == item.product_id);
        return sum + (p ? parseFloat(p.price) * item.qty : 0);
    }, 0);
}

function updateCartBadge() {
    const total = cart.reduce((s, i) => s + i.qty, 0);
    const badge = document.getElementById('cart-badge');
    if (!badge) return;
    badge.textContent = total;
    badge.style.display = total > 0 ? 'inline' : 'none';
}

// ─── Toast ────────────────────────────────────────────────────────────────────
let toastTimer;
function showToast(msg) {
    let t = document.getElementById('toast');
    if (!t) { t = document.createElement('div'); t.id = 'toast'; t.className = 'toast'; document.body.appendChild(t); }
    t.innerHTML = `<span class="toast-icon">✓</span>${msg}`;
    t.style.display = 'flex';
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { t.style.display = 'none'; }, 2600);
}

// ─── Stars ────────────────────────────────────────────────────────────────────
function starsHTML(rating) {
    const full = Math.round(parseFloat(rating));
    return '★'.repeat(full) + '☆'.repeat(5 - full);
}

// ─── Image Helper ─────────────────────────────────────────────────────────────
function productImgHTML(p) {
    const safeAlt = (p.name || '').replace(/"/g, '&quot;');
    return `<img src="${p.image}" alt="${safeAlt}" loading="lazy"
        onerror="this.onerror=null;this.src='images/default.jpg';">`;
}

// ─── Page Router ──────────────────────────────────────────────────────────────
function showPage(name) {
    document.querySelectorAll('.page-view').forEach(el => el.style.display = 'none');
    const pg = document.getElementById('page-' + name);
    if (pg) pg.style.display = 'block';
    document.querySelectorAll('.nav-link').forEach(el => {
        el.classList.toggle('active', el.dataset.page === name);
    });
    window.scrollTo(0, 0);
}

// ─── Render: Home ─────────────────────────────────────────────────────────────
function renderHome() {
    // Featured products (first 4)
    const featured = allProducts.slice(0, 4);
    document.getElementById('featured-grid').innerHTML = featured.map(p => productCardHTML(p)).join('');

    // Category cards
    document.getElementById('cat-grid-home').innerHTML = allCategories.map(c => `
        <div class="cat-card" onclick="filterByCategory(${c.id})" style="cursor:pointer">
            <i class="ti ${c.icon}" style="color:${c.color}"></i>
            <div class="cat-card-name">${c.name}</div>
            <div class="cat-card-count">${allProducts.filter(p => p.category_id == c.id).length} products</div>
        </div>`).join('');

    showPage('home');
}

// ─── Render: Products ─────────────────────────────────────────────────────────
function renderProducts() {
    // Category filter chips
    const chipsCont = document.getElementById('cat-chips');
    chipsCont.innerHTML = `<span class="cat-chip${currentCat === null ? ' active' : ''}" onclick="filterByCategory(null)">All Products</span>`
        + allCategories.map(c => `<span class="cat-chip${currentCat == c.id ? ' active' : ''}" onclick="filterByCategory(${c.id})">${c.name}</span>`).join('');

    // Filter
    const products = currentCat !== null
        ? allProducts.filter(p => p.category_id == currentCat)
        : allProducts;

    const grid = document.getElementById('products-grid');
    if (products.length === 0) {
        grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1"><div class="empty-icon">🔍</div><p>No products in this category.</p></div>`;
    } else {
        grid.innerHTML = products.map(p => productCardHTML(p)).join('');
    }

    showPage('products');
}

function filterByCategory(catId) {
    currentCat = catId;
    renderProducts();
}

// ─── Product Card HTML ────────────────────────────────────────────────────────
function productCardHTML(p) {
    const inStock = parseInt(p.stock) > 0;
    return `
    <div class="product-card">
        <div class="product-card-img" onclick="openProductDetail(${p.id})">${productImgHTML(p)}</div>
        <div class="product-card-body">
            <div class="product-cat-label">${p.category_name || ''}</div>
            <div class="product-name" onclick="openProductDetail(${p.id})">${p.name}</div>
            <div class="rating-meta">
                <span class="stars">${starsHTML(p.rating)}</span>
                <span>${parseFloat(p.rating).toFixed(1)} (${p.reviews})</span>
            </div>
            <div class="product-footer">
                <span class="product-price">$${parseFloat(p.price).toFixed(2)}</span>
                ${inStock
                    ? `<button class="btn-sm" onclick="addToCart(${p.id}, '${p.name.replace(/'/g,"\\'")}')">Add to cart</button>`
                    : `<span class="out-of-stock">Out of stock</span>`}
            </div>
        </div>
    </div>`;
}

// ─── Product Detail Modal ─────────────────────────────────────────────────────
async function openProductDetail(productId) {
    const p = allProducts.find(x => x.id == productId) || await api('get_product', 'GET', null, { id: productId });
    const inStock = parseInt(p.stock) > 0;
    document.getElementById('product-detail-body').innerHTML = `
        <div class="detail-img">${productImgHTML(p)}</div>
        <div style="margin-top:16px">
            <div class="detail-cat">${p.category_name || ''}</div>
            <div class="detail-name">${p.name}</div>
            <div class="rating-meta" style="margin-bottom:14px">
                <span class="stars">${starsHTML(p.rating)}</span>
                <span>${parseFloat(p.rating).toFixed(1)} · ${p.reviews} reviews</span>
            </div>
            <p class="detail-desc">${p.description}</p>
            <hr class="divider">
            <div style="display:flex;align-items:center;justify-content:space-between">
                <div>
                    <div class="detail-price">$${parseFloat(p.price).toFixed(2)}</div>
                    <div class="detail-stock">${p.stock} in stock</div>
                </div>
                ${inStock
                    ? `<button class="btn-primary blue" style="padding:12px 28px;font-size:15px"
                        onclick="addToCart(${p.id},'${p.name.replace(/'/g,"\\'")}');closeModal('modal-product-detail')">
                        Add to cart</button>`
                    : `<span class="out-of-stock" style="font-size:14px">Out of stock</span>`}
            </div>
        </div>`;
    openModal('modal-product-detail');
}

// ─── Cart Sidebar ─────────────────────────────────────────────────────────────
function openCart() {
    renderCartItems();
    document.getElementById('cart-overlay').style.display = 'block';
    document.getElementById('cart-sidebar').style.display = 'flex';
}

function closeCart() {
    document.getElementById('cart-overlay').style.display = 'none';
    document.getElementById('cart-sidebar').style.display = 'none';
}

function renderCartItems() {
    const cont = document.getElementById('cart-items');
    const footer = document.getElementById('cart-footer');
    if (cart.length === 0) {
        cont.innerHTML = `<div class="empty-state"><div class="empty-icon">🛒</div><p>Your cart is empty</p></div>`;
        footer.style.display = 'none';
        return;
    }
    cont.innerHTML = cart.map(item => {
        const p = allProducts.find(x => x.id == item.product_id);
        if (!p) return '';
        return `
        <div class="cart-item">
            <div class="cart-item-img">${productImgHTML(p)}</div>
            <div style="flex:1">
                <div class="cart-item-name">${p.name}</div>
                <div class="cart-item-price">$${parseFloat(p.price).toFixed(2)}</div>
                <div class="qty-controls">
                    <button class="qty-btn" onclick="changeQty(${p.id},-1)">−</button>
                    <span class="qty-val">${item.qty}</span>
                    <button class="qty-btn" onclick="changeQty(${p.id},1)">+</button>
                    <button class="remove-btn" onclick="removeFromCart(${p.id})">Remove</button>
                </div>
            </div>
        </div>`;
    }).join('');
    document.getElementById('cart-total').textContent = `$${cartTotal().toFixed(2)}`;
    footer.style.display = 'block';
    updateCartBadge();
}

// ─── Checkout ─────────────────────────────────────────────────────────────────
function openCheckout() {
    closeCart();
    openModal('modal-checkout');
    document.getElementById('checkout-total').textContent = `$${cartTotal().toFixed(2)}`;
}

async function submitCheckout() {
    const get = id => document.getElementById(id).value.trim();
    const errors = {};
    const name  = get('co-name');
    const email = get('co-email');
    const addr  = get('co-address');
    const card  = get('co-card').replace(/\s/g, '');
    const exp   = get('co-exp');
    const cvv   = get('co-cvv');

    if (!name)  errors['co-name']    = 'Required';
    if (!email.includes('@')) errors['co-email'] = 'Valid email required';
    if (!addr)  errors['co-address'] = 'Required';
    if (card.length < 16) errors['co-card']  = '16-digit card number required';
    if (!exp.match(/^\d{2}\/\d{2}$/)) errors['co-exp'] = 'Use MM/YY format';
    if (cvv.length < 3)   errors['co-cvv']   = '3-4 digits required';

    ['co-name','co-email','co-address','co-card','co-exp','co-cvv'].forEach(id => {
        const inp = document.getElementById(id);
        const err = document.getElementById(id + '-err');
        if (errors[id]) {
            inp.classList.add('error');
            if (err) { err.textContent = errors[id]; err.style.display = 'block'; }
        } else {
            inp.classList.remove('error');
            if (err) err.style.display = 'none';
        }
    });

    if (Object.keys(errors).length) return;

    const btn = document.getElementById('checkout-submit');
    btn.disabled = true; btn.textContent = 'Placing order…';

    const res = await api('place_order', 'POST', { name, email, address: addr, items: cart });

    btn.disabled = false; btn.textContent = 'Place order';

    if (res.error) { showToast('Error: ' + res.error); return; }

    cart = []; saveCart(); updateCartBadge();
    closeModal('modal-checkout');
    renderConfirmation(res.order_id, email, res.total);
}

function renderConfirmation(orderId, email, total) {
    document.getElementById('confirm-body').innerHTML = `
        <div class="confirm-page">
            <div class="confirm-emoji">🎉</div>
            <div class="confirm-title">Order confirmed!</div>
            <div class="confirm-subtitle">Order #${orderId} has been placed.<br>A confirmation was sent to ${email}.</div>
            <div class="order-summary-card">
                <div style="font-weight:700;margin-bottom:14px">Order total</div>
                <div class="summary-row summary-total">
                    <span>Total paid</span>
                    <span style="color:#185FA5">$${parseFloat(total).toFixed(2)}</span>
                </div>
            </div>
            <button class="btn-primary" onclick="showPage('home');loadData()">Continue shopping</button>
        </div>`;
    showPage('confirm');
}

// ─── Modal Helpers ────────────────────────────────────────────────────────────
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

// ─── About & Contact ──────────────────────────────────────────────────────────
async function submitContact() {
    const name    = document.getElementById('cnt-name').value.trim();
    const email   = document.getElementById('cnt-email').value.trim();
    const message = document.getElementById('cnt-message').value.trim();
    if (!name || !email || !message) { showToast('Please fill in all fields'); return; }
    document.getElementById('cnt-name').value = '';
    document.getElementById('cnt-email').value = '';
    document.getElementById('cnt-message').value = '';
    showToast('Message sent! We\'ll get back to you within 24 hours.');
}

// ─── Load Data ────────────────────────────────────────────────────────────────
async function loadData() {
    const [cats, prods] = await Promise.all([
        api('get_categories'),
        api('get_products')
    ]);
    allCategories = cats || [];
    allProducts   = prods || [];
    renderHome();
    updateCartBadge();
}

// ─── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Nav clicks
    document.querySelectorAll('[data-page]').forEach(el => {
        el.addEventListener('click', () => {
            const pg = el.dataset.page;
            if (pg === 'products') renderProducts();
            else if (pg === 'home') renderHome();
            else showPage(pg);
        });
    });

    // Close modals on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) overlay.style.display = 'none';
        });
    });

    loadData();
});