<?php
// edit_product.php - প্রোডাক্ট এডিট করার ফর্ম

$data_file_path = 'products.json';
$all_data = json_decode(file_get_contents($data_file_path), true);
$product_to_edit = null;
$category_name = $_GET['category'] ?? '';
$product_id = $_GET['id'] ?? null;

if ($category_name && $product_id) {
    foreach ($all_data as $category) {
        if ($category['name'] === $category_name) {
            foreach ($category['products'] as $product) {
                if ($product['id'] == $product_id) {
                    $product_to_edit = $product;
                    break 2; // Exit both loops
                }
            }
        }
    }
}

if (!$product_to_edit) {
    die("Product not found or invalid ID!");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --primary-color: #8c52ff; --primary-color-darker: #7444d9; }
        .form-input, .form-select, .form-textarea { width: 100%; border-radius: 0.5rem; border: 1px solid #d1d5db; padding: 0.75rem; transition: all 0.2s ease-in-out; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: var(--primary-color); box-shadow: 0 0 0 2px #e5d9ff; outline: none; }
        .btn { padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: bold; transition: background-color 0.3s; }
        .btn-primary { background-color: var(--primary-color); color: white; }
        .btn-primary:hover { background-color: var(--primary-color-darker); }
        .btn-danger { background-color: #ef4444; color: white; }
        .btn-danger:hover { background-color: #dc2626; }
        .btn-secondary { background-color: #4b5563; color: white; }
        .btn-secondary:hover { background-color: #1f2937; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="container mx-auto p-4 md:p-8 max-w-3xl">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Edit "<?= htmlspecialchars($product_to_edit['name']) ?>"</h1>
            <form action="api.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="edit_product">
                <input type="hidden" name="category_name" value="<?= htmlspecialchars($category_name) ?>">
                <input type="hidden" name="product_id" value="<?= htmlspecialchars($product_id) ?>">
                
                <div>
                    <label class="block mb-1 font-medium">1. Product Name</label>
                    <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($product_to_edit['name']) ?>" required>
                </div>
                <div>
                    <label class="block mb-1 font-medium">2. Short Description</label>
                    <input type="text" name="description" class="form-input" value="<?= htmlspecialchars($product_to_edit['description']) ?>" required>
                </div>
                <div>
                    <label class="block mb-1 font-medium">3. Long Description</label>
                    <textarea name="long_description" class="form-textarea" rows="4"><?= htmlspecialchars($product_to_edit['long_description'] ?? '') ?></textarea>
                </div>

                <?php
                    $is_single_price = count($product_to_edit['pricing']) <= 1 && ($product_to_edit['pricing'][0]['duration'] === 'Default' || count($product_to_edit['pricing']) === 0);
                ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">4. Pricing</label>
                        <select id="pricing-type" class="form-select">
                            <option value="single" <?= $is_single_price ? 'selected' : '' ?>>Single Price</option>
                            <option value="multiple" <?= !$is_single_price ? 'selected' : '' ?>>Multiple Durations</option>
                        </select>
                    </div>
                </div>
                
                <div id="single-price-container" class="<?= !$is_single_price ? 'hidden' : '' ?>">
                    <label class="block mb-1 font-medium">Price</label>
                    <input type="number" name="price" step="0.01" class="form-input" value="<?= htmlspecialchars($product_to_edit['pricing'][0]['price'] ?? '0.00') ?>">
                </div>

                <div id="multiple-pricing-container" class="space-y-2 <?= $is_single_price ? 'hidden' : '' ?>">
                    <label class="block font-medium">Durations & Prices</label>
                    <div id="duration-fields">
                        <?php if (!$is_single_price): ?>
                            <?php foreach ($product_to_edit['pricing'] as $pricing): ?>
                            <div class="flex items-center gap-2 mb-2">
                                <input type="text" name="durations[]" class="form-input" placeholder="Duration" value="<?= htmlspecialchars($pricing['duration']) ?>" required>
                                <input type="number" name="duration_prices[]" step="0.01" class="form-input" placeholder="Price" value="<?= htmlspecialchars($pricing['price']) ?>" required>
                                <button type="button" class="btn btn-danger btn-sm remove-duration-btn">Remove</button>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="add-duration-btn" class="btn btn-secondary text-sm">Add Duration</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t mt-4">
                    <div>
                        <label class="block mb-1 font-medium">6. Stock Status</label>
                        <select name="stock_out" class="form-select">
                            <option value="false" <?= !$product_to_edit['stock_out'] ? 'selected' : '' ?>>In Stock</option>
                            <option value="true" <?= $product_to_edit['stock_out'] ? 'selected' : '' ?>>Out of Stock</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">7. Featured Product</label>
                        <div class="flex items-center gap-2 mt-2">
                           <input type="checkbox" name="featured" id="featured" value="true" <?= !empty($product_to_edit['featured']) ? 'checked' : '' ?>>
                           <label for="featured">Mark as featured?</label>
                       </div>
                   </div>
                </div>

                <div>
                    <label class="block mb-1 font-medium">8. Product Image</label>
                    <?php if (!empty($product_to_edit['image'])): ?>
                        <div class="my-2">
                            <img src="<?= htmlspecialchars($product_to_edit['image']) ?>" class="w-24 h-24 object-cover rounded-md border">
                            <div class="flex items-center gap-2 mt-2">
                                <input type="checkbox" name="delete_image" id="delete_image" value="true">
                                <label for="delete_image" class="text-red-600">Delete current image</label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="image" class="form-input" accept="image/png, image/jpeg, image/gif, image/webp">
                    <p class="text-sm text-gray-500 mt-1">Upload a new image to replace the current one.</p>
                </div>
                
                <div class="md:col-span-2 flex justify-between items-center mt-4">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="admin.php?category=<?= urlencode($category_name) ?>" class="text-gray-600 hover:underline">Cancel</a>
                </div>
            </form>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pricingType = document.getElementById('pricing-type');
    const singlePriceContainer = document.getElementById('single-price-container');
    const multiplePricingContainer = document.getElementById('multiple-pricing-container');
    const addDurationBtn = document.getElementById('add-duration-btn');
    const durationFields = document.getElementById('duration-fields');

    pricingType.addEventListener('change', function() {
        if (this.value === 'single') {
            singlePriceContainer.style.display = 'block';
            multiplePricingContainer.style.display = 'none';
        } else {
            singlePriceContainer.style.display = 'none';
            multiplePricingContainer.style.display = 'block';
            if (durationFields.children.length === 0) {
                 addDurationField();
            }
        }
    });

    addDurationBtn.addEventListener('click', addDurationField);
    
    function addDurationField() {
        const fieldGroup = document.createElement('div');
        fieldGroup.className = 'flex items-center gap-2 mb-2';
        fieldGroup.innerHTML = `
            <input type="text" name="durations[]" class="form-input" placeholder="Duration (e.g., 1 Year)" required>
            <input type="number" name="duration_prices[]" step="0.01" class="form-input" placeholder="Price" required>
            <button type="button" class="btn btn-danger btn-sm remove-duration-btn">Remove</button>
        `;
        durationFields.appendChild(fieldGroup);
    }
    
    durationFields.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-duration-btn')) {
            e.target.closest('.flex').remove();
        }
    });
});
</script>

</body>
</html>