<?php
// ─── Database Configuration ───────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'shopflow');

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['error' => 'DB connection failed: ' . $conn->connect_error]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// ─── Install / seed the database ─────────────────────────────────────────────
function installDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    $conn->set_charset('utf8mb4');

    $conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db(DB_NAME);

    $conn->query("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        icon VARCHAR(50) DEFAULT 'ti-tag',
        color VARCHAR(20) DEFAULT '#378ADD',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        category_id INT,
        price DECIMAL(10,2) NOT NULL,
        stock INT DEFAULT 0,
        image VARCHAR(10) DEFAULT '📦',
        description TEXT,
        rating DECIMAL(3,1) DEFAULT 4.5,
        reviews INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(150) NOT NULL,
        customer_email VARCHAR(150) NOT NULL,
        customer_address TEXT NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        status ENUM('Pending','Processing','Shipped','Delivered','Cancelled') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        qty INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL
    )");

    // Seed only if empty
    $r = $conn->query("SELECT COUNT(*) as c FROM categories");
    if ($r->fetch_assoc()['c'] == 0) {
        $conn->query("INSERT INTO categories (name, icon, color) VALUES
            ('Electronics', 'ti-device-laptop', '#1D9E75'),
            ('Clothing',    'ti-shirt',          '#7F77DD'),
            ('Home & Garden','ti-home',           '#D85A30'),
            ('Books',       'ti-book',            '#BA7517')");

        $conn->query("INSERT INTO products (name, category_id, price, stock, image, description, rating, reviews) VALUES
            ('Wireless Noise-Cancelling Headphones', 1, 149.99, 24, '🎧', 'Premium audio with 30hr battery, active noise cancellation, and foldable design.', 4.8, 312),
            ('4K Ultra HD Smart TV — 55\"',           1, 499.99,  8, '📺', 'Crystal clear 4K display with built-in streaming apps and voice control.',        4.6, 188),
            ('Mechanical Keyboard — RGB',             1,  89.99, 45, '⌨️', 'Tactile blue switches, per-key RGB, aluminum body, N-key rollover.',               4.7,  94),
            ('Slim Fit Oxford Shirt',                 2,  39.99, 62, '👔', '100% cotton Oxford weave, button-down collar, machine washable.',                  4.4,  76),
            ('Running Sneakers — Pro V3',             2, 119.99, 17, '👟', 'Lightweight mesh upper, responsive foam midsole, grippy outsole.',                 4.9, 241),
            ('Bamboo Coffee Table',                   3, 219.99,  6, '🪑', 'Eco-friendly bamboo with tempered glass top. Easy assembly.',                      4.3,  38),
            ('Smart Planter with Self-Watering',      3,  34.99, 89, '🪴', 'Sensor-monitored soil moisture, app-connected with 2-week water reservoir.',       4.5,  55),
            ('The Design of Everyday Things',         4,  18.99,200, '📚', 'Don Norman classic on user-centered design. Paperback, 368 pages.',                4.9,1204)");

        $conn->query("INSERT INTO orders (customer_name, customer_email, customer_address, total, status, created_at) VALUES
            ('Kasun Perera',     'kasun@example.com', '12 Galle Rd, Colombo',    187.97, 'Delivered',  '2025-06-10 09:00:00'),
            ('Nimal Silva',      'nimal@example.com', '45 Kandy Rd, Kandy',      119.99, 'Shipped',    '2025-06-18 14:00:00'),
            ('Amara De Silva',   'amara@example.com', '78 Marine Dr, Galle',     579.97, 'Processing', '2025-06-22 11:00:00'),
            ('Priya Jayasinghe', 'priya@example.com', '33 Temple St, Negombo',   159.97, 'Pending',    '2025-06-25 16:00:00')");

        // seed order_items
        $conn->query("INSERT INTO order_items (order_id, product_id, qty, price) VALUES
            (1,1,1,149.99),(1,8,2,18.99),
            (2,5,1,119.99),
            (3,2,1,499.99),(3,4,2,39.99),
            (4,3,1,89.99),(4,7,2,34.99)");

        // default admin
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $conn->query("INSERT IGNORE INTO admin_users (username, password) VALUES ('admin', '$hash')");
    }

    $conn->close();
}

installDB();
?>
