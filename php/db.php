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
       image VARCHAR(255) DEFAULT 'images/default.jpg',
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
            ('Scientific Calculator', 1, 8000, 80, 'images/calculator.jpg', 'Multi-function scientific calculator approved for university exams.', 4.8, 492),
            ('A4 Spiral Notebook', 4, 350, 250, 'images/notebook.png', '200-page ruled notebook perfect for lectures and note-taking.', 4.6, 215),
            ('Blue Ballpoint Pen (Pack of 10)', 4, 500, 180, 'images/pen.jpg', 'Smooth writing pens suitable for daily academic use.', 4.5, 165),
            ('Mechanical Pencil Set', 4, 1230, 120, 'images/pencil.jpg', 'Includes 2 mechanical pencils with refill leads.', 4.7, 97),
            ('Sticky Notes Pack', 4, 325, 140, 'images/sticky_notes.jpg', 'Colorful sticky notes for reminders and studying.', 4.6, 84),
            ('16GB USB Flash Drive', 1, 1899, 65, 'images/usb16.jpg', 'USB 3.0 flash drive for storing assignments and projects.', 4.8, 212),
            ('Wireless Mouse', 1, 2560, 55, 'images/mouse.jpg', 'Ergonomic wireless mouse with adjustable DPI.', 4.7, 173),
            ('Laptop Backpack', 4, 3530, 40, 'images/backpack.png', 'Water-resistant backpack fits laptops up to 15.6 inches.', 4.8, 264),
            ('Laptop Cooling Pad', 1, 6500, 38, 'images/cooling_pad.png', 'Dual-fan cooling pad for gaming and study laptops.', 4.5, 116),
            ('Wireless Earbuds', 1, 5000, 50, 'images/earbuds.jpg', 'Bluetooth earbuds with charging case and noise isolation.', 4.7, 328),
            ('University Hoodie', 4, 3250, 45, 'images/hoodie.jpg', 'Comfortable university-branded hoodie made from premium cotton.', 4.8, 154),
            ('University T-Shirt', 4, 2800, 70, 'images/tshirt.jpg', 'Official university logo T-shirt.', 4.6, 102),
            ('Reusable Water Bottle', 4, 1390, 90, 'images/water_bottle.jpg', '750ml stainless steel insulated water bottle.', 4.8, 241),
            ('Canvas Tote Bag', 4, 990, 85, 'images/tote_bag.jpg', 'Eco-friendly reusable tote bag for books and groceries.', 4.5, 91),
            ('Desk Study Lamp', 4, 1640, 30, 'images/study_lamp.png', 'LED desk lamp with adjustable brightness and color temperature.', 4.7, 134)");

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
