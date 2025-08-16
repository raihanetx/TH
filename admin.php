<?php
// admin.php - ক্যাটাগরি, প্রোডাক্ট, কুপন এবং অর্ডার ম্যানেজমেন্ট ইন্টারফেস

// --- Load All Data ---
$products_file_path = 'products.json';
if (!file_exists($products_file_path)) file_put_contents($products_file_path, '[]');
$all_products_data = json_decode(file_get_contents($products_file_path), true);

$coupons_file_path = 'coupons.json';
if (!file_exists($coupons_file_path)) file_put_contents($coupons_file_path, '[]');
$all_coupons_data = json_decode(file_get_contents($coupons_file_path), true);

$orders_file_path = 'orders.json';
if (!file_exists($orders_file_path)) file_put_contents($orders_file_path, '[]');
$all_orders_data_raw = json_decode(file_get_contents($orders_file_path), true);
usort($all_orders_data_raw, fn($a, $b) => $b['order_id'] <=> $a['order_id']);

// --- Helper function to calculate stats for a given period ---
function calculate_stats($orders, $days = null) {
    $filtered_orders = $orders;
    if ($days !== null) {
        $cutoff_date = new DateTime();
        $cutoff_date->modify("-{$days} days");
        $filtered_orders = array_filter($orders, function ($order) use ($cutoff_date) {
            $order_date = new DateTime($order['order_date']);
            return $order_date >= $cutoff_date;
        });
    }

    $stats = [
        'total_revenue' => 0,
        'total_orders' => count($filtered_orders),
        'pending_orders' => 0,
        'confirmed_orders' => 0,
        'cancelled_orders' => 0,
    ];

    foreach ($filtered_orders as $order) {
        if ($order['status'] === 'Confirmed') {
            $stats['total_revenue'] += $order['totals']['total'];
            $stats['confirmed_orders']++;
        } elseif ($order['status'] === 'Pending') {
            $stats['pending_orders']++;
        } elseif ($order['status'] === 'Cancelled') {
            $stats['cancelled_orders']++;
        }
    }
    return $stats;
}

// Pre-calculate stats for different periods
$stats_today = calculate_stats($all_orders_data_raw, 0);
$stats_7_days = calculate_stats($all_orders_data_raw, 7);
$stats_30_days = calculate_stats($all_orders_data_raw, 30);
$stats_6_months = calculate_stats($all_orders_data_raw, 180);
$stats_all_time = calculate_stats($all_orders_data_raw);


// --- Logic for displaying specific views ---
$category_to_manage = null;
if (isset($_GET['category'])) {
    foreach ($all_products_data as $category) {
        if ($category['name'] === $_GET['category']) {
            $category_to_manage = $category;
            break;
        }
    }
}

// Consolidate all reviews
$all_reviews = [];
if (!empty($all_products_data)) {
    foreach ($all_products_data as $category) {
        if (isset($category['products']) && is_array($category['products'])) {
            foreach ($category['products'] as $product) {
                if (isset($product['reviews']) && is_array($product['reviews'])) {
                    foreach ($product['reviews'] as $review) {
                        $review['product_id'] = $product['id'];
                        $review['product_name'] = $product['name'];
                        $all_reviews[] = $review;
                    }
                }
            }
        }
    }
}
usort($all_reviews, fn($a, $b) => strcmp($b['id'], $a['id']));

