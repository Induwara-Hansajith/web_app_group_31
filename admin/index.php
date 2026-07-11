<?php
session_start();
require_once '../php/db.php';

// Check session
$loggedIn = !empty($_SESSION['admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopFlow Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.0.0/tabler-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<?php if (!$loggedIn): ?>
<!-- ── Login Page ─────────────────────────────────────────────────────────── -->
<div class="login-page">
    <div class="login-card">
        <div class="login-icon">🔐</div>
        <h2 class="login-title">Admin login</h2>
        <p class="login-subtitle">ShopFlow control panel</p>
        <div id="login-error" class="login-error" style="display:none"></div>
        <div class="form-group" style="text-align:left">
            <label class="form-label">Username</label>
            <input class="form-input" id="login-user" placeholder="admin" autocomplete="username">
        </div>
        <div class="form-group" style="text-align:left">
            <label class="form-label">Password</label>
            <input class="form-input" id="login-pass" type="password" placeholder="••••••••" autocomplete="current-password">
        </div>
        <button class="btn-primary" id="login-btn" style="width:100%;padding:13px" onclick="doLogin()">Log in</button>
        <p class="login-hint">Hint: admin / admin123</p>
    </div>
</div>
<div id="login-form"></div><!-- marker for JS -->

<?php else: ?>
<!-- ── Admin Panel ────────────────────────────────────────────────────────── -->
<div class="admin-layout">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">⚡ ShopFlow <small>Admin</small></div>
        <nav class="sidebar-nav">
            <button class="sidebar-link active" data-page="dashboard">
                <i class="ti ti-layout-dashboard"></i> Dashboard
            </button>
            <button class="sidebar-link" data-page="categories">
                <i class="ti ti-category"></i> Categories
            </button>
            <button class="sidebar-link" data-page="products">
                <i class="ti ti-package"></i> Products
            </button>
            <button class="sidebar-link" data-page="orders">
                <i class="ti ti-clipboard-list"></i> Orders
            </button>
        </nav>
        <div class="sidebar-footer">
            <button class="sidebar-logout" onclick="doLogout()">
                <i class="ti ti-logout"></i> Logout
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">

        <!-- ── Dashboard ──────────────────────────────────────────────────── -->
        <div id="admin-page-dashboard" class="admin-page">
            <div class="admin-page-header">
                <h1 class="admin-page-title">Dashboard</h1>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-num" id="stat-revenue">—</div>
                    <div class="stat-label">Total revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-num" id="stat-orders">—</div>
                    <div class="stat-label">Total orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🛍️</div>
                    <div class="stat-num" id="stat-products">—</div>
                    <div class="stat-label">Products listed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-num" id="stat-delivered">—</div>
                    <div class="stat-label">Orders delivered</div>
                </div>
            </div>
            <div class="table-card">
                <div class="table-card-header">Recent orders</div>
                <table>
                    <thead><tr>
                        <th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th>
                    </tr></thead>
                    <tbody id="recent-orders-body">
                        <tr><td colspan="5"><div class="spinner"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Categories ─────────────────────────────────────────────────── -->
        <div id="admin-page-categories" class="admin-page" style="display:none">
            <div class="admin-page-header">
                <h1 class="admin-page-title">Categories</h1>
                <button class="btn-primary" onclick="openCatModal()">+ New category</button>
            </div>
            <div class="table-card">
                <table>
                    <thead><tr>
                        <th>Name</th><th>Icon</th><th>Color</th><th></th>
                    </tr></thead>
                    <tbody id="cats-tbody">
                        <tr><td colspan="4"><div class="spinner"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Products ───────────────────────────────────────────────────── -->
        <div id="admin-page-products" class="admin-page" style="display:none">
            <div class="admin-page-header">
                <h1 class="admin-page-title">Products</h1>
                <button class="btn-primary" onclick="openProdModal()">+ New product</button>
            </div>
            <input class="search-bar" id="prod-search" placeholder="Search products…">
            <div class="table-card">
                <table>
                    <thead><tr>
                        <th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Rating</th><th></th>
                    </tr></thead>
                    <tbody id="prods-tbody">
                        <tr><td colspan="6"><div class="spinner"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Orders ─────────────────────────────────────────────────────── -->
        <div id="admin-page-orders" class="admin-page" style="display:none">
            <div class="admin-page-header">
                <h1 class="admin-page-title">Orders</h1>
            </div>
            <div class="filter-chips">
                <?php foreach(['All','Pending','Processing','Shipped','Delivered','Cancelled'] as $s): ?>
                <button class="filter-chip order-filter-chip<?= $s==='All'?' active':'' ?>"
                    data-status="<?= $s ?>"
                    onclick="loadOrders('<?= $s ?>')">
                    <?= $s ?>
                </button>
                <?php endforeach; ?>
            </div>
            <div class="table-card">
                <table>
                    <thead><tr>
                        <th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th></th>
                    </tr></thead>
                    <tbody id="orders-tbody">
                        <tr><td colspan="6"><div class="spinner"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- ── Category Modal ─────────────────────────────────────────────────────── -->
<div id="modal-cat" class="modal-overlay" style="display:none">
    <div class="modal" style="max-width:420px">
        <div class="modal-header">
            <h2 class="modal-title" id="cat-modal-title">New category</h2>
            <button class="modal-close" onclick="closeModal('modal-cat')">✕</button>
        </div>
        <div class="form-group">
            <label class="form-label">Name</label>
            <input class="form-input" id="cat-name" placeholder="Electronics">
        </div>
        <div class="form-group">
            <label class="form-label">Icon class <small style="color:#888">(Tabler Icons)</small></label>
            <input class="form-input" id="cat-icon" placeholder="ti-device-laptop">
        </div>
        <div class="form-group">
            <label class="form-label">Color</label>
            <input type="color" id="cat-color" value="#378ADD">
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal('modal-cat')">Cancel</button>
            <button class="btn-primary" onclick="saveCat()">Save category</button>
        </div>
    </div>
</div>

<!-- ── Product Modal ──────────────────────────────────────────────────────── -->
<div id="modal-prod" class="modal-overlay" style="display:none">
    <div class="modal" style="max-width:560px">
        <div class="modal-header">
            <h2 class="modal-title" id="prod-modal-title">Add product</h2>
            <button class="modal-close" onclick="closeModal('modal-prod')">✕</button>
        </div>
        <div class="form-group">
            <label class="form-label">Product name</label>
            <input class="form-input" id="prod-name" placeholder="Wireless Headphones">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Price ($)</label>
                <input class="form-input" id="prod-price" type="number" step="0.01" placeholder="99.99">
            </div>
            <div class="form-group">
                <label class="form-label">Stock quantity</label>
                <input class="form-input" id="prod-stock" type="number" placeholder="100">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Category</label>
            <select class="form-select" id="prod-category"></select>
        </div>
        <div class="form-group">
            <label class="form-label">Image path <small style="color:#888">(relative to store root, e.g. images/mouse.jpg)</small></label>
            <input class="form-input" id="prod-image" placeholder="images/mouse.jpg" oninput="updateProdImgPreview()">
            <div class="prod-img-preview-wrap">
                <img id="prod-img-preview" class="prod-img-preview" src="../images/default.jpg"
                     onerror="this.src='../images/default.jpg'" alt="preview">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea class="form-textarea" id="prod-description" placeholder="Product description…"></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Rating (0–5)</label>
                <input class="form-input" id="prod-rating" type="number" step="0.1" min="0" max="5" placeholder="4.5">
            </div>
            <div class="form-group">
                <label class="form-label">Review count</label>
                <input class="form-input" id="prod-reviews" type="number" placeholder="0">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal('modal-prod')">Cancel</button>
            <button class="btn-primary" onclick="saveProd()">Save product</button>
        </div>
    </div>
</div>

<!-- ── Order Detail Modal ─────────────────────────────────────────────────── -->
<div id="modal-order" class="modal-overlay" style="display:none">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <h2 class="modal-title">Order details</h2>
            <button class="modal-close" onclick="closeModal('modal-order')">✕</button>
        </div>
        <div id="order-detail-body"></div>
    </div>
</div>

<?php endif; ?>

<!-- Toast -->
<div id="toast" class="toast" style="display:none"></div>

<script src="../js/admin.js"></script>
</body>
</html>