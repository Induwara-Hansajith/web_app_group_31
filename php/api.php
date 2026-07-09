<?php
// ─── api.php — JSON REST-style endpoint for ShopFlow ─────────────────────────
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once __DIR__ . '/db.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ─── Helper ───────────────────────────────────────────────────────────────────
function send($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function body() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function requireAdmin() {
    if (empty($_SESSION['admin'])) send(['error' => 'Unauthorized'], 401);
}

// ─── Router ───────────────────────────────────────────────────────────────────
switch ($action) {

    // ── Categories ────────────────────────────────────────────────────────────
    case 'get_categories':
        $res = $db->query("SELECT * FROM categories ORDER BY id");
        send(array_values($res->fetch_all(MYSQLI_ASSOC)));

    case 'save_category':
        requireAdmin();
        $d  = body();
        $id = intval($d['id'] ?? 0);
        $name  = $db->real_escape_string(trim($d['name']  ?? ''));
        $icon  = $db->real_escape_string(trim($d['icon']  ?? 'ti-tag'));
        $color = $db->real_escape_string(trim($d['color'] ?? '#378ADD'));
        if (!$name) send(['error' => 'Name required'], 400);
        if ($id) {
            $db->query("UPDATE categories SET name='$name',icon='$icon',color='$color' WHERE id=$id");
            send(['ok' => true, 'id' => $id]);
        } else {
            $db->query("INSERT INTO categories (name,icon,color) VALUES ('$name','$icon','$color')");
            send(['ok' => true, 'id' => $db->insert_id]);
        }

    case 'delete_category':
        requireAdmin();
        $id = intval(body()['id'] ?? 0);
        $db->query("DELETE FROM categories WHERE id=$id");
        send(['ok' => true]);

    // ── Products ──────────────────────────────────────────────────────────────
    case 'get_products':
        $where = '';
        if (!empty($_GET['category_id'])) {
            $cid   = intval($_GET['category_id']);
            $where = "WHERE p.category_id=$cid";
        }
        $search = trim($_GET['search'] ?? '');
        if ($search) {
            $s = $db->real_escape_string($search);
            $where = $where ? "$where AND p.name LIKE '%$s%'" : "WHERE p.name LIKE '%$s%'";
        }
        $res = $db->query("SELECT p.*, c.name AS category_name, c.color AS category_color
                           FROM products p LEFT JOIN categories c ON c.id=p.category_id
                           $where ORDER BY p.id");
        send(array_values($res->fetch_all(MYSQLI_ASSOC)));

    case 'get_product':
        $id  = intval($_GET['id'] ?? 0);
        $res = $db->query("SELECT p.*, c.name AS category_name
                           FROM products p LEFT JOIN categories c ON c.id=p.category_id
                           WHERE p.id=$id LIMIT 1");
        $row = $res->fetch_assoc();
        $row ? send($row) : send(['error' => 'Not found'], 404);

    case 'save_product':
        requireAdmin();
        $d    = body();
        $id   = intval($d['id'] ?? 0);
        $name = $db->real_escape_string(trim($d['name'] ?? ''));
        $cat  = intval($d['category_id'] ?? 0);
        $price= floatval($d['price'] ?? 0);
        $stock= intval($d['stock']  ?? 0);
        $img  = $db->real_escape_string($d['image'] ?? '📦');
        $desc = $db->real_escape_string($d['description'] ?? '');
        $rat  = floatval($d['rating'] ?? 4.5);
        $rev  = intval($d['reviews'] ?? 0);
        if (!$name || $price <= 0) send(['error' => 'Name and price required'], 400);
        if ($id) {
            $db->query("UPDATE products SET name='$name',category_id=$cat,price=$price,stock=$stock,
                        image='$img',description='$desc',rating=$rat,reviews=$rev WHERE id=$id");
            send(['ok' => true, 'id' => $id]);
        } else {
            $db->query("INSERT INTO products (name,category_id,price,stock,image,description,rating,reviews)
                        VALUES ('$name',$cat,$price,$stock,'$img','$desc',$rat,$rev)");
            send(['ok' => true, 'id' => $db->insert_id]);
        }

    case 'delete_product':
        requireAdmin();
        $id = intval(body()['id'] ?? 0);
        $db->query("DELETE FROM products WHERE id=$id");
        send(['ok' => true]);

    // ── Orders ────────────────────────────────────────────────────────────────
    case 'get_orders':
        $where = '';
        if (!empty($_GET['status'])) {
            $s = $db->real_escape_string($_GET['status']);
            $where = "WHERE o.status='$s'";
        }
        $res = $db->query("SELECT o.*,
                            GROUP_CONCAT(oi.product_id,':',oi.qty,':',p.name SEPARATOR '|') AS items_raw
                           FROM orders o
                           LEFT JOIN order_items oi ON oi.order_id=o.id
                           LEFT JOIN products p ON p.id=oi.product_id
                           $where GROUP BY o.id ORDER BY o.id DESC");
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $items = [];
            if ($row['items_raw']) {
                foreach (explode('|', $row['items_raw']) as $part) {
                    [$pid,$qty,$pname] = explode(':', $part, 3);
                    $items[] = ['product_id' => (int)$pid, 'qty' => (int)$qty, 'name' => $pname];
                }
            }
            $row['items'] = $items;
            unset($row['items_raw']);
            $rows[] = $row;
        }
        send($rows);

    case 'get_order':
        $id  = intval($_GET['id'] ?? 0);
        $res = $db->query("SELECT o.*, oi.product_id, oi.qty, oi.price AS item_price, p.name AS product_name, p.image
                           FROM orders o
                           LEFT JOIN order_items oi ON oi.order_id=o.id
                           LEFT JOIN products    p  ON p.id=oi.product_id
                           WHERE o.id=$id");
        $order = null; $items = [];
        while ($row = $res->fetch_assoc()) {
            if (!$order) {
                $order = ['id'=>$row['id'],'customer_name'=>$row['customer_name'],
                          'customer_email'=>$row['customer_email'],
                          'customer_address'=>$row['customer_address'],
                          'total'=>$row['total'],'status'=>$row['status'],
                          'created_at'=>$row['created_at']];
            }
            if ($row['product_id']) {
                $items[] = ['product_id'=>$row['product_id'],'qty'=>$row['qty'],
                            'price'=>$row['item_price'],'name'=>$row['product_name'],
                            'image'=>$row['image']];
            }
        }
        if (!$order) send(['error' => 'Not found'], 404);
        $order['items'] = $items;
        send($order);

    case 'place_order':
        $d      = body();
        $name   = $db->real_escape_string(trim($d['name']    ?? ''));
        $email  = $db->real_escape_string(trim($d['email']   ?? ''));
        $addr   = $db->real_escape_string(trim($d['address'] ?? ''));
        $items  = $d['items'] ?? [];
        if (!$name || !$email || !$addr || !$items) send(['error' => 'Missing fields'], 400);

        $total = 0;
        $rows  = [];
        foreach ($items as $item) {
            $pid = intval($item['product_id']);
            $qty = intval($item['qty']);
            $pr  = $db->query("SELECT price,stock FROM products WHERE id=$pid LIMIT 1")->fetch_assoc();
            if (!$pr) continue;
            $price  = floatval($pr['price']);
            $total += $price * $qty;
            $rows[] = "($pid,$qty,$price)";
        }

        $db->query("INSERT INTO orders (customer_name,customer_email,customer_address,total)
                    VALUES ('$name','$email','$addr',$total)");
        $oid = $db->insert_id;

        foreach ($items as $item) {
            $pid = intval($item['product_id']);
            $qty = intval($item['qty']);
            $pr  = $db->query("SELECT price,stock FROM products WHERE id=$pid LIMIT 1")->fetch_assoc();
            if (!$pr) continue;
            $price = floatval($pr['price']);
            $db->query("INSERT INTO order_items (order_id,product_id,qty,price) VALUES ($oid,$pid,$qty,$price)");
            $newStock = max(0, intval($pr['stock']) - $qty);
            $db->query("UPDATE products SET stock=$newStock WHERE id=$pid");
        }

        send(['ok' => true, 'order_id' => $oid, 'total' => $total]);

    case 'update_order_status':
        requireAdmin();
        $d      = body();
        $id     = intval($d['id'] ?? 0);
        $status = $db->real_escape_string($d['status'] ?? '');
        $valid  = ['Pending','Processing','Shipped','Delivered','Cancelled'];
        if (!in_array($status, $valid)) send(['error' => 'Invalid status'], 400);
        $db->query("UPDATE orders SET status='$status' WHERE id=$id");
        send(['ok' => true]);

    // ── Admin Auth ────────────────────────────────────────────────────────────
    case 'admin_login':
        $d    = body();
        $user = $db->real_escape_string($d['username'] ?? '');
        $pass = $d['password'] ?? '';
        $res  = $db->query("SELECT * FROM admin_users WHERE username='$user' LIMIT 1");
        $row  = $res->fetch_assoc();
        if ($row && password_verify($pass, $row['password'])) {
            $_SESSION['admin'] = $row['id'];
            send(['ok' => true, 'username' => $row['username']]);
        } else {
            send(['error' => 'Invalid credentials'], 401);
        }

    case 'admin_logout':
        session_destroy();
        send(['ok' => true]);

    case 'admin_check':
        send(['logged_in' => !empty($_SESSION['admin'])]);

    // ── Stats ─────────────────────────────────────────────────────────────────
    case 'get_stats':
        requireAdmin();
        $rev      = $db->query("SELECT COALESCE(SUM(total),0) AS v FROM orders")->fetch_assoc()['v'];
        $orders   = $db->query("SELECT COUNT(*) AS v FROM orders")->fetch_assoc()['v'];
        $products = $db->query("SELECT COUNT(*) AS v FROM products")->fetch_assoc()['v'];
        $delivered= $db->query("SELECT COUNT(*) AS v FROM orders WHERE status='Delivered'")->fetch_assoc()['v'];
        send(['revenue'=>(float)$rev,'orders'=>(int)$orders,'products'=>(int)$products,'delivered'=>(int)$delivered]);

    default:
        send(['error' => 'Unknown action'], 404);
}
$db->close();
?>
