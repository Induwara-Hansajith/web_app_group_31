# ShopFlow — E-Commerce App Setup Guide

## Project Structure

```
shopflow/
├── index.html          ← Customer storefront
├── css/
│   ├── store.css       ← Customer styles
│   └── admin.css       ← Admin panel styles
├── js/
│   ├── store.js        ← Customer JavaScript (cart, modals, API calls)
│   └── admin.js        ← Admin JavaScript (CRUD, dashboard)
├── php/
│   ├── db.php          ← Database config, connection, auto-install & seed
│   └── api.php         ← JSON API (all endpoints)
└── admin/
    └── index.php       ← Admin panel (PHP session-gated)
```

## Requirements

- PHP 7.4+ with MySQLi extension
- MySQL 5.7+ or MariaDB 10+
- Apache or Nginx with mod_rewrite / URL rewriting
- Recommended: XAMPP, WAMP, MAMP, or Laragon for local development

## Setup Steps

### 1. Place files
Copy the `shopflow/` folder into your web server's root directory:
- XAMPP: `C:/xampp/htdocs/shopflow/`
- MAMP:  `/Applications/MAMP/htdocs/shopflow/`
- Linux: `/var/www/html/shopflow/`

### 2. Configure database
Open `php/db.php` and update the constants if needed:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // your MySQL password
define('DB_NAME', 'shopflow');
```

### 3. Launch
Visit `http://localhost/shopflow/` in your browser.

The database and tables are **created automatically** on first load.
Sample data (categories, products, orders) is also seeded automatically.

### 4. Admin panel
Visit `http://localhost/shopflow/admin/`

Default credentials:
- **Username:** `admin`
- **Password:** `admin123`

## API Endpoints

All endpoints live in `php/api.php?action=<name>`

| Action | Method | Description |
|--------|--------|-------------|
| `get_categories` | GET | List all categories |
| `save_category` | POST | Create or update a category |
| `delete_category` | POST | Delete a category |
| `get_products` | GET | List products (supports `?category_id=` and `?search=`) |
| `get_product` | GET | Single product by `?id=` |
| `save_product` | POST | Create or update a product |
| `delete_product` | POST | Delete a product |
| `get_orders` | GET | List orders (supports `?status=`) |
| `get_order` | GET | Single order with items by `?id=` |
| `place_order` | POST | Place new order from cart |
| `update_order_status` | POST | Change order status (admin only) |
| `get_stats` | GET | Dashboard stats (admin only) |
| `admin_login` | POST | Authenticate admin |
| `admin_logout` | POST | Clear admin session |
| `admin_check` | GET | Check login state |

## Features

### Customer (index.html)
- Home page with hero, featured products, category grid
- Products page with category filter
- Product detail modal with rating, stock, description
- Cart sidebar with quantity controls + persistent localStorage
- Checkout with client-side validation
- Order confirmation page

### Admin (admin/index.php)
- Session-based login (PHP)
- Dashboard with revenue, order count, product count, deliveries
- Category CRUD (name, icon, color)
- Product CRUD with search, stock warnings, emoji icon
- Orders with status filter + status update modal

## Security Notes

This is a learning/demo project. For production use:
- Use prepared statements throughout (or PDO with bound params)
- Add CSRF protection on forms
- Hash + validate payment data server-side (never trust client)
- Restrict admin via `.htaccess` or server config
- Use HTTPS
- Remove the hint text from the login page