$current_view = $_GET['view'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root { --primary-color: #8c52ff; --primary-color-darker: #7444d9; }
        .form-input, .form-select, .form-textarea { width: 100%; border-radius: 0.5rem; border: 1px solid #d1d5db; padding: 0.75rem; transition: all 0.2s ease-in-out; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: var(--primary-color); box-shadow: 0 0 0 2px #e5d9ff; outline: none; }
        .btn { padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: bold; transition: background-color 0.3s; }
        .btn-primary { background-color: var(--primary-color); color: white; } .btn-primary:hover { background-color: var(--primary-color-darker); }
        .btn-secondary { background-color: #4b5563; color: white; } .btn-secondary:hover { background-color: #1f2937; }
        .btn-danger { background-color: #ef4444; color: white; } .btn-danger:hover { background-color: #dc2626; }
        .btn-success { background-color: #16a34a; color: white; } .btn-success:hover { background-color: #15803d; }
        .tab { padding: 0.75rem 1.5rem; font-weight: 600; color: #4b5563; border-bottom: 3px solid transparent; }
        .tab-active { color: var(--primary-color); border-bottom-color: var(--primary-color); }
        .hidden { display: none; }
        .stats-filter-btn { padding: 0.5rem 1rem; border-radius: 9999px; font-weight: 500; transition: all 0.2s; border: 1px solid transparent; }
        .stats-filter-btn.active { background-color: var(--primary-color); color: white; }
        .stats-filter-btn:not(.active) { background-color: #f3f4f6; color: #374151; }
        .stats-filter-btn:not(.active):hover { background-color: #e5e7eb; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto p-4 md:p-8 max-w-7xl">
        
        <?php if ($category_to_manage !== null): ?>
            <!-- Product Management View -->
            <a href="admin.php?view=dashboard" class="inline-block mb-6 text-gray-600 font-semibold hover:text-[var(--primary-color)]">&larr; Back to Dashboard</a>
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Managing Products in "<?= htmlspecialchars($category_to_manage['name']) ?>"</h1>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-2xl font-bold text-gray-700 mb-4 pb-2 border-b">Add New Product</h2>
                    <form action="api.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="add_product">
                        <input type="hidden" name="category_name" value="<?= htmlspecialchars($category_to_manage['name']) ?>">
                        <div><label class="block mb-1 font-medium">1. Product Name</label><input type="text" name="name" class="form-input" required></div>
                        <div><label class="block mb-1 font-medium">2. Short Description</label><textarea name="description" class="form-textarea" rows="3" required></textarea></div>
                        <div><label class="block mb-1 font-medium">3. Long Description</label><textarea name="long_description" class="form-textarea" rows="5"></textarea></div>
                        <div><label class="block mb-1 font-medium">4. Pricing</label><select id="pricing-type" class="form-select"><option value="single">Single Price</option><option value="multiple">Multiple Durations</option></select></div>
                        <div id="single-price-container"><label class="block mb-1 font-medium">Price</label><input type="number" name="price" step="0.01" class="form-input" value="0.00"></div>
                        <div id="multiple-pricing-container" class="space-y-2 hidden"><label class="block font-medium">Durations & Prices</label><div id="duration-fields"></div><button type="button" id="add-duration-btn" class="btn btn-secondary text-sm">Add Duration</button></div>
                        <div><label class="block mb-1 font-medium">5. Product Image</label><input type="file" name="image" class="form-input" accept="image/*" required></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block mb-1 font-medium">6. Stock Status</label><select name="stock_out" class="form-select"><option value="false">In Stock</option><option value="true">Out of Stock</option></select></div>
                            <div><label class="block mb-1 font-medium">7. Featured</label><div class="flex items-center gap-2 mt-2"><input type="checkbox" name="featured" id="featured" value="true"><label for="featured">Mark as featured?</label></div></div>
                        </div>
                        <button type="submit" class="btn btn-primary w-full">Add Product</button>
                    </form>
                </div>

                <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-2xl font-bold text-gray-700 mb-4 pb-2 border-b">Existing Products</h2>
                    <div class="space-y-4">
                        <?php if (empty($category_to_manage['products'])): ?>
                            <p class="text-gray-500 text-center py-10">No products found in this category.</p>
                        <?php else: ?>
                            <?php foreach ($category_to_manage['products'] as $product): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border flex-wrap gap-4">
                                <div class="flex items-center gap-4">
                                    <img src="<?= htmlspecialchars($product['image'] ?? 'placeholder.png') ?>" class="w-16 h-16 object-cover rounded-md bg-gray-200">
                                    <div>
                                        <p class="font-bold text-lg"><?= htmlspecialchars($product['name']) ?></p>
                                        <p class="text-sm text-gray-600 font-semibold text-[var(--primary-color)]">$<?= number_format($product['pricing'][0]['price'], 2) ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="edit_product.php?category=<?= urlencode($category_to_manage['name']) ?>&id=<?= $product['id'] ?>" class="btn btn-secondary text-sm">Edit</a>
                                    <form action="api.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="action" value="delete_product">
                                        <input type="hidden" name="category_name" value="<?= htmlspecialchars($category_to_manage['name']) ?>">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <button type="submit" class="btn btn-danger text-sm">Delete</button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Main Dashboard View -->
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Admin Dashboard</h1>
            <div class="bg-white rounded-lg shadow-md">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex gap-6 px-6 overflow-x-auto">
                        <a href="admin.php?view=dashboard" class="tab flex-shrink-0 <?= $current_view === 'dashboard' ? 'tab-active' : '' ?>">Dashboard</a>
                        <a href="admin.php?view=orders" class="tab flex-shrink-0 <?= $current_view === 'orders' ? 'tab-active' : '' ?>">Order Management</a>
                        <a href="admin.php?view=reviews" class="tab flex-shrink-0 <?= $current_view === 'reviews' ? 'tab-active' : '' ?>">Review Management <span class="ml-2 bg-purple-500 text-white text-xs font-bold rounded-full px-2 py-1"><?= count($all_reviews) ?></span></a>
                    </nav>
                </div>
                <!-- Dashboard (Category & Coupon) View -->
                <div id="view-dashboard" style="<?= $current_view === 'dashboard' ? '' : 'display:none;' ?>" class="p-6">
                    <div class="mb-12"><h2 class="text-2xl font-bold text-gray-700 mb-4 pb-2 border-b">Manage Categories</h2><div class="grid grid-cols-1 md:grid-cols-2 gap-8"><div class="bg-white p-6 rounded-lg shadow-md border"><h3 class="text-xl font-semibold mb-4">Add New Category</h3><form action="api.php" method="POST" class="space-y-4"><input type="hidden" name="action" value="add_category"><div><label class="block mb-1 font-medium">Category Name</label><input type="text" name="name" class="form-input" required></div><div><label class="block mb-1 font-medium">Font Awesome Icon Class</label><input type="text" name="icon" class="form-input" placeholder="e.g., fas fa-book-open" required></div><div><button type="submit" class="btn btn-primary">Add Category</button></div></form></div><div class="bg-white p-6 rounded-lg shadow-md border"><h3 class="text-xl font-semibold mb-4">Existing Categories</h3><div class="space-y-3 max-h-96 overflow-y-auto"><?php foreach ($all_products_data as $category): ?><div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border flex-wrap"><div class="flex items-center gap-4"><i class="<?= htmlspecialchars($category['icon']) ?> text-xl w-8 text-center text-[var(--primary-color)]"></i><span class="font-semibold text-lg"><?= htmlspecialchars($category['name']) ?></span></div><div class="flex items-center gap-2 mt-2 md:mt-0"><a href="admin.php?category=<?= urlencode($category['name']) ?>" class="btn btn-secondary text-sm">Manage Products (<?= count($category['products'] ?? []) ?>)</a><form action="api.php" method="POST" onsubmit="return confirm('Delete this category and all its products?');"><input type="hidden" name="action" value="delete_category"><input type="hidden" name="name" value="<?= htmlspecialchars($category['name']) ?>"><button type="submit" class="btn btn-danger text-sm">Delete</button></form></div></div><?php endforeach; ?></div></div></div></div>
                    <div><h2 class="text-2xl font-bold text-gray-700 mb-4 pb-2 border-b">Manage Coupons</h2><div class="grid grid-cols-1 md:grid-cols-2 gap-8"><div class="bg-white p-6 rounded-lg shadow-md border"><h3 class="text-xl font-semibold mb-4">Add New Coupon</h3><form action="api.php" method="POST" class="space-y-4"><input type="hidden" name="action" value="add_coupon"><div><label class="block mb-1 font-medium">Coupon Code</label><input type="text" name="code" class="form-input uppercase" placeholder="e.g., SALE20" required></div><div><label class="block mb-1 font-medium">Discount Percentage (%)</label><input type="number" name="discount_percentage" class="form-input" placeholder="e.g., 20" required min="1" max="100"></div><div class="flex items-center gap-2"><input type="checkbox" name="is_active" id="is_active" value="true" checked><label for="is_active">Activate Coupon</label></div><div><button type="submit" class="btn btn-primary">Add Coupon</button></div></form></div><div class="bg-white p-6 rounded-lg shadow-md border"><h3 class="text-xl font-semibold mb-4">Existing Coupons</h3><div class="space-y-3 max-h-96 overflow-y-auto"><?php if (empty($all_coupons_data)): ?><p class="text-gray-500">No coupons found.</p><?php else: ?><?php foreach ($all_coupons_data as $coupon): ?><div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border flex-wrap"><div><p class="font-bold text-lg uppercase"><?= htmlspecialchars($coupon['code']) ?></p><p class="text-sm text-gray-600"><?= htmlspecialchars($coupon['discount_percentage']) ?>% Discount</p></div><div class="flex items-center gap-2"><span class="text-sm font-bold <?= $coupon['is_active'] ? 'text-green-600' : 'text-red-600' ?>"><?= $coupon['is_active'] ? 'Active' : 'Inactive' ?></span><form action="api.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this coupon?');"><input type="hidden" name="action" value="delete_coupon"><input type="hidden" name="coupon_id" value="<?= $coupon['id'] ?>"><button type="submit" class="btn btn-danger text-sm">Delete</button></form></div></div><?php endforeach; ?><?php endif; ?></div></div></div></div>
                </div>
                <!-- Order Management View -->
                <div id="view-orders" style="<?= $current_view === 'orders' ? '' : 'display:none;' ?>" class="p-6">
                    <!-- Statistics Section -->
                    <div class="mb-6">
                        <div class="flex flex-wrap gap-2 mb-4" id="stats-filter-container">
                            <button class="stats-filter-btn" data-period="today">Today</button>
                            <button class="stats-filter-btn" data-period="7days">Last 7 Days</button>
                            <button class="stats-filter-btn" data-period="30days">Last 30 Days</button>
                            <button class="stats-filter-btn" data-period="6months">Last 6 Months</button>
                            <button class="stats-filter-btn active" data-period="all">All Time</button>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4" id="stats-display-container">
                            <!-- Stats will be loaded here by JS -->
                        </div>
                    </div>
                     <div class="mb-4">
                        <input type="text" id="order-search-input" class="form-input" placeholder="Search by customer name...">
                     </div>
                     <?php if(empty($all_orders_data_raw)): ?><p class="text-gray-500 text-center py-10">No orders have been placed yet.</p><?php else: ?>
                        <div id="order-list-container" class="space-y-6">
                            <?php foreach($all_orders_data_raw as $order): ?>
                                <div class="order-card-wrapper" data-customer-name="<?= strtolower(htmlspecialchars($order['customer']['name'])) ?>">
                                    <div class="bg-white border rounded-lg shadow-sm">
                                        <div class="p-4 border-b flex justify-between items-center flex-wrap gap-4">
                                            <div><h3 class="font-bold text-lg">Order #<?= htmlspecialchars($order['order_id']) ?></h3><p class="text-sm text-gray-500"><?= htmlspecialchars($order['order_date']) ?></p></div>
                                            <div><?php $status_class = ''; switch ($order['status']) { case 'Confirmed': $status_class = 'bg-green-100 text-green-800'; break; case 'Cancelled': $status_class = 'bg-red-100 text-red-800'; break; default: $status_class = 'bg-yellow-100 text-yellow-800'; break; }?><span class="font-bold py-1 px-3 rounded-full text-sm <?= $status_class ?>"><?= htmlspecialchars($order['status']) ?></span></div>
                                        </div>
                                        <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-6">
                                            <div><h4 class="font-semibold mb-2">Customer & Payment</h4><p><strong>Name:</strong> <?= htmlspecialchars($order['customer']['name']) ?></p><p><strong>Phone:</strong> <?= htmlspecialchars($order['customer']['phone']) ?></p><p><strong>Email:</strong> <?= htmlspecialchars($order['customer']['email']) ?></p><hr class="my-2"><p><strong>Method:</strong> <?= htmlspecialchars($order['payment']['method']) ?></p><p><strong>TrxID:</strong> <?= htmlspecialchars($order['payment']['trx_id']) ?></p></div>
                                            <div><h4 class="font-semibold mb-2">Items Ordered</h4><?php foreach($order['items'] as $item): ?><div class="mb-1 text-sm"><?= $item['quantity'] ?>x <?= htmlspecialchars($item['name']) ?> (<?= htmlspecialchars($item['pricing']['duration']) ?>)</div><?php endforeach; ?></div>
                                            <div><h4 class="font-semibold mb-2">Summary</h4><p><strong>Subtotal:</strong> $<?= number_format($order['totals']['subtotal'], 2) ?></p><?php if($order['totals']['discount'] > 0): ?><p class="text-green-600"><strong>Discount (<?= htmlspecialchars($order['coupon']['code']) ?>):</strong> -$<?= number_format($order['totals']['discount'], 2) ?></p><?php endif; ?><p class="font-bold"><strong>Total:</strong> $<?= number_format($order['totals']['total'], 2) ?></p><?php if($order['status'] === 'Pending'): ?><div class="mt-4 flex gap-2"><form action="api.php" method="POST"><input type="hidden" name="action" value="update_order_status"><input type="hidden" name="order_id" value="<?= $order['order_id'] ?>"><input type="hidden" name="new_status" value="Confirmed"><button type="submit" class="btn btn-success text-sm">Confirm</button></form><form action="api.php" method="POST"><input type="hidden" name="action" value="update_order_status"><input type="hidden" name="order_id" value="<?= $order['order_id'] ?>"><input type="hidden" name="new_status" value="Cancelled"><button type="submit" class="btn btn-danger text-sm">Cancel</button></form></div><?php endif; ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                             <div id="no-orders-found" class="text-gray-500 text-center py-10 hidden">No orders match your search.</div>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Review Management View -->
                <div id="view-reviews" style="<?= $current_view === 'reviews' ? '' : 'display:none;' ?>" class="p-6"><h2 class="text-2xl font-bold text-gray-700 mb-4">Manage All Reviews</h2><?php if(empty($all_reviews)): ?><p class="text-gray-500 text-center py-10">There are no reviews on the website yet.</p><?php else: ?><div class="space-y-4"><?php foreach($all_reviews as $review): ?><div class="bg-gray-50 border rounded-lg p-4 flex flex-col md:flex-row gap-4 justify-between items-start"><div class="flex-grow"><p class="font-bold"><?= htmlspecialchars($review['name']) ?> <span class="text-yellow-500 ml-2"><?= str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']) ?></span></p><p class="text-sm text-gray-500">For product: <strong><?= htmlspecialchars($review['product_name']) ?></strong></p><p class="mt-2 text-gray-700">"<?= nl2br(htmlspecialchars($review['comment'])) ?>"</p></div><div class="flex-shrink-0 flex items-center gap-2 mt-2 md:mt-0"><form action="api.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this review permanently?');"><input type="hidden" name="action" value="update_review_status"><input type="hidden" name="product_id" value="<?= $review['product_id'] ?>"><input type="hidden" name="review_id" value="<?= $review['id'] ?>"><input type="hidden" name="new_status" value="deleted"><button type="submit" class="btn btn-danger text-sm">Delete</button></form></div></div><?php endforeach; ?></div><?php endif; ?></div>
            </div>
        <?php endif; ?>
        
        <div class="text-center mt-8">
            <a href="index.php" class="text-[var(--primary-color)] font-semibold hover:underline">← Back to Main Site</a>
        </div>
    </div>

<script>
// Store PHP-calculated stats in a JS object
const allStats = {
    today: <?= json_encode($stats_today) ?>,
    '7days': <?= json_encode($stats_7_days) ?>,
    '30days': <?= json_encode($stats_30_days) ?>,
    '6months': <?= json_encode($stats_6_months) ?>,
    'all': <?= json_encode($stats_all_time) ?>
};

function updateStatsDisplay(period) {
    const stats = allStats[period];
    const container = document.getElementById('stats-display-container');
    
    container.innerHTML = `
        <div class="bg-blue-50 p-4 rounded-lg text-center shadow">
            <p class="text-3xl font-bold text-blue-600">$${stats.total_revenue.toFixed(2)}</p>
            <p class="text-sm text-blue-800 font-semibold">Total Revenue</p>
        </div>
        <div class="bg-purple-50 p-4 rounded-lg text-center shadow">
            <p class="text-3xl font-bold text-purple-600">${stats.total_orders}</p>
            <p class="text-sm text-purple-800 font-semibold">Total Orders</p>
        </div>
        <div class="bg-green-50 p-4 rounded-lg text-center shadow">
            <p class="text-3xl font-bold text-green-600">${stats.confirmed_orders}</p>
            <p class="text-sm text-green-800 font-semibold">Confirmed</p>
        </div>
        <div class="bg-yellow-50 p-4 rounded-lg text-center shadow">
            <p class="text-3xl font-bold text-yellow-600">${stats.pending_orders}</p>
            <p class="text-sm text-yellow-800 font-semibold">Pending</p>
        </div>
        <div class="bg-red-50 p-4 rounded-lg text-center shadow">
            <p class="text-3xl font-bold text-red-600">${stats.cancelled_orders}</p>
            <p class="text-sm text-red-800 font-semibold">Cancelled</p>
        </div>
    `;
}

document.addEventListener('DOMContentLoaded', function() {
    // --- Product Management Form Script ---
    const pricingType = document.getElementById('pricing-type');
    if (pricingType) {
        const singlePriceContainer = document.getElementById('single-price-container');
        const multiplePricingContainer = document.getElementById('multiple-pricing-container');
        const addDurationBtn = document.getElementById('add-duration-btn');
        const durationFields = document.getElementById('duration-fields');

        pricingType.addEventListener('change', function() {
            if (this.value === 'single') {
                singlePriceContainer.classList.remove('hidden');
                multiplePricingContainer.classList.add('hidden');
            } else {
                singlePriceContainer.classList.add('hidden');
                multiplePricingContainer.classList.remove('hidden');
                if (durationFields.children.length === 0) addDurationField();
            }
        });
        addDurationBtn.addEventListener('click', addDurationField);
        function addDurationField() {
            const fieldGroup = document.createElement('div');
            fieldGroup.className = 'flex items-center gap-2 mb-2';
            fieldGroup.innerHTML = `<input type="text" name="durations[]" class="form-input" placeholder="Duration (e.g., 1 Year)" required><input type="number" name="duration_prices[]" step="0.01" class="form-input" placeholder="Price" required><button type="button" class="btn btn-danger text-sm remove-duration-btn">X</button>`;
            durationFields.appendChild(fieldGroup);
        }
        durationFields.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('remove-duration-btn')) {
                e.target.closest('.flex').remove();
            }
        });
    }

    // --- Order Search Script (by name) ---
    const orderSearchInput = document.getElementById('order-search-input');
    if (orderSearchInput) {
        orderSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const orderCards = document.querySelectorAll('#order-list-container .order-card-wrapper');
            const noResultsMessage = document.getElementById('no-orders-found');
            let visibleCount = 0;

            orderCards.forEach(card => {
                const name = card.dataset.customerName || '';
                if (name.includes(searchTerm)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (visibleCount === 0 && orderCards.length > 0) {
                noResultsMessage.classList.remove('hidden');
            } else {
                noResultsMessage.classList.add('hidden');
            }
        });
    }

    // --- Stats Filter Script ---
    const statsFilterContainer = document.getElementById('stats-filter-container');
    if (statsFilterContainer) {
        // Initial load
        updateStatsDisplay('all');

        statsFilterContainer.addEventListener('click', function(e) {
            if (e.target.matches('.stats-filter-btn')) {
                // Remove active class from all buttons
                this.querySelectorAll('.stats-filter-btn').forEach(btn => btn.classList.remove('active'));
                // Add active class to the clicked button
                e.target.classList.add('active');
                // Update the stats display
                const period = e.target.dataset.period;
                updateStatsDisplay(period);
            }
        });
    }
});
</script>
</body>
</html>