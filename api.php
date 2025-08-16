<?php
// api.php - ক্যাটাগরি, প্রোডাক্ট, কুপন এবং অর্ডার এর সমস্ত ব্যাকএন্ড লজিক হ্যান্ডেল করে

// --- File Paths ---
$products_file_path = 'products.json';
$coupons_file_path = 'coupons.json';
$orders_file_path = 'orders.json';
$upload_dir = 'uploads/';

// --- Helper Functions ---
function get_data($file_path) {
    if (!file_exists($file_path)) file_put_contents($file_path, '[]');
    return json_decode(file_get_contents($file_path), true);
}

function save_data($file_path, $data) {
    file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

// --- Specific API Actions (GET requests) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    if ($_GET['action'] === 'get_orders_by_phone' && isset($_GET['phone'])) {
        $phone = trim($_GET['phone']);
        $all_orders = get_data($orders_file_path);
        $user_orders = array_filter($all_orders, fn($order) => $order['customer']['phone'] === $phone);
        header('Content-Type: application/json');
        echo json_encode(array_values($user_orders));
        exit;
    }
}


// --- Form Submission Actions (POST requests) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $json_data = null;
    
    // If action is not in POST, check for JSON payload
    if (!$action) {
        $json_data = json_decode(file_get_contents('php://input'), true);
        $action = $json_data['action'] ?? null;
    }
    
    if (!$action) { http_response_code(400); echo "Action not specified."; exit; }

    $redirect_url = 'admin.php';

    // ======== PRODUCT & CATEGORY ACTIONS ========
    if (in_array($action, ['add_category', 'delete_category', 'add_product', 'delete_product', 'edit_product'])) {
        $all_data = get_data($products_file_path);
        if ($action === 'add_category') { $all_data[] = ['name' => htmlspecialchars(trim($_POST['name'])), 'icon' => htmlspecialchars(trim($_POST['icon'])), 'products' => []]; }
        if ($action === 'delete_category') { $all_data = array_values(array_filter($all_data, fn($cat) => $cat['name'] !== $_POST['name'])); }
        if ($action === 'add_product' || $action === 'edit_product') {
            function handle_image_upload($file, $upload_dir) { if (isset($file) && $file['error'] === UPLOAD_ERR_OK) { $filename = uniqid() . '-' . basename($file['name']); $destination = $upload_dir . $filename; if (move_uploaded_file($file['tmp_name'], $destination)) { return $destination; } } return null; }
            function parse_pricing_data() { $p = []; if (!empty($_POST['durations'])) { for ($i = 0; $i < count($_POST['durations']); $i++) { $p[] = ['duration' => htmlspecialchars(trim($_POST['durations'][$i])), 'price' => (float)$_POST['duration_prices'][$i]]; } } else { $p[] = ['duration' => 'Default', 'price' => (float)$_POST['price']]; } return $p; }
            if ($action === 'add_product') { $image_path = handle_image_upload($_FILES['image'] ?? null, $upload_dir); if ($image_path) { $np = ['id' => time() . rand(100, 999), 'name' => htmlspecialchars(trim($_POST['name'])), 'description' => htmlspecialchars(trim($_POST['description'])), 'long_description' => htmlspecialchars(trim($_POST['long_description'] ?? '')), 'image' => $image_path, 'pricing' => parse_pricing_data(), 'stock_out' => $_POST['stock_out'] === 'true', 'featured' => isset($_POST['featured']), 'reviews' => []]; for ($i = 0; $i < count($all_data); $i++) { if ($all_data[$i]['name'] === $_POST['category_name']) { $all_data[$i]['products'][] = $np; break; } } } $redirect_url = 'admin.php?category=' . urlencode($_POST['category_name']); }
            if ($action === 'edit_product') { for ($i = 0; $i < count($all_data); $i++) { if ($all_data[$i]['name'] === $_POST['category_name']) { for ($j = 0; $j < count($all_data[$i]['products']); $j++) { if ($all_data[$i]['products'][$j]['id'] == $_POST['product_id']) { $cp = &$all_data[$i]['products'][$j]; if (isset($_POST['delete_image']) && !empty($cp['image']) && file_exists($cp['image'])) { unlink($cp['image']); $cp['image'] = ''; } $nip = handle_image_upload($_FILES['image'] ?? null, 'uploads/'); if ($nip) { if (!empty($cp['image']) && file_exists($cp['image'])) { unlink($cp['image']); } $cp['image'] = $nip; } $cp['name'] = htmlspecialchars(trim($_POST['name'])); $cp['description'] = htmlspecialchars(trim($_POST['description'])); $cp['long_description'] = htmlspecialchars(trim($_POST['long_description'] ?? '')); $cp['pricing'] = parse_pricing_data(); $cp['stock_out'] = $_POST['stock_out'] === 'true'; $cp['featured'] = isset($_POST['featured']); break 2; } } } } $redirect_url = 'admin.php?category=' . urlencode($_POST['category_name']); }
        }
        if ($action === 'delete_product') { for ($i = 0; $i < count($all_data); $i++) { if ($all_data[$i]['name'] === $_POST['category_name']) { foreach($all_data[$i]['products'] as $p) { if ($p['id'] == $_POST['product_id'] && !empty($p['image']) && file_exists($p['image'])) { unlink($p['image']); break; } } $all_data[$i]['products'] = array_values(array_filter($all_data[$i]['products'], fn($prod) => $prod['id'] != $_POST['product_id'])); break; } } $redirect_url = 'admin.php?category=' . urlencode($_POST['category_name']); }
        save_data($products_file_path, $all_data);
    }
    
    // ======== COUPON ACTIONS ========
    if (in_array($action, ['add_coupon', 'delete_coupon'])) {
        $all_coupons = get_data($coupons_file_path);
        if ($action === 'add_coupon') { $all_coupons[] = ['id' => time() . rand(100, 999), 'code' => strtoupper(htmlspecialchars(trim($_POST['code']))), 'discount_percentage' => (int)$_POST['discount_percentage'], 'is_active' => isset($_POST['is_active'])]; }
        if ($action === 'delete_coupon') { $all_coupons = array_values(array_filter($all_coupons, fn($c) => $c['id'] != $_POST['coupon_id'])); }
        save_data($coupons_file_path, $all_coupons);
    }

    // ======== REVIEW ACTIONS ========
    if ($action === 'add_review') {
        $review_data = $json_data['review'];
        $product_id = $review_data['productId'];
        $all_products = get_data($products_file_path);
        $product_found = false;

        for ($i = 0; $i < count($all_products); $i++) {
            for ($j = 0; $j < count($all_products[$i]['products']); $j++) {
                if ($all_products[$i]['products'][$j]['id'] == $product_id) {
                    if (!isset($all_products[$i]['products'][$j]['reviews'])) {
                        $all_products[$i]['products'][$j]['reviews'] = [];
                    }
                    $new_review = [
                        'id' => time() . '-' . rand(100, 999), // Unique ID for the review
                        'name' => htmlspecialchars($review_data['name']),
                        'rating' => (int)$review_data['rating'],
                        'comment' => htmlspecialchars($review_data['comment']),
                    ];
                    array_push($all_products[$i]['products'][$j]['reviews'], $new_review);
                    $product_found = true;
                    break 2;
                }
            }
        }
        
        if ($product_found) {
            save_data($products_file_path, $all_products);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Review added successfully!']);
        } else {
            header('Content-Type: application/json', true, 404);
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
        }
        exit;
    }

    if ($action === 'update_review_status') {
        $product_id = $_POST['product_id'];
        $review_id = $_POST['review_id'];
        $new_status = $_POST['new_status'];
        $all_products = get_data($products_file_path);

        if ($new_status === 'deleted') {
            for ($i = 0; $i < count($all_products); $i++) {
                for ($j = 0; $j < count($all_products[$i]['products']); $j++) {
                    if ($all_products[$i]['products'][$j]['id'] == $product_id) {
                        // Filter out the review to be deleted
                        $all_products[$i]['products'][$j]['reviews'] = array_values(
                            array_filter(
                                $all_products[$i]['products'][$j]['reviews'] ?? [],
                                fn($review) => $review['id'] !== $review_id
                            )
                        );
                        break 2;
                    }
                }
            }
            save_data($products_file_path, $all_products);
        }
        $redirect_url = 'admin.php?view=reviews';
    }


    // ======== ORDER ACTIONS ========
    if ($action === 'place_order') {
        $order_data = $json_data['order']; $all_orders = get_data($orders_file_path); $subtotal = 0; foreach($order_data['items'] as $item) { $subtotal += $item['pricing']['price'] * $item['quantity']; } $discount = 0; if (!empty($order_data['coupon'])) { $discount = $subtotal * ($order_data['coupon']['discount_percentage'] / 100); } $total = $subtotal - $discount;
        $new_order = ['order_id' => time(), 'order_date' => date('Y-m-d H:i:s'), 'customer' => $order_data['customerInfo'], 'payment' => $order_data['paymentInfo'], 'items' => $order_data['items'], 'coupon' => $order_data['coupon'], 'totals' => ['subtotal' => $subtotal, 'discount' => $discount, 'total' => $total,], 'status' => 'Pending',];
        $all_orders[] = $new_order; save_data($orders_file_path, $all_orders); header('Content-Type: application/json'); echo json_encode(['success' => true, 'message' => 'Order placed successfully!']); exit;
    }

    if ($action === 'update_order_status') {
        $order_id = $_POST['order_id']; $new_status = $_POST['new_status']; $all_orders = get_data($orders_file_path);
        foreach ($all_orders as &$order) { if ($order['order_id'] == $order_id) { $order['status'] = $new_status; break; } }
        save_data($orders_file_path, $all_orders); $redirect_url = 'admin.php?view=orders';
    }

    header('Location: ' . $redirect_url);
    exit;
}

http_response_code(403);
echo "Invalid Access";
?>