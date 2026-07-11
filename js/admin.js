// ─── admin.js — ShopFlow Admin Panel ─────────────────────────────────────────

const API = '../php/api.php';

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
function starsHTML(r) {
    const n = Math.round(parseFloat(r));
    return '<span class="stars" style="color:#EF9F27">' + '★'.repeat(n) + '☆'.repeat(5-n) + '</span>';
}

// ─── Image Helper ─────────────────────────────────────────────────────────────
// admin pages live in /admin/, so store images (at project root /images/) need '../' prefixed
function adminImgSrc(path) {
    if (!path) return '../images/default.jpg';
    return path.startsWith('../') ? path : '../' + path;
}
function prodThumbHTML(path, alt = '') {
    const safeAlt = (alt || '').replace(/"/g, '&quot;');
    return `<img src="${adminImgSrc(path)}" alt="${safeAlt}" class="prod-thumb"
        onerror="this.onerror=null;this.src='../images/default.jpg';">`;
}

// ─── Modal ────────────────────────────────────────────────────────────────────
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

// ─── Login ────────────────────────────────────────────────────────────────────
async function doLogin() {
    const username = document.getElementById('login-user').value.trim();
    const password = document.getElementById('login-pass').value;
    const err      = document.getElementById('login-error');
    const btn      = document.getElementById('login-btn');

    btn.disabled = true; btn.textContent = 'Logging in…';
    const res = await api('admin_login', 'POST', { username, password });
    btn.disabled = false; btn.textContent = 'Log in';

    if (res.error) {
        err.textContent = res.error;
        err.style.display = 'block';
    } else {
        location.reload();
    }
}

async function doLogout() {
    await api('admin_logout', 'POST');
    location.reload();
}

// ─── Nav / Pages ─────────────────────────────────────────────────────────────
function showAdminPage(name) {
    document.querySelectorAll('.admin-page').forEach(el => el.style.display = 'none');
    const pg = document.getElementById('admin-page-' + name);
    if (pg) pg.style.display = 'block';

    document.querySelectorAll('.sidebar-link').forEach(el => {
        el.classList.toggle('active', el.dataset.page === name);
    });
}

// ─── Dashboard ───────────────────────────────────────────────────────────────
async function loadDashboard() {
    showAdminPage('dashboard');
    const [stats, orders] = await Promise.all([
        api('get_stats'),
        api('get_orders')
    ]);

    document.getElementById('stat-revenue').textContent  = `$${parseFloat(stats.revenue).toFixed(2)}`;
    document.getElementById('stat-orders').textContent   = stats.orders;
    document.getElementById('stat-products').textContent = stats.products;
    document.getElementById('stat-delivered').textContent= stats.delivered;

    const tbody = document.getElementById('recent-orders-body');
    if (!orders.length) { tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:#888">No orders yet</td></tr>'; return; }
    tbody.innerHTML = orders.slice(0, 6).map(o => `
        <tr>
            <td><strong>#${o.id}</strong></td>
            <td>${o.customer_name}<br><small style="color:#888">${o.customer_email}</small></td>
            <td><strong>$${parseFloat(o.total).toFixed(2)}</strong></td>
            <td><span class="status-chip status-${o.status}">${o.status}</span></td>
            <td style="color:#888">${o.created_at.slice(0,10)}</td>
        </tr>`).join('');
}

// ─── Categories ──────────────────────────────────────────────────────────────
let editCatId = null;

async function loadCategories() {
    showAdminPage('categories');
    const cats = await api('get_categories');
    const tbody = document.getElementById('cats-tbody');
    if (!cats.length) { tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;color:#888">No categories yet</td></tr>'; return; }
    tbody.innerHTML = cats.map(c => `
        <tr>
            <td><strong>${c.name}</strong></td>
            <td><i class="ti ${c.icon}"></i></td>
            <td><span style="display:inline-block;width:20px;height:20px;border-radius:4px;background:${c.color};border:1px solid #ddd;vertical-align:middle"></span></td>
            <td class="td-right td-nowrap">
                <button class="btn-secondary" style="margin-right:8px" onclick="openCatModal(${c.id},'${c.name.replace(/'/g,"\\'")}','${c.icon}','${c.color}')">Edit</button>
                <button class="btn-danger" onclick="deleteCat(${c.id})">Delete</button>
            </td>
        </tr>`).join('');
}

function openCatModal(id = null, name = '', icon = 'ti-tag', color = '#378ADD') {
    editCatId = id;
    document.getElementById('cat-modal-title').textContent = id ? 'Edit category' : 'New category';
    document.getElementById('cat-name').value  = name;
    document.getElementById('cat-icon').value  = icon;
    document.getElementById('cat-color').value = color;
    openModal('modal-cat');
}

async function saveCat() {
    const name  = document.getElementById('cat-name').value.trim();
    const icon  = document.getElementById('cat-icon').value.trim();
    const color = document.getElementById('cat-color').value;
    if (!name) { showToast('Category name is required'); return; }
    const res = await api('save_category', 'POST', { id: editCatId, name, icon, color });
    if (res.error) { showToast('Error: ' + res.error); return; }
    closeModal('modal-cat');
    showToast(editCatId ? 'Category updated' : 'Category created');
    loadCategories();
}

async function deleteCat(id) {
    if (!confirm('Delete this category?')) return;
    await api('delete_category', 'POST', { id });
    showToast('Category deleted');
    loadCategories();
}

// ─── Products ─────────────────────────────────────────────────────────────────
let editProdId = null;
let allCats    = [];
let searchTimer;

async function loadProducts(search = '') {
    showAdminPage('products');
    const [prods, cats] = await Promise.all([
        api('get_products', 'GET', null, search ? { search } : {}),
        api('get_categories')
    ]);
    allCats = cats;

    const tbody = document.getElementById('prods-tbody');
    if (!prods.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:#888">No products found</td></tr>';
        return;
    }
    tbody.innerHTML = prods.map(p => {
        const stock = parseInt(p.stock);
        const stockClass = stock === 0 ? 'stock-out' : stock < 10 ? 'stock-low' : 'stock-ok';
        const stockLabel = stock === 0 ? 'Out of stock' : stock;
        return `
        <tr>
            <td style="display:flex;align-items:center;gap:10px">
                ${prodThumbHTML(p.image, p.name)}
                <strong>${p.name}</strong>
            </td>
            <td style="color:#666">${p.category_name || '—'}</td>
            <td><strong>$${parseFloat(p.price).toFixed(2)}</strong></td>
            <td class="${stockClass}">${stockLabel}</td>
            <td>${starsHTML(p.rating)} <span style="font-size:12px;color:#888">${parseFloat(p.rating).toFixed(1)}</span></td>
            <td class="td-right td-nowrap">
                <button class="btn-secondary" style="margin-right:8px" onclick="openProdModal(${p.id})">Edit</button>
                <button class="btn-danger" onclick="deleteProd(${p.id})">Delete</button>
            </td>
        </tr>`;
    }).join('');
}

function updateProdImgPreview() {
    const path = document.getElementById('prod-image').value.trim();
    const img  = document.getElementById('prod-img-preview');
    img.src = adminImgSrc(path);
}

async function openProdModal(id = null) {
    editProdId = id;
    const cats = allCats.length ? allCats : await api('get_categories');
    allCats = cats;

    const catOpts = cats.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    document.getElementById('prod-category').innerHTML = catOpts;

    if (id) {
        const p = await api('get_product', 'GET', null, { id });
        document.getElementById('prod-modal-title').textContent = 'Edit product';
        document.getElementById('prod-name').value        = p.name;
        document.getElementById('prod-category').value   = p.category_id;
        document.getElementById('prod-price').value      = p.price;
        document.getElementById('prod-stock').value      = p.stock;
        document.getElementById('prod-image').value      = p.image;
        document.getElementById('prod-description').value= p.description;
        document.getElementById('prod-rating').value     = p.rating;
        document.getElementById('prod-reviews').value    = p.reviews;
    } else {
        document.getElementById('prod-modal-title').textContent = 'Add product';
        document.getElementById('prod-name').value        = '';
        document.getElementById('prod-price').value      = '';
        document.getElementById('prod-stock').value      = '';
        document.getElementById('prod-image').value      = 'images/default.jpg';
        document.getElementById('prod-description').value= '';
        document.getElementById('prod-rating').value     = '4.5';
        document.getElementById('prod-reviews').value    = '0';
    }
    updateProdImgPreview();
    openModal('modal-prod');
}

async function saveProd() {
    const data = {
        id:          editProdId,
        name:        document.getElementById('prod-name').value.trim(),
        category_id: document.getElementById('prod-category').value,
        price:       document.getElementById('prod-price').value,
        stock:       document.getElementById('prod-stock').value,
        image:       document.getElementById('prod-image').value.trim() || 'images/default.jpg',
        description: document.getElementById('prod-description').value.trim(),
        rating:      document.getElementById('prod-rating').value,
        reviews:     document.getElementById('prod-reviews').value,
    };
    if (!data.name || !data.price) { showToast('Name and price are required'); return; }
    const res = await api('save_product', 'POST', data);
    if (res.error) { showToast('Error: ' + res.error); return; }
    closeModal('modal-prod');
    showToast(editProdId ? 'Product updated' : 'Product added');
    loadProducts();
}

async function deleteProd(id) {
    if (!confirm('Delete this product?')) return;
    await api('delete_product', 'POST', { id });
    showToast('Product deleted');
    loadProducts();
}

// ─── Orders ───────────────────────────────────────────────────────────────────
let orderFilter = 'All';
let currentOrderId = null;

async function loadOrders(status = 'All') {
    orderFilter = status;
    showAdminPage('orders');

    // Update filter chips
    document.querySelectorAll('.order-filter-chip').forEach(el => {
        el.classList.toggle('active', el.dataset.status === status);
    });

    const params = status !== 'All' ? { status } : {};
    const orders = await api('get_orders', 'GET', null, params);

    const tbody = document.getElementById('orders-tbody');
    if (!orders.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:#888">No orders found</td></tr>';
        return;
    }
    tbody.innerHTML = orders.map(o => `
        <tr>
            <td><strong>#${o.id}</strong></td>
            <td>${o.customer_name}<br><small style="color:#888">${o.customer_email}</small></td>
            <td><strong>$${parseFloat(o.total).toFixed(2)}</strong></td>
            <td><span class="status-chip status-${o.status}">${o.status}</span></td>
            <td style="color:#888">${o.created_at.slice(0,10)}</td>
            <td class="td-right">
                <button class="btn-secondary" onclick="openOrderModal(${o.id})">View</button>
            </td>
        </tr>`).join('');
}

async function openOrderModal(id) {
    currentOrderId = id;
    const order = await api('get_order', 'GET', null, { id });

    document.getElementById('order-detail-body').innerHTML = `
        <div style="margin-bottom:14px">
            <strong>${order.customer_name}</strong><br>
            <span style="font-size:13px;color:#888">${order.customer_email}</span><br>
            <span style="font-size:13px;color:#666;margin-top:4px;display:block">${order.customer_address}</span>
        </div>
        <div style="margin-bottom:12px;display:flex;align-items:center;gap:10px">
            <span class="status-chip status-${order.status}" id="order-current-status">${order.status}</span>
            <span style="font-size:12px;color:#888">placed ${order.created_at.slice(0,10)}</span>
        </div>
        <hr class="divider">
        ${order.items.map(i => `
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:14px;margin-bottom:8px">
                <span style="display:flex;align-items:center;gap:8px">${prodThumbHTML(i.image, i.name)} ${i.name} × ${i.qty}</span>
                <span style="font-weight:600">$${(parseFloat(i.price)*i.qty).toFixed(2)}</span>
            </div>`).join('')}
        <hr class="divider">
        <div style="display:flex;justify-content:space-between;font-weight:700;font-size:16px;margin-bottom:20px">
            <span>Total</span><span style="color:#185FA5">$${parseFloat(order.total).toFixed(2)}</span>
        </div>
        <div class="form-label">Update status</div>
        <div class="status-actions">
            ${['Pending','Processing','Shipped','Delivered','Cancelled'].map(s => `
            <button class="status-action-btn${order.status===s?' current':''}" onclick="setOrderStatus(${order.id},'${s}')">${s}</button>`).join('')}
        </div>`;

    openModal('modal-order');
}

async function setOrderStatus(id, status) {
    const res = await api('update_order_status', 'POST', { id, status });
    if (res.error) { showToast('Error: ' + res.error); return; }
    showToast(`Order #${id} marked as ${status}`);
    // Update UI in modal
    document.querySelectorAll('.status-action-btn').forEach(btn => {
        btn.classList.toggle('current', btn.textContent === status);
    });
    const chip = document.getElementById('order-current-status');
    if (chip) { chip.textContent = status; chip.className = `status-chip status-${status}`; }
    // Refresh table row
    loadOrders(orderFilter);
}

// ─── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    // Check if login form exists
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        document.getElementById('login-pass')?.addEventListener('keydown', e => {
            if (e.key === 'Enter') doLogin();
        });
        return; // Don't load dashboard stuff on login page
    }

    // Sidebar nav
    document.querySelectorAll('.sidebar-link[data-page]').forEach(el => {
        el.addEventListener('click', () => {
            const pg = el.dataset.page;
            if (pg === 'dashboard') loadDashboard();
            else if (pg === 'categories') loadCategories();
            else if (pg === 'products') loadProducts();
            else if (pg === 'orders') loadOrders();
        });
    });

    // Close modals on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) overlay.style.display = 'none';
        });
    });

    // Product search
    const searchEl = document.getElementById('prod-search');
    if (searchEl) {
        searchEl.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => loadProducts(searchEl.value), 350);
        });
    }

    // Start on dashboard
    loadDashboard();
});