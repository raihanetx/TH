<?php
$products_file_path = 'products.json';
$all_data = file_exists($products_file_path) ? json_decode(file_get_contents($products_file_path), true) : [];
$coupons_file_path = 'coupons.json';
$coupons_data = file_exists($coupons_file_path) ? json_decode(file_get_contents($coupons_file_path), true) : [];

function parse_description_php($text) {
    $safe_text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $formatted_text = preg_replace('/\\*(.*?)\\*/', '<strong>$1</strong>', $safe_text);
    return $formatted_text;
}

$js_database = [];
if (!empty($all_data)) {
    foreach ($all_data as $category) {
        if (isset($category['products']) && is_array($category['products'])) {
            foreach ($category['products'] as $product) {
                $js_database[$product['id']] = [
                    'name' => $product['name'] ?? 'No Name',
                    'shortDescription' => $product['description'] ?? '',
                    'longDescription' => $product['long_description'] ?? '',
                    'image' => $product['image'] ?? '',
                    'available' => !($product['stock_out'] ?? true),
                    'category' => $category['name'],
                    'pricing' => $product['pricing'] ?? [['duration' => 'Default', 'price' => 0.0]],
                    'featured' => $product['featured'] ?? false,
                    'reviews' => $product['reviews'] ?? []
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roko Flux - E-commerce</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --primary-color: #8c52ff; --primary-color-darker: #7444d9; --primary-color-light: #e5d9ff; --gap-border-and-card-mobile: 10px; --gap-between-cards-mobile: 10px; --gap-between-cards-desktop: 30px; --color-gray-50: #f9fafb; --color-gray-100: #f3f4f6; --color-gray-200: #e5e7eb; --color-gray-300: #d1d5db; --color-gray-400: #9ca3af; --color-gray-500: #6b7280; --color-gray-600: #4b5563; --color-gray-700: #374151; --color-gray-800: #1f2937; --color-gray-900: #111827; --color-white: #ffffff; --color-red-500: #ef4444; --color-red-600: #dc2626; --color-green-600: #16a34a; --color-green-700: #15803d; --color-yellow-400: #facc15; }
        * { box-sizing: border-box; } html { font-size: 100%; } body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif; background-color: var(--color-gray-50); display: flex; flex-direction: column; min-height: 100vh; color: var(--color-gray-800); padding-bottom: 60px; } main { flex-grow: 1; } a { color: inherit; text-decoration: inherit; } button { font-family: inherit; font-size: 100%; margin: 0; padding: 0; border: none; background: transparent; cursor: pointer; }
        .container { width: 100%; margin-left: auto; margin-right: auto; padding-left: 1.5rem; padding-right: 1.5rem; } @media (min-width: 640px) { .container { max-width: 640px; } } @media (min-width: 768px) { .container { max-width: 768px; } } @media (min-width: 1024px) { .container { max-width: 1024px; } } @media (min-width: 1280px) { .container { max-width: 1280px; } } .shadow-md { box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); } .shadow-lg { box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1); } .btn { display: inline-block; font-weight: 600; padding: 0.75rem 1.5rem; border-radius: 0.5rem; transition: all 0.2s ease-in-out; text-align: center; } .btn-primary { background-color: var(--primary-color); color: var(--color-white); } .btn-primary:hover { background-color: var(--primary-color-darker); } .btn-secondary { background-color: var(--color-gray-200); color: var(--color-gray-800); } .btn-secondary:hover { background-color: var(--color-gray-300); }
        .main-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem; background-color: var(--color-white); position: sticky; top: 0; z-index: 50; height: 4.5rem; } .main-header .logo img { height: 2.5rem; }
        .header-icons { display: flex; align-items: center; gap: 1rem; }
        .header-icons .icon { font-size: 1.25rem; color: var(--color-gray-600); cursor: pointer; position: relative; padding: 0.5rem; } .header-icons .icon:hover { color: var(--primary-color); }
        .cart-count-badge { position: absolute; top: 0; right: 0; background-color: var(--color-red-500); color: var(--color-white); font-size: 0.75rem; border-radius: 9999px; height: 1.25rem; width: 1.25rem; display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .desktop-search-bar { flex-grow: 1; max-width: 42rem; margin: 0 1.25rem; display: none; } .desktop-search-bar input { width: 100%; padding: 0.5rem 1rem; border-radius: 9999px; border: 1px solid var(--color-gray-300); } .desktop-search-bar input:focus { outline: none; box-shadow: 0 0 0 2px var(--primary-color); }
        .mobile-search-container { background: var(--color-white); padding: 1rem; position: sticky; top: 4.5rem; z-index: 40; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); } .mobile-search-container input { width: 100%; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid var(--color-gray-300); } .mobile-search-container input:focus { outline: none; box-shadow: 0 0 0 2px var(--primary-color); }
        .mobile-header-icons, .desktop-header-icons { display: none; }
        @media (max-width: 767px) { .mobile-header-icons { display: flex; align-items: center; gap: 0.5rem; } }
        @media (min-width: 768px) { body { padding-bottom: 0; } .desktop-header-icons { display: flex; align-items: center; gap: 1rem; } .desktop-search-bar { display: block; } }
        .side-menu-overlay { position: fixed; inset: 0; background-color: rgba(0,0,0,0.5); z-index: 100; opacity: 0; visibility: hidden; transition: opacity 0.3s ease; }
        .side-menu-overlay.active { opacity: 1; visibility: visible; }
        .side-menu { position: fixed; top: 0; left: 0; height: 100%; width: 280px; background: var(--color-white); z-index: 110; transform: translateX(-100%); transition: transform 0.3s ease; display: flex; flex-direction: column; }
        .side-menu.active { transform: translateX(0); }
        .side-menu-header { padding: 1rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--color-gray-200); }
        .side-menu-header .logo img { height: 2rem; }
        .side-menu-header .close-btn { font-size: 1.5rem; color: var(--color-gray-500); }
        .side-menu-content { padding: 1rem; overflow-y: auto; flex-grow: 1; }
        .side-menu-content h3 { font-size: 1rem; font-weight: 700; color: var(--color-gray-800); margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .side-menu-content ul { list-style: none; padding: 0; margin: 0; }
        .side-menu-content a { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem; font-weight: 500; color: var(--color-gray-700); }
        .side-menu-content a:hover { background-color: var(--color-gray-100); color: var(--primary-color); }
        .side-menu-content a i { font-size: 1.25rem; width: 20px; text-align: center; }
        .mobile-bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: var(--color-white); box-shadow: 0 -2px 10px rgba(0,0,0,0.1); z-index: 40; display: flex; justify-content: space-around; padding: 0.5rem 0; }
        .mobile-bottom-nav a { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--color-gray-500); padding: 0.25rem 0; }
        .mobile-bottom-nav a.active { color: var(--primary-color); }
        .mobile-bottom-nav a i { font-size: 1.5rem; margin-bottom: 0.25rem; }
        .mobile-bottom-nav a span { font-size: 0.75rem; font-weight: 500; }
        @media (min-width: 768px) { .mobile-bottom-nav { display: none; } }
        .hero-section { border-radius: 2rem; overflow: hidden; height: 300px; margin: 1rem; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); position: relative; } .hero-slide { width: 100%; height: 100%; background: linear-gradient(45deg, var(--primary-color), #b59cff); border-radius: 2rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.875rem; font-weight: bold; } .category-icon { display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease; text-decoration: none; width: 75px; height: auto; padding: 1rem 0; flex-shrink: 0; border: 3px solid #e5e7eb; border-radius: 0.75rem; } .category-icon:hover { transform: translateY(-5px); border-color: var(--primary-color); } .category-icon i { font-size: 2.25rem; color: var(--primary-color); margin-bottom: 0; transition: all 0.3s ease; } .category-icon span { font-size: 0.75rem; margin-top: 0.2rem; color: #374151; font-weight: 500; text-align: center; line-height: 1; transition: color 0.3s ease; } .category-icon:hover i, .category-icon:hover span { color: var(--primary-color-darker); } .category-scroll-container { display: flex; flex-wrap: nowrap; width: max-content; gap: 1.5rem; } .horizontal-scroll { overflow-x: auto; scrollbar-width: none; } .horizontal-scroll::-webkit-scrollbar { display: none; } .smooth-scroll { scroll-behavior: smooth; } .product-card { width: 170px; height: 280px; display: flex; flex-direction: column; flex-shrink: 0; border: 1px solid #e5e7eb; border-radius: 0.75rem; overflow: hidden; position: relative; scroll-snap-align: start; transition: all 0.3s ease; background-color: var(--color-white); } .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.07); } .product-card-image-wrapper { height: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; position: relative; background-color: #f3f4f6; } .product-card-image-wrapper img { width: 100%; height: 100%; object-fit: cover; } .product-card-content { padding: 1rem; flex-grow: 1; display: flex; flex-direction: column; } .product-card-content h3 { font-weight: 700; margin-bottom: 0.25rem; font-size: 1rem; } .product-card-content .description { color: var(--color-gray-600); font-size: 0.875rem; margin-bottom: 0.75rem; } .product-card-content .price { color: var(--primary-color); font-weight: 700; font-size: 1.125rem; margin-bottom: 0.75rem; margin-top: auto; } .product-card-content .details-btn { width: 100%; background-color: var(--color-gray-200); color: var(--color-gray-700); padding: 0.375rem 0; border-radius: 0.5rem; transition: background-color 0.3s; font-size: 0.875rem; } .product-card-content .details-btn:hover { background-color: var(--color-gray-300); } .product-scroll-container { display: flex; width: max-content; padding-left: var(--gap-border-and-card-mobile); padding-right: var(--gap-border-and-card-mobile); gap: var(--gap-between-cards-mobile); } @media (min-width: 768px) { .category-scroll-container { gap: 2rem; } .category-icon { width: 140px; padding: 1.25rem 0; } .category-icon i { font-size: 4.4rem; } .category-icon span { font-size: 1.125rem; margin-top: 0.5rem; } .product-card { width: 280px; height: 350px; } .product-card-content .details-btn { padding: 0.5rem 0; font-size: 1rem; } .product-scroll-container { padding-left: var(--gap-between-cards-desktop); padding-right: var(--gap-between-cards-desktop); gap: var(--gap-between-cards-desktop); } .horizontal-scroll { padding: 0 2rem; } } @media (max-width: 767px) { html { font-size: 80%; } .hero-section { height: 180px; } .category-scroll-container { padding: 0 1rem; } } .feature-card { transition: all 0.3s ease; background-color: var(--color-gray-50); padding: 1rem; border-radius: 0.75rem; text-align: center; display: flex; flex-direction: column; width: 100%; } .feature-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); } .feature-card .icon-wrapper { background: var(--primary-color-light); width: 3rem; height: 3rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; } .feature-card .icon-wrapper i { font-size: 1.5rem; color: var(--primary-color); } .feature-card h3 { font-size: 1.125rem; font-weight: 700; margin-bottom: 0.25rem; } .feature-card p { font-size: 0.875rem; color: var(--color-gray-600); margin-top: 0.5rem; text-align: left; } .stock-out-badge { position: absolute; top: 0.5rem; right: 0.5rem; background: rgba(255, 255, 255, 0.9); padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; box-shadow: 0 2px 5px rgba(0,0,0,0.1); z-index: 10; color: #b91c1c; } .product-grid-card { display: flex; flex-direction: column; background-color: white; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); overflow: hidden; transition: all 0.3s ease; position: relative; } .product-grid-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1); } .form-input, .form-select, .form-textarea { width: 100%; border-radius: 0.5rem; border: 1px solid #d1d5db; padding: 0.75rem; transition: all 0.2s ease-in-out; } .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: var(--primary-color); box-shadow: 0 0 0 2px var(--primary-color-light); outline: none; } .form-label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151; } .page-section-header { text-align: center; margin-top: 1.5rem; margin-bottom: 1.5rem; } .page-section-header h2 { font-size: 1.5rem; font-weight: 700; } .page-section-title-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding: 0 1rem; } .page-section-title-bar h2 { font-size: 1.5rem; font-weight: 700; } .page-section-title-bar a { color: var(--primary-color); font-weight: 700; display: flex; align-items: center; font-size: 1.125rem; } .page-section-title-bar a:hover { text-decoration: underline; } .page-section-title-bar i { margin-left: 0.25rem; font-size: 1.5rem; } .why-choose-us-section { padding: 3rem 0; background-color: var(--color-white); } .why-choose-us-section h2 { font-size: 1.875rem; font-weight: 700; text-align: center; margin-bottom: 3rem; } .features-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; } .products-page-container, .cart-page-container, .checkout-page-container, .product-detail-page-container, .my-orders-page-container { max-width: 80rem; margin: 0 auto; padding: 2rem 1rem; } .page-back-link { font-size: 1.125rem; font-weight: 600; color: var(--primary-color); display: flex; align-items: center; margin-bottom: 1.5rem; } .page-back-link:hover { text-decoration: underline; } .page-back-link i { margin-right: 0.25rem; font-size: 1.5rem; } .page-main-title { font-size: 1.875rem; font-weight: 700; margin-bottom: 2rem; } .products-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; } .product-grid-card-img-wrapper { height: 7rem; display: flex; align-items: center; justify-content: center; background-color: #f3f4f6;} .product-grid-card-img-wrapper img { width: 100%; height: 100%; object-fit: cover; } .product-grid-card-content { padding: 0.75rem; display: flex; flex-direction: column; flex-grow: 1; } .product-grid-card-content h3 { font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem; } .product-grid-card-content .description { font-size: 0.75rem; color: var(--color-gray-600); margin-bottom: 0.5rem; flex-grow: 1; } .product-grid-card-content .price { font-size: 1.125rem; font-weight: 800; color: var(--primary-color); margin-bottom: 0.75rem; } .product-grid-card-actions { display: flex; flex-direction: column; gap: 0.5rem; } .product-grid-card-actions .btn { width: 100%; padding: 0.5rem; font-size: 0.875rem; } .product-grid-card-actions .btn:disabled { opacity: 0.5; cursor: not-allowed; } .cart-empty-state, .checkout-empty-state { text-align: center; background-color: var(--color-white); padding: 3rem; border-radius: 0.5rem; } .cart-empty-state i, .checkout-empty-state i { font-size: 3.75rem; margin-bottom: 1rem; } .cart-empty-state h2, .checkout-empty-state h2 { font-size: 1.5rem; font-weight: 700; } .cart-empty-state p, .checkout-empty-state p { color: var(--color-gray-500); margin: 0.5rem 0 1.5rem; } .cart-grid { display: grid; grid-template-columns: 1fr; gap: 2rem; } .cart-items-column { background-color: var(--color-white); border-radius: 0.5rem; } #cart-items-container > div:not(:last-child) { border-bottom: 1px solid var(--color-gray-200); } .cart-item { display: flex; align-items: center; padding: 1rem; } .cart-item-img { width: 5rem; height: 5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; margin-right: 1rem; flex-shrink: 0; overflow: hidden; } .cart-item-img img { width: 100%; height: 100%; object-fit: cover; } .cart-item-info { flex-grow: 1; } .cart-item-info h3 { font-weight: 700; } .cart-item-info p { font-size: 0.875rem; color: var(--color-gray-500); } .cart-item-qty { display: flex; align-items: center; gap: 0.5rem; margin: 0 1rem; } .cart-item-qty button { width: 1.75rem; height: 1.75rem; background-color: var(--color-gray-200); border-radius: 9999px; font-weight: 700; color: var(--color-gray-600); } .cart-item-qty button:hover { background-color: var(--color-gray-300); } .cart-item-qty input[type="number"] { width: 3rem; text-align: center; border: 1px solid var(--color-gray-300); border-radius: 0.25rem; padding: 0.25rem; -moz-appearance: textfield; } .cart-item-qty input[type="number"]::-webkit-outer-spin-button, .cart-item-qty input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; } .cart-item-total { font-weight: 700; font-size: 1.125rem; width: 6rem; text-align: right; } .cart-item-remove-btn { margin-left: 1rem; color: var(--color-gray-400); } .cart-item-remove-btn:hover { color: var(--color-red-500); } .cart-item-remove-btn i { font-size: 1.25rem; } .cart-summary-box, .checkout-summary-box { background-color: var(--color-white); padding: 1.5rem; border-radius: 0.5rem; } .summary-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; } .summary-row { display: flex; justify-content: space-between; color: var(--color-gray-600); } .summary-total-row { border-top: 1px solid var(--color-gray-200); padding-top: 1rem; margin-top: 0.5rem; display: flex; justify-content: space-between; font-weight: 700; font-size: 1.25rem; } .checkout-grid { display: flex; flex-direction: column; gap: 2rem; } .checkout-form-section { background-color: var(--color-white); padding: 1.5rem; border-radius: 0.75rem; } .checkout-form-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem; border-bottom: 1px solid var(--color-gray-200); padding-bottom: 1rem; } .checkout-form-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem 1.5rem; } .checkout-form-grid .span-2 { grid-column: span 2 / span 2; } .checkout-summary-item { display: flex; align-items: center; justify-content: space-between; } .checkout-summary-item-info { display: flex; align-items: center; } .checkout-summary-item-img { width: 4rem; height: 4rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; margin-right: 1rem; overflow: hidden;} .checkout-summary-item-img img { width: 100%; height: 100%; object-fit: cover; } .checkout-summary-item-details p:first-child { font-weight: 600; } .checkout-summary-item-details p:last-child { font-size: 0.875rem; color: var(--color-gray-500); } .checkout-summary-item-price { font-weight: 600; } 
        .payment-number-wrapper { text-align: center; margin-bottom: 1rem; }
        .payment-number-wrapper .number { font-size: 1.5rem; font-weight: 700; color: var(--primary-color); display: inline-block; }
        .payment-number-wrapper .copy-btn { margin-left: 0.5rem; color: var(--primary-color); font-size: 1.25rem; }
        #payment-instructions { margin-top: 1rem; font-size: 1rem; color: var(--color-gray-800); padding: 1.5rem; border-radius: 0.75rem; border: 1px solid var(--color-gray-200); background-color: var(--color-white); }
        #payment-instructions .instruction-header { display: flex; align-items: center; gap: 0.75rem; font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; }
        #payment-instructions .instruction-header i { font-size: 1.5rem; color: var(--primary-color); }
        #payment-instructions ul { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem; }
        #payment-instructions li { display: flex; align-items: center; gap: 0.75rem; }
        #payment-instructions li i { color: var(--primary-color); }
        #payment-instructions .highlight { background-color: var(--primary-color-light); color: var(--primary-color-darker); padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-weight: 600; }

        /* --- START: PRODUCT DETAIL UI IMPROVEMENTS --- */
        .product-detail-grid { display: grid; grid-template-columns: 1fr; gap: 2rem; } 
        .product-detail-image-box { border-radius: 1rem; display: flex; align-items: center; justify-content: center; overflow: hidden; background-color: #f3f4f6;} 
        .product-detail-image-box img { width: 100%; height: 100%; object-fit: cover; }
        .product-detail-info { display: flex; flex-direction: column; }
        .product-detail-info > div { margin-bottom: 1.5rem; } /* Mobile spacing */
        .product-detail-info > div:last-child { margin-bottom: 0; }
        #pd-name { font-size: 2.25rem; font-weight: 800; line-height: 1.2; margin: 0; }
        .pd-availability { font-size: 1rem; font-weight: 700; display: inline-block; vertical-align: middle; margin-left: 0.75rem; }
        .pd-availability.in-stock { color: var(--color-green-700); }
        .pd-availability.out-of-stock { color: var(--color-red-600); }
        #pd-description-short { color: var(--color-gray-600); font-size: 1.125rem; margin: 0;}
        #pd-price { font-size: 2.5rem; font-weight: 700; color: var(--primary-color); margin: 0; }
        .product-detail-duration h3 { font-size: 1.125rem; font-weight: 600; margin-top: 0; margin-bottom: 1rem; }
        .duration-buttons { display: flex; flex-wrap: wrap; gap: 0.75rem; }
        .duration-btn { padding: 0.75rem 1.25rem; border: 2px solid var(--color-gray-300); font-weight: 600; transition: all 0.2s; border-radius: 0.5rem; background-color: var(--color-white); color: var(--color-gray-700); position: relative; }
        .duration-btn:hover { background-color: var(--color-gray-100); border-color: var(--color-gray-400); }
        .duration-btn.active { border-color: var(--primary-color); background-color: var(--primary-color-light); color: var(--primary-color-darker); box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .duration-btn.active i { opacity: 1; }
        .duration-btn i { position: absolute; top: -0.6rem; right: -0.6rem; font-size: 1.25rem; background: var(--color-white); border-radius: 50%; color: var(--primary-color); opacity: 0; transition: opacity 0.2s; }
        .product-detail-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: auto; } /* margin-top: auto is key */
        .product-detail-actions .btn { padding: 1rem; font-size: 1rem; border: 2px solid transparent; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
        .product-detail-actions .btn-primary { box-shadow: 0 4px 14px 0 rgba(140, 82, 255, 0.39); }
        .product-detail-actions .btn-secondary { background: transparent; border-color: var(--primary-color); color: var(--primary-color); }
        .product-detail-actions .btn-secondary:hover { background: var(--primary-color-light); }
        .product-detail-actions .btn:disabled { background: var(--color-gray-200); color: var(--color-gray-400); border-color: var(--color-gray-200); box-shadow: none; cursor: not-allowed; }
        .product-detail-tabs-container, .related-products-container { margin-top: 4rem; }
        @media (min-width: 768px) { 
            .checkout-grid { flex-direction: row-reverse; align-items: flex-start; }
            .checkout-grid > div:first-child { flex: 1; position: sticky; top: 6rem; }
            .checkout-grid > div:last-child { flex: 2; }
            .product-detail-grid { grid-template-columns: repeat(2, 1fr); gap: 3rem; align-items: stretch; }
            .product-detail-image-box { aspect-ratio: 100 / 85; }
            .product-detail-info { justify-content: space-between; }
            .product-detail-info > div { margin-bottom: 0; }
            .product-detail-tabs-container, .related-products-container { grid-column: span 2; }
            #related-products-grid.products-grid, .products-grid.is-related { grid-template-columns: repeat(3, 1fr) !important; }
        }
        .product-detail-tab-nav { border-bottom: 2px solid var(--color-gray-200); margin-bottom: 1.5rem; }
        .tab-buttons-container { display: flex; gap: 2rem; margin-bottom: -2px; }
        .tab-buttons-container button { padding: 1rem 0.25rem; border-bottom: 2px solid transparent; font-weight: 600; font-size: 1.125rem; color: var(--color-gray-500); }
        .tab-buttons-container button.active, .tab-buttons-container button:hover { color: var(--primary-color); border-color: var(--primary-color); }
        .review-input-box { display: flex; align-items: center; gap: 1rem; width: 100%; text-align: left; padding: 1rem; color: var(--color-gray-500); border: 1px solid var(--color-gray-200); box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); border-radius: 0.5rem; cursor: pointer; }
        .review-input-box .avatar { width: 2.5rem; height: 2.5rem; border-radius: 9999px; background: var(--color-gray-200); color: var(--color-gray-500); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        .review-input-box:hover { background-color: var(--color-gray-100); }
        .review-item { display: flex; gap: 1rem; padding: 1.5rem 0; text-align: left; } 
        .review-item:not(:last-child) { border-bottom: 1px solid var(--color-gray-200); }
        /* --- END: PRODUCT DETAIL UI IMPROVEMENTS --- */
        .prose-styles p { line-height: 1.6; margin-bottom: 1rem; } .coupon-input-group { display: flex; gap: 0.5rem; } .coupon-input-group input { flex-grow: 1; text-transform: uppercase; } .coupon-input-group button { background-color: var(--color-gray-300); color: var(--color-gray-700); font-weight: 600; padding: 0 1rem; border-radius: 0.5rem; } .coupon-input-group button:hover { background-color: var(--color-gray-400); } .coupon-input-group button:disabled { opacity: 0.5; cursor: not-allowed; } .coupon-message { font-size: 0.875rem; margin-top: 0.5rem; } .coupon-error { color: var(--color-red-600); } .coupon-success { color: var(--color-green-600); display: flex; justify-content: space-between; align-items: center; } .remove-coupon-btn { font-size: 0.75rem; color: var(--color-red-500); text-decoration: underline; cursor: pointer; border: none; background: none;}
        .footer-section { position: relative; overflow: hidden; background-color: var(--color-gray-900); color: var(--color-white); padding: 4rem 0 2rem; }
        .footer-grid { display: grid; grid-template-columns: 1fr; gap: 2.5rem; }
        .footer-grid > div h3 { font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem; }
        .footer-grid p { color: var(--color-gray-400); line-height: 1.6; }
        .footer-contact-list, .footer-links-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 1rem; }
        .footer-contact-list li, .footer-links-list li { display: flex; align-items: center; gap: 0.75rem; }
        .footer-contact-list i, .footer-links-list i { font-size: 1.25rem; color: var(--primary-color); width: 20px; text-align: center; }
        .footer-links-list a:hover { color: var(--primary-color); }
        .footer-newsletter { margin-top: 2.5rem; text-align: center; }
        .footer-newsletter h3 { font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; }
        .footer-newsletter-form { display: flex; max-width: 400px; margin: 0 auto; } .footer-newsletter-form input { width: 100%; padding: 0.75rem 1rem; border-radius: 0.375rem 0 0 0.375rem; color: var(--color-gray-900); border: none; } .footer-newsletter-form button { padding: 0.75rem 1.5rem; border-radius: 0 0.375rem 0.375rem 0; }
        .footer-bottom { border-top: 1px solid var(--color-gray-700); margin-top: 3rem; padding-top: 2rem; text-align: center; color: var(--color-gray-500); }
        .order-card { background: white; border-radius: 0.5rem; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); margin-bottom: 1.5rem; } .order-header { padding: 1rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; } .status-badge { padding: 0.25rem 0.75rem; border-radius: 9999px; font-weight: 600; font-size: 0.875rem; } .status-Pending { background-color: #fef9c3; color: #a16207; } .status-Confirmed { background-color: #dcfce7; color: #166534; } .status-Cancelled { background-color: #fee2e2; color: #991b1b; }
        .review-modal-overlay { position: fixed; inset: 0; background-color: rgba(0,0,0,0.6); z-index: 100; display: none; align-items: center; justify-content: center; padding: 1rem; }
        .review-modal-content { background-color: var(--color-white); border-radius: 0.75rem; padding: 1.5rem; width: 100%; max-width: 32rem; } .review-modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; } .review-modal-header h2 { font-size: 1.5rem; font-weight: 700; } .review-modal-header button { font-size: 1.5rem; color: var(--color-gray-400); } .review-modal-header button:hover { color: var(--color-gray-600); } .review-modal-form div { margin-bottom: 1rem; } .review-modal-form textarea { min-height: 6rem; } .review-modal-footer { margin-top: 2rem; display: flex; justify-content: flex-end; }
        .star-rating { display: flex; flex-direction: row-reverse; justify-content: center; font-size: 2rem; } .star-rating input { display: none; } .star-rating label { color: #ddd; cursor: pointer; } .star-rating input:checked ~ label, .star-rating label:hover, .star-rating label:hover ~ label { color: var(--color-yellow-400); }
        .review-item .avatar { width: 3rem; height: 3rem; border-radius: 9999px; background: var(--primary-color-light); color: var(--primary-color); display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0; }
        .description, #pd-description-long, #pd-description-short { white-space: pre-wrap; word-break: break-word; }
        @keyframes pulse { 50% { opacity: .5; } } .skeleton { animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite; background-color: var(--color-gray-200); } .skeleton-card { background-color: var(--color-white); border: 1px solid var(--color-gray-200); border-radius: 0.75rem; overflow: hidden; } .skeleton-image { height: 7rem; } .skeleton-content { padding: 0.75rem; } .skeleton-title { height: 1.25rem; width: 75%; margin-bottom: 0.5rem; border-radius: 0.25rem; } .skeleton-text { height: 0.75rem; width: 90%; margin-bottom: 1rem; border-radius: 0.25rem; } .skeleton-price { height: 1.25rem; width: 40%; border-radius: 0.25rem; }
        @media (min-width: 768px) { .page-section-header { margin-top: 2rem; margin-bottom: 2rem; } .page-section-title-bar { padding: 0 1.5rem; } .features-grid { grid-template-columns: repeat(4, 1fr); } .products-page-container, .cart-page-container, .checkout-page-container, .product-detail-page-container, .my-orders-page-container { padding: 3rem 1.5rem; } .products-grid { grid-template-columns: repeat(3, 1fr); gap: 2rem; } .product-grid-card-img-wrapper { height: 10rem; } .product-grid-card-content { padding: 1rem; } .product-grid-card-content h3 { font-size: 1.125rem; } .product-grid-card-content .description { font-size: 0.875rem; margin-bottom: 1rem; } .product-grid-card-content .price { font-size: 1.25rem; margin-bottom: 1rem; } .product-grid-card-actions { flex-direction: row; } .cart-grid { grid-template-columns: 2fr 1fr; } .checkout-form-grid { grid-template-columns: repeat(2, 1fr); } .checkout-form-grid .span-2 { grid-column: span 2 / span 2; } .footer-grid { grid-template-columns: 2fr 1fr 1fr; } #related-products-grid.products-grid, .products-grid.is-related { grid-template-columns: repeat(3, 1fr) !important; } }
    </style>
</head>
<body>
    <header id="main-header" class="main-header shadow-md">
        <a href="index.php" class="logo"><img src="https://i.postimg.cc/632VFynb/IMG-20250811-105655.png" alt="Roko Flux Logo"></a>
        <div class="desktop-search-bar"><input type="text" id="desktop-search-input" placeholder="Search products..."></div>
        <div class="header-icons">
            <div class="mobile-header-icons">
                <button id="mobile-search-btn" class="icon" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
                <a href="#" data-view="cart" class="icon"><i class="fa-solid fa-cart-shopping"></i><span id="mobile-cart-count" class="cart-count-badge" style="display: none;"></span></a>
                <button class="icon open-menu-btn" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
            </div>
            <div class="desktop-header-icons">
                <a href="#" data-view="products" class="icon" aria-label="All Products"><i class="fa-solid fa-box-archive"></i></a>
                <a href="#" data-view="my-orders" class="icon" aria-label="My Orders"><i class="fa-solid fa-receipt"></i></a>
                <a href="#" data-view="cart" class="icon"><i class="fa-solid fa-cart-shopping"></i><span id="desktop-cart-count" class="cart-count-badge" style="display: none;"></span></a>
                <button class="icon open-menu-btn" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
            </div>
        </div>
    </header>
    <div class="side-menu-overlay"></div>
    <div class="side-menu">
        <div class="side-menu-header">
            <a href="index.php" class="logo"><img src="https://i.postimg.cc/632VFynb/IMG-20250811-105655.png" alt="Roko Flux Logo"></a>
            <button class="close-btn icon"><i class="fa-solid fa-times"></i></button>
        </div>
        <div class="side-menu-content">
            <h3>Categories</h3>
            <ul id="side-menu-categories">
                <?php if (!empty($all_data)) foreach ($all_data as $category): ?>
                    <li><a href="#" data-view="products" data-category-filter="<?= htmlspecialchars($category['name']) ?>"><i class="<?= htmlspecialchars($category['icon']) ?>"></i><span><?= htmlspecialchars($category['name']) ?></span></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <nav class="mobile-bottom-nav">
        <a href="#" data-view="home" class="bottom-nav-link active" aria-label="Home"><i class="fa-solid fa-house"></i><span>Home</span></a>
        <a href="#" data-view="products" class="bottom-nav-link" aria-label="Products"><i class="fa-solid fa-box-archive"></i><span>Products</span></a>
        <a href="#" data-view="my-orders" class="bottom-nav-link" aria-label="My Orders"><i class="fa-solid fa-receipt"></i><span>Orders</span></a>
    </nav>
    <main>
        <div id="mobile-search-bar" class="mobile-search-container" style="display: none;"><input type="text" id="mobile-search-input" placeholder="Search products..."></div>
        <div id="view-home" class="page-view">
            <section class="hero-section"><div class="hero-slide">High-Quality Digital Products</div></section>
            <section><div class="page-section-header"><h2>Product Categories</h2></div><div class="horizontal-scroll smooth-scroll"><div class="category-scroll-container" style="padding: 0 2rem;"><?php if (!empty($all_data)) foreach ($all_data as $category): ?><a href="#" data-view="products" data-category-filter="<?= htmlspecialchars($category['name']) ?>" class="category-icon"><i class="<?= htmlspecialchars($category['icon']) ?>"></i><span><?= htmlspecialchars($category['name']) ?></span></a><?php endforeach; ?></div></div></section>
            <?php if (!empty($all_data)) foreach ($all_data as $category): ?><section style="margin-bottom: 3rem;"><div class="page-section-title-bar"><h2><?= htmlspecialchars($category['name']) ?></h2><a href="#" data-view="products" data-category-filter="<?= htmlspecialchars($category['name']) ?>"> View all <i class="fa-solid fa-arrow-right"></i> </a></div><div class="horizontal-scroll smooth-scroll"><div class="product-scroll-container"><?php if (!empty($category['products'])): foreach ($category['products'] as $product): ?><div class="product-card"><div class="product-card-image-wrapper"><?php if ($product['stock_out']): ?><div class="stock-out-badge">Stock Out</div><?php endif; ?><img src="<?= htmlspecialchars($product['image'] ?? '') ?>" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.style.display='none'"></div><div class="product-card-content"><h3 class="title"><?= htmlspecialchars($product['name']) ?></h3><p class="description"><?= parse_description_php($product['description']) ?></p><div class="price">৳<?= htmlspecialchars(number_format($product['pricing'][0]['price'], 2)) ?></div><button data-view="productDetail" data-product-id="<?= $product['id'] ?>" class="details-btn">View Details</button></div></div><?php endforeach; else: ?><div style="display: flex; align-items: center; justify-content: center; width: 100%; min-height: 280px; padding: 2rem; color: var(--color-gray-500);"><p>No products here yet.</p></div><?php endif; ?></div></div></section><?php endforeach; ?>
        </div>
        <div id="view-products" class="page-view products-page-container" style="display: none;"><div><a href="#" data-view="home" class="page-back-link"><i class="fa-solid fa-arrow-left"></i> Back to Home</a></div><h1 id="products-page-title" class="page-main-title">All Products</h1>
            <div id="products-grid-container" class="products-grid">
                <!-- Skeleton Loaders -->
                <?php for ($i = 0; $i < 6; $i++): ?>
                <div class="skeleton-card">
                    <div class="skeleton skeleton-image"></div>
                    <div class="skeleton-content">
                        <div class="skeleton skeleton-title"></div>
                        <div class="skeleton skeleton-text"></div>
                        <div class="skeleton skeleton-price"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <!-- START: IMPROVED PRODUCT DETAIL PAGE -->
        <div id="view-productDetail" class="page-view product-detail-page-container" style="display: none;">
            <div><a href="#" data-view="products" class="page-back-link"><i class="fa-solid fa-arrow-left"></i> Back to Products</a></div>
            <div class="product-detail-grid">
                <div id="pd-image-container" class="product-detail-image-box shadow-lg"></div>
                <div class="product-detail-info">
                    <div>
                        <h1 id="pd-name"></h1>
                        <p id="pd-description-short" class="description"></p>
                        <div id="pd-price-container"><p id="pd-price"></p></div>
                        <div id="pd-duration-section" class="product-detail-duration">
                            <h3>Choose Subscription:</h3>
                            <div id="pd-duration-container" class="duration-buttons"></div>
                        </div>
                    </div>
                    <div class="product-detail-actions">
                         <button id="pd-buy-now-btn" data-action="buy-now" class="btn btn-primary"><i class="fa-solid fa-bolt"></i> Buy Now</button>
                        <button id="pd-add-to-cart-btn" data-action="add-to-cart" class="btn btn-secondary"><i class="fa-solid fa-cart-plus"></i> Add to Cart</button>
                    </div>
                </div>
                <div class="product-detail-tabs-container">
                    <div class="product-detail-tab-nav">
                        <nav class="tab-buttons-container">
                            <button data-tab="description" class="pd-tab-btn active">Description</button>
                            <button data-tab="reviews" class="pd-tab-btn">Reviews</button>
                        </nav>
                    </div>
                    <div>
                        <div id="pd-tab-description" class="pd-tab-content prose-styles"><div id="pd-description-long"></div></div>
                        <div id="pd-tab-reviews" class="pd-tab-content" style="display:none;">
                            <button data-action="open-review-modal" class="review-input-box">
                                <div class="avatar"><i class="fa-solid fa-user"></i></div>
                                <span>Click here to write your review...</span>
                            </button>
                            <div id="pd-reviews-container" style="margin-top: 1.5rem;"></div>
                        </div>
                    </div>
                </div>
                <section id="related-products-section" class="related-products-container" style="display: none;">
                    <h2 class="page-main-title" style="text-align: center;">Related Products</h2>
                    <div id="related-products-grid" class="products-grid is-related">
                        <?php for ($i = 0; $i < 3; $i++): ?>
                        <div class="skeleton-card">
                           <div class="skeleton skeleton-image"></div>
                           <div class="skeleton-content">
                               <div class="skeleton skeleton-title"></div>
                               <div class="skeleton skeleton-text"></div>
                               <div class="skeleton skeleton-price"></div>
                           </div>
                       </div>
                       <?php endfor; ?>
                    </div>
                </section>
            </div>
        </div>
        <!-- END: IMPROVED PRODUCT DETAIL PAGE -->

        <div id="view-cart" class="page-view cart-page-container" style="display: none;"><h1 class="page-main-title">Your Shopping Cart</h1><div id="cart-empty-message" class="cart-empty-state shadow-md" style="display: none;"><i class="fa-solid fa-cart-shopping" style="color: var(--color-gray-300);"></i><h2>Your cart is empty.</h2><p>Looks like you haven't added anything to your cart yet.</p><button data-view="products" class="btn btn-primary">Continue Shopping</button></div><div id="cart-content" class="cart-grid" style="display: none;"><div id="cart-items-container" class="cart-items-column shadow-md"></div><div class="cart-summary-box shadow-md" style="align-self: start;"><h2 class="summary-title">Summary</h2><div style="display: flex; flex-direction: column; gap: 0.5rem;"><div class="summary-row"><span>Subtotal</span><span id="cart-summary-subtotal">৳0.00</span></div><div class="summary-total-row"><span>Total</span><span id="cart-summary-total">৳0.00</span></div></div><button data-view="checkout" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 0.75rem; font-size: 1.125rem;">Proceed to Checkout</button></div></div></div>

        <div id="view-checkout" class="page-view checkout-page-container" style="display: none;"><h1 class="page-main-title">Secure Checkout</h1><div id="checkout-empty-cart" class="checkout-empty-state shadow-md" style="display: none;"><i class="fa-solid fa-triangle-exclamation" style="color: var(--color-yellow-400);"></i><h2>No items to checkout.</h2><p>Please add items to your cart or use 'Buy Now' to proceed.</p><button data-view="products" class="btn btn-primary">Shop Now</button></div>
            <div id="checkout-content" class="checkout-grid" style="display: none;">
                <div class="checkout-summary-box shadow-md">
                    <h2 class="summary-title" style="border-bottom: 1px solid var(--color-gray-200); padding-bottom: 1rem;">Order Summary</h2>
                    <div id="checkout-summary-items" style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem; padding-top: 1rem;"></div>
                    <div style="margin-bottom: 1.5rem;"><label for="coupon-code" class="form-label">Coupon Code</label><div class="coupon-input-group"><input type="text" id="coupon-code" placeholder="Enter code" class="form-input uppercase"><button id="apply-coupon-btn" class="btn-secondary px-4">Apply</button></div><div id="coupon-message-container" class="mt-2"></div></div>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; border-top: 1px solid var(--color-gray-200); padding-top: 1.5rem;"><div class="summary-row"><p>Subtotal</p><p id="checkout-summary-subtotal">৳0.00</p></div><div id="checkout-discount-row" class="summary-row" style="color: var(--color-green-600); display: none;"><p>Discount (<span id="discount-percent">0</span>%)</p><p id="discount-amount">-৳0.00</p></div><div class="summary-total-row"><span>Total</span><span id="checkout-total">৳0.00</span></div></div>
                    <button id="pay-now-btn" data-action="pay-now" class="btn btn-primary" style="width: 100%; margin-top: 2rem; padding: 0.75rem; font-size: 1.125rem;">Place Order</button>
                </div>
                <div style="display: flex; flex-direction: column; gap: 2rem;">
                    <div class="checkout-form-section shadow-md"><h2 class="checkout-form-title">Billing Information</h2><form id="checkout-form"><div class="checkout-form-grid"><div><label for="full-name" class="form-label">Full Name</label><input type="text" id="full-name" class="form-input" required></div><div><label for="phone" class="form-label">Phone Number</label><input type="tel" id="phone" class="form-input" required></div><div class="span-2"><label for="email" class="form-label">Email Address</label><input type="email" id="email" class="form-input" required></div></div></form></div>
                    <div class="checkout-form-section shadow-md">
                        <h2 class="checkout-form-title">Payment Details</h2>
                        <div class="checkout-form-grid">
                            <div class="span-2"><label for="payment-method" class="form-label">Payment Method</label><select id="payment-method" class="form-select" required><option value="">Select Method</option><option value="bKash">bKash</option><option value="Nagad">Nagad</option><option value="Rocket">Rocket</option><option value="Upay">Upay</option></select></div>
                            <div id="payment-details-container" class="span-2" style="display: none;">
                                <div id="payment-instructions">
                                    <div class="instruction-header">
                                        <i class="fa-solid fa-shield-halved"></i>
                                        <span id="instruction-title">bKash Instructions</span>
                                    </div>
                                    <ul>
                                        <li><i class="fa-solid fa-check"></i> <span>Open <span id="app-name">bKash</span> & select '<span class="highlight">Send Money</span>'.</span></li>
                                        <li id="amount-instruction">
                                            <i class="fa-solid fa-check"></i>
                                            <span>Amount: <span id="payment-amount" class="highlight">৳0.00</span> to <span class="highlight">01757204719</span>.</span>
                                            <button id="copy-payment-number-btn" class="copy-btn"><i class="fa-regular fa-copy"></i></button>
                                        </li>
                                        <li><i class="fa-solid fa-check"></i> <span>Copy <span class="highlight">TrxID</span> & enter below.</span></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="span-2"><label for="transaction-id" class="form-label">Transaction ID (TrxID)</label><input type="text" id="transaction-id" class="form-input" placeholder="e.g., 8N7N2C8A1H" required></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="view-my-orders" class="page-view my-orders-page-container" style="display: none;">
            <h1 class="page-main-title">My Order History</h1>
            <div id="my-orders-container">
                <!-- Orders will be loaded here by JavaScript -->
            </div>
            <div id="no-orders-message" style="display: none;" class="cart-empty-state shadow-md">
                <i class="fa-solid fa-receipt" style="color: var(--color-gray-300);"></i>
                <h2>No Order History Found</h2>
                <p>It looks like you haven't placed any orders yet. Start shopping to see your orders here!</p>
                <button data-view="products" class="btn btn-primary">Start Shopping</button>
            </div>
             <div style="text-align: center; margin-top: 2rem;">
                <button id="show-find-order-form" class="btn btn-secondary">Find Order by Phone Number</button>
            </div>
            <div id="my-orders-form-container" class="checkout-form-section mt-8 shadow-md" style="display: none; max-width: 500px; margin-left:auto; margin-right:auto;">
                <p class="mb-4">Enter the phone number you used during checkout to find your order history.</p>
                <form id="my-orders-form" class="coupon-input-group">
                    <input type="tel" id="order-lookup-phone" class="form-input" placeholder="Enter your phone number" required>
                    <button type="submit" class="btn btn-primary">Find Orders</button>
                </form>
            </div>
        </div>

    </main>
    <footer class="footer-section">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h3>Roko Flux</h3>
                    <p>Your one-stop shop for premium digital courses, subscriptions, software, and ebooks. Empowering your digital journey with quality products and exceptional service.</p>
                </div>
                <div>
                    <h3>Contact Information</h3>
                    <ul class="footer-contact-list">
                        <li><i class="fa-solid fa-location-dot"></i><span>Digital Office, Dhaka, Bangladesh</span></li>
                        <li><i class="fa-solid fa-phone"></i><span>+8801757204719</span></li>
                        <li><i class="fa-solid fa-envelope"></i><span>support@rokoflux.com</span></li>
                    </ul>
                </div>
                <div>
                    <h3>Important Links</h3>
                    <ul class="footer-links-list">
                        <li><i class="fa-solid fa-info-circle"></i><a href="#">About Us</a></li>
                        <li><i class="fa-solid fa-file-contract"></i><a href="#">Terms & Conditions</a></li>
                        <li><i class="fa-solid fa-user-shield"></i><a href="#">Privacy Policy</a></li>
                        <li><i class="fa-solid fa-rotate-left"></i><a href="#">Refund Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-newsletter">
                <h3>Subscribe for Offers</h3>
                <form class="footer-newsletter-form">
                    <input type="email" placeholder="Your email address" class="footer-input">
                    <button class="btn btn-primary footer-button">Subscribe</button>
                </form>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 Roko Flux. All rights reserved.</p>
            </div>
        </div>
    </footer>


    <div id="review-modal" class="review-modal-overlay"><div class="review-modal-content shadow-2xl"><div class="review-modal-header"><h2>Write a Review</h2><button data-action="close-review-modal">&times;</button></div><form id="review-form" class="review-modal-form"><div><label for="review-name" class="form-label">Your Name</label><input type="text" id="review-name" class="form-input" required></div><div><label class="form-label">Rating</label><div class="star-rating"><input type="radio" id="star5" name="rating" value="5" required><label for="star5">☆</label><input type="radio" id="star4" name="rating" value="4"><label for="star4">☆</label><input type="radio" id="star3" name="rating" value="3"><label for="star3">☆</label><input type="radio" id="star2" name="rating" value="2"><label for="star2">☆</label><input type="radio" id="star1" name="rating" value="1"><label for="star1">☆</label></div></div><div><label for="review-comment" class="form-label">Your Review</label><textarea id="review-comment" class="form-textarea" required></textarea></div><div class="review-modal-footer"><button type="submit" class="btn btn-primary">Submit Review</button></div></form></div></div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const productsDatabase = <?php echo json_encode($js_database, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>;
        const couponsDatabase = <?php echo json_encode($coupons_data ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>;
        let state = { cart: [], checkoutItems: [], coupon: null, currentProductDetailId: null, currentProductDetailState: { selectedPricing: null } };
        const parseDescription = (text) => { if (!text) return ''; const tempDiv = document.createElement('div'); tempDiv.textContent = text; let safeText = tempDiv.innerHTML; const formattedText = safeText.replace(/\*(.*?)\*/g, '<strong>$1</strong>'); return formattedText; };
        const formatPrice = (price) => `৳${Number(price).toFixed(2)}`;
        const mobileSearchBtn = document.getElementById('mobile-search-btn');
        const mobileSearchBar = document.getElementById('mobile-search-bar');
        const sideMenuOverlay = document.querySelector('.side-menu-overlay');
        const sideMenu = document.querySelector('.side-menu');
        const openMenuBtns = document.querySelectorAll('.open-menu-btn');
        const closeMenuBtn = document.querySelector('.close-btn');
        const bottomNavLinks = document.querySelectorAll('.bottom-nav-link');
        const sideMenuCategoryLinks = document.querySelectorAll('#side-menu-categories a');
        const toggleSideMenu = (show) => { sideMenuOverlay.classList.toggle('active', show); sideMenu.classList.toggle('active', show); };
        mobileSearchBtn.addEventListener('click', () => { mobileSearchBar.style.display = mobileSearchBar.style.display === 'none' ? 'block' : 'none'; });
        openMenuBtns.forEach(btn => btn.addEventListener('click', () => toggleSideMenu(true)));
        closeMenuBtn.addEventListener('click', () => toggleSideMenu(false));
        sideMenuOverlay.addEventListener('click', () => toggleSideMenu(false));
        const updateBottomNav = (viewName) => { bottomNavLinks.forEach(link => { link.classList.remove('active'); if (link.dataset.view === viewName) link.classList.add('active'); }); };
        const switchView = (viewName, options = {}) => {
            document.querySelectorAll('.page-view').forEach(v => v.style.display = 'none');
            document.getElementById(`view-${viewName}`).style.display = 'block';
            window.scrollTo(0, 0);
            toggleSideMenu(false);
            updateBottomNav(viewName);
            state.currentProductDetailId = options.productId || null;
            if (options.productId) state.currentProductDetailState.selectedPricing = null;
            if (viewName === 'products') renderProductsPage(options);
            if (viewName === 'cart') renderCartPage();
            if (viewName === 'checkout') renderCheckoutPage(options.checkoutData);
            if (viewName === 'productDetail' && options.productId) renderProductDetailPage();
            if (viewName === 'my-orders') loadMyOrders();
        };
        const updateHeaderCartCount = () => { const total = state.cart.reduce((sum, item) => sum + item.quantity, 0); document.querySelectorAll('.cart-count-badge').forEach(el => { el.textContent = total; el.style.display = total > 0 ? 'flex' : 'none'; }); };
        function renderProductsPage({ searchTerm = '', categoryFilter = null } = {}) {
            const container = document.getElementById('products-grid-container'); 
            container.innerHTML = ''; 
            document.getElementById('products-page-title').textContent = categoryFilter ? `Products in "${categoryFilter}"` : 'All Products';
            
            const filteredProducts = Object.entries(productsDatabase).filter(([id, p]) => 
                (!searchTerm || p.name.toLowerCase().includes(searchTerm.toLowerCase())) && 
                (!categoryFilter || p.category === categoryFilter)
            );

            if (filteredProducts.length === 0) {
                container.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: var(--color-gray-500);">No products found.</p>';
                return;
            }

            filteredProducts.forEach(([id, p]) => {
                container.innerHTML += `<div class="product-grid-card"><div class="product-grid-card-img-wrapper">${p.available ? '' : '<div class="stock-out-badge">Stock Out</div>'}<img src="${p.image}" alt="${p.name}" onerror="this.style.display='none'"></div><div class="product-grid-card-content"><h3>${p.name}</h3><p class="description">${parseDescription(p.shortDescription)}</p><p class="price">${formatPrice(p.pricing[0].price)}</p><div class="product-grid-card-actions" style="flex-direction:row; gap:0.5rem; margin-top:auto;"><button data-action="buy-now" data-product-id="${id}" class="btn btn-primary" style="flex:1; padding:0.5rem;" ${p.available ? '' : 'disabled'}>Buy Now</button><button data-view="productDetail" data-product-id="${id}" class="btn btn-secondary" style="flex:1; padding:0.5rem;">Details</button></div></div></div>`;
            });
        }
        function renderProductDetailPage() {
            const product = productsDatabase[state.currentProductDetailId]; if (!product) return;
            if (!state.currentProductDetailState.selectedPricing) state.currentProductDetailState.selectedPricing = product.pricing[0];
            document.getElementById('pd-image-container').innerHTML = `<img src="${product.image}" alt="${product.name}" onerror="this.parentElement.innerHTML = 'Image not found.'">`;
            const availabilityClass = product.available ? 'in-stock' : 'out-of-stock';
            const availabilityText = product.available ? '[In Stock]' : '[Stock Out]';
            document.getElementById('pd-name').innerHTML = `${product.name} <span class="pd-availability ${availabilityClass}">${availabilityText}</span>`;
            document.getElementById('pd-description-short').innerHTML = parseDescription(product.shortDescription);
            document.getElementById('pd-description-long').innerHTML = parseDescription(product.longDescription);
            const durationContainer = document.getElementById('pd-duration-container');
            durationContainer.innerHTML = product.pricing.map(item => `<button data-action="select-duration" data-duration='${JSON.stringify(item)}' class="duration-btn ${state.currentProductDetailState.selectedPricing.duration === item.duration ? 'active' : ''}"><span>${item.duration}</span><i class="fa-solid fa-check-circle"></i></button>`).join('');
            document.getElementById('pd-price').textContent = formatPrice(state.currentProductDetailState.selectedPricing.price);
            document.getElementById('pd-duration-section').style.display = (product.pricing.length === 1 && product.pricing[0].duration === 'Default') ? 'none' : 'block';
            document.getElementById('pd-add-to-cart-btn').disabled = !product.available; document.getElementById('pd-buy-now-btn').disabled = !product.available;
            renderReviews(state.currentProductDetailId);
            renderRelatedProducts();
        }
        function renderReviews(productId) {
            const container = document.getElementById('pd-reviews-container'); const reviews = productsDatabase[productId]?.reviews || [];
            if (reviews.length === 0) { container.innerHTML = `<p style="color: var(--color-gray-500); text-align: center; padding: 2rem 0;">No reviews yet. Be the first to write one!</p>`; } 
            else { container.innerHTML = reviews.map(r => `<div class="review-item"><div class="avatar">${r.name.charAt(0).toUpperCase()}</div><div><p style="font-weight: bold;">${parseDescription(r.name)}</p><div style="color: var(--color-yellow-400); margin: 0.25rem 0;">${'★'.repeat(r.rating)}${'☆'.repeat(5 - r.rating)}</div><p style="margin-top: 0.5rem; color: var(--color-gray-700);">${parseDescription(r.comment)}</p></div></div>`).join(''); }
        }
       function renderRelatedProducts() {
            const currentProduct = productsDatabase[state.currentProductDetailId]; if (!currentProduct) return;
            const container = document.getElementById('related-products-grid');
            const section = document.getElementById('related-products-section');
            container.innerHTML = '';
            const relatedProducts = Object.entries(productsDatabase).filter(([id, p]) => p.category === currentProduct.category && id !== state.currentProductDetailId).slice(0, 3);
            
            if (relatedProducts.length > 0) {
                let productsHTML = '';
                relatedProducts.forEach(([id, p]) => {
                    productsHTML += `<div class="product-grid-card"><div class="product-grid-card-img-wrapper">${p.available ? '' : '<div class="stock-out-badge">Stock Out</div>'}<img src="${p.image}" alt="${p.name}" onerror="this.style.display='none'"></div><div class="product-grid-card-content"><h3>${p.name}</h3><p class="description">${parseDescription(p.shortDescription)}</p><p class="price">${formatPrice(p.pricing[0].price)}</p><div class="product-grid-card-actions" style="margin-top:auto;"><button data-view="productDetail" data-product-id="${id}" class="btn btn-primary" style="width:100%; padding:0.5rem;">View Details</button></div></div></div>`;
                });
                container.innerHTML = productsHTML;
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        }
        function addToCart(productId, selectedPricing) { const cartItemId = `${productId}-${selectedPricing.duration.replace(/\s+/g, '-')}`; const existingItem = state.cart.find(item => item.cartItemId === cartItemId); if (existingItem) { existingItem.quantity++; } else { state.cart.push({ cartItemId, id: productId, name: productsDatabase[productId].name, image: productsDatabase[productId].image, quantity: 1, pricing: selectedPricing }); } updateHeaderCartCount(); }
        function updateQuantity(cartItemId, amount) { const item = state.cart.find(i => i.cartItemId === cartItemId); if (item) { item.quantity += amount; if (item.quantity <= 0) state.cart = state.cart.filter(i => i.cartItemId !== cartItemId); } renderCartPage(); }
        function renderCartPage() {
            const emptyMsg = document.getElementById('cart-empty-message'); const content = document.getElementById('cart-content');
            if (state.cart.length === 0) { emptyMsg.style.display = 'block'; content.style.display = 'none'; updateHeaderCartCount(); return; }
            emptyMsg.style.display = 'none'; content.style.display = 'grid';
            document.getElementById('cart-items-container').innerHTML = state.cart.map(item => `<div class="cart-item"><div class="cart-item-img"><img src="${item.image}" alt="${item.name}"></div><div class="cart-item-info"><h3>${item.name}</h3><p>${item.pricing.duration} - ${formatPrice(item.pricing.price)}</p></div><div class="cart-item-qty"><button data-action="update-qty" data-cart-item-id="${item.cartItemId}" data-amount="-1">-</button><span class="p-2">${item.quantity}</span><button data-action="update-qty" data-cart-item-id="${item.cartItemId}" data-amount="1">+</button></div><div class="cart-item-total">${formatPrice(item.pricing.price * item.quantity)}</div><button data-action="remove-from-cart" data-cart-item-id="${item.cartItemId}" class="cart-item-remove-btn"><i class="fa-solid fa-trash"></i></button></div>`).join('');
            const subtotal = state.cart.reduce((sum, item) => sum + item.pricing.price * item.quantity, 0);
            document.getElementById('cart-summary-subtotal').textContent = formatPrice(subtotal); document.getElementById('cart-summary-total').textContent = formatPrice(subtotal);
            updateHeaderCartCount();
        }
        function renderCheckoutPage(buyNowData = null) {
            state.checkoutItems = buyNowData ? [buyNowData] : state.cart;
            const emptyMsg = document.getElementById('checkout-empty-cart'); const content = document.getElementById('checkout-content');
            if (state.checkoutItems.length === 0) { emptyMsg.style.display = 'block'; content.style.display = 'none'; return; }
            emptyMsg.style.display = 'none'; content.style.display = 'flex';
            document.getElementById('checkout-summary-items').innerHTML = state.checkoutItems.map(item => `<div class="checkout-summary-item"><div class="checkout-summary-item-info"><div class="checkout-summary-item-img"><img src="${item.image}" alt="${item.name}"></div><div><p>${item.name}</p><p class="text-sm text-gray-500">Qty: ${item.quantity}</p></div></div><p>${formatPrice(item.pricing.price * item.quantity)}</p></div>`).join('');
            removeCoupon(false); updateCheckoutTotals();
        }
        function updateCheckoutTotals() {
            const subtotal = state.checkoutItems.reduce((sum, item) => sum + item.pricing.price * item.quantity, 0); const discountRow = document.getElementById('checkout-discount-row');
            let total = subtotal;
            if (state.coupon) { const discountAmount = subtotal * (state.coupon.discount_percentage / 100); total -= discountAmount; discountRow.style.display = 'flex'; document.getElementById('discount-percent').textContent = state.coupon.discount_percentage; document.getElementById('discount-amount').textContent = `-${formatPrice(discountAmount)}`; } 
            else { discountRow.style.display = 'none'; }
            document.getElementById('checkout-summary-subtotal').textContent = formatPrice(subtotal); document.getElementById('checkout-total').textContent = formatPrice(total);
            document.getElementById('payment-amount').textContent = formatPrice(total);
        }
        function applyCoupon() { const codeInput = document.getElementById('coupon-code'); const code = codeInput.value.toUpperCase(); const msgContainer = document.getElementById('coupon-message-container'); const coupon = couponsDatabase.find(c => c.code === code && c.is_active); if (coupon) { state.coupon = coupon; msgContainer.innerHTML = `<div class="coupon-success">✓ Code ${coupon.code} applied! <button data-action="remove-coupon" class="remove-coupon-btn">Remove</button></div>`; codeInput.disabled = true; } else { state.coupon = null; msgContainer.innerHTML = `<div class="coupon-error">Invalid or inactive coupon code.</div>`; } updateCheckoutTotals(); }
        function removeCoupon(showAlert = true) { state.coupon = null; const codeInput = document.getElementById('coupon-code'); codeInput.value = ''; codeInput.disabled = false; document.getElementById('coupon-message-container').innerHTML = ''; updateCheckoutTotals(); }
        function validateCheckoutForm() { let isValid = true; document.querySelectorAll('#checkout-form [required], #payment-method, #transaction-id').forEach(input => { if (!input.value.trim()) { input.style.borderColor = 'var(--color-red-500)'; isValid = false; } else { input.style.borderColor = ''; } }); return isValid; }
        
        async function loadMyOrders() {
            const container = document.getElementById('my-orders-container');
            const noOrdersMessage = document.getElementById('no-orders-message');
            const findOrderForm = document.getElementById('my-orders-form-container');
            container.innerHTML = '<p>Loading your orders...</p>';
            noOrdersMessage.style.display = 'none';
            findOrderForm.style.display = 'none';

            let localOrders = JSON.parse(localStorage.getItem('myOrders')) || [];
            if (localOrders.length === 0) {
                container.innerHTML = '';
                noOrdersMessage.style.display = 'block';
                return;
            }
            
            try {
                const response = await fetch(`api.php?action=get_orders_by_ids&ids=${JSON.stringify(localOrders.map(o => o.order_id))}`);
                const orders = await response.json();
                
                if (orders.length === 0) {
                    container.innerHTML = '';
                    noOrdersMessage.style.display = 'block';
                } else {
                    orders.sort((a, b) => b.order_id - a.order_id);
                    container.innerHTML = orders.map(order => `<div class="order-card"><div class="order-header"><div><h3 class="font-bold">Order #${order.order_id}</h3><p class="text-sm text-gray-500">${order.order_date}</p></div><span class="status-badge status-${order.status}">${order.status}</span></div><div class="p-4">${order.items.map(item => `<p>${item.quantity}x ${item.name}</p>`).join('')}${order.coupon ? `<p class="text-green-600 text-sm mt-1">Coupon Used: ${order.coupon.code}</p>` : ''}<p class="font-bold mt-2 text-right">Total: ${formatPrice(order.totals.total)}</p></div></div>`).join('');
                }
            } catch (error) {
                container.innerHTML = `<p class="text-red-500 text-center py-8">Could not fetch orders. Please try again later.</p>`;
            }
        }

        async function findMyOrdersByPhone() {
            const phone = document.getElementById('order-lookup-phone').value; 
            const container = document.getElementById('my-orders-container');
            if (!phone) { alert('Please enter a phone number.'); return; }
            container.innerHTML = `<p>Searching for orders...</p>`;
            try {
                const response = await fetch(`api.php?action=get_orders_by_phone&phone=${encodeURIComponent(phone)}`);
                const orders = await response.json();
                if (orders.length === 0) { container.innerHTML = `<p class="text-gray-500 text-center py-8">No orders found for this phone number.</p>`; } 
                else {
                    orders.sort((a, b) => b.order_id - a.order_id);
                    container.innerHTML = orders.map(order => `<div class="order-card"><div class="order-header"><div><h3 class="font-bold">Order #${order.order_id}</h3><p class="text-sm text-gray-500">${order.order_date}</p></div><span class="status-badge status-${order.status}">${order.status}</span></div><div class="p-4">${order.items.map(item => `<p>${item.quantity}x ${item.name}</p>`).join('')}${order.coupon ? `<p class="text-green-600 text-sm mt-1">Coupon Used: ${order.coupon.code}</p>` : ''}<p class="font-bold mt-2 text-right">Total: ${formatPrice(order.totals.total)}</p></div></div>`).join('');
                }
            } catch (error) { container.innerHTML = `<p class="text-red-500 text-center py-8">Could not fetch orders. Please try again later.</p>`; }
        }

        document.getElementById('show-find-order-form').addEventListener('click', () => {
            const formContainer = document.getElementById('my-orders-form-container');
            formContainer.style.display = formContainer.style.display === 'none' ? 'block' : 'none';
        });

        document.getElementById('payment-method').addEventListener('change', (e) => {
            const paymentDetailsContainer = document.getElementById('payment-details-container');
            const method = e.target.value;
            if (method) {
                document.getElementById('instruction-title').textContent = `${method} Instructions`;
                document.getElementById('app-name').textContent = method;
                paymentDetailsContainer.style.display = 'block';
            } else {
                paymentDetailsContainer.style.display = 'none';
            }
        });
        document.getElementById('copy-payment-number-btn').addEventListener('click', (e) => {
            const button = e.currentTarget;
            const number = '01757204719';
            navigator.clipboard.writeText(number).then(() => {
                button.innerHTML = '<i class="fa-solid fa-check"></i>';
                setTimeout(() => { button.innerHTML = '<i class="fa-regular fa-copy"></i>'; }, 2000);
            });
        });
        document.body.addEventListener('click', async (e) => {
            const target = e.target.closest('[data-view], [data-action], [data-tab]'); if (!target) return;
            const { view, action, productId, categoryFilter, tab } = target.dataset;
            if (view) { e.preventDefault(); switchView(view, { productId, categoryFilter }); }
            if (tab) { document.querySelectorAll('.pd-tab-btn').forEach(b => b.classList.remove('active')); target.classList.add('active'); document.querySelectorAll('.pd-tab-content').forEach(c => c.style.display = c.id.includes(tab) ? 'block' : 'none'); }
            if (action) {
                e.preventDefault();
                if (action === 'select-duration') { state.currentProductDetailState.selectedPricing = JSON.parse(target.dataset.duration); renderProductDetailPage(); }
                if (action === 'add-to-cart') { const id = state.currentProductDetailId; const pricing = state.currentProductDetailState.selectedPricing; addToCart(id, pricing); alert(`${productsDatabase[id].name} has been added to cart.`); }
                if (action === 'buy-now') { const id = productId || state.currentProductDetailId; const product = productsDatabase[id]; const pricing = productId ? product.pricing[0] : state.currentProductDetailState.selectedPricing; switchView('checkout', { checkoutData: { id, name: product.name, image: product.image, quantity: 1, pricing: pricing } }); }
                if (action === 'update-qty') { updateQuantity(target.dataset.cartItemId, parseInt(target.dataset.amount)); }
                if (action === 'remove-from-cart') { state.cart = state.cart.filter(item => item.cartItemId !== target.dataset.cartItemId); renderCartPage(); }
                if (action === 'remove-coupon') { removeCoupon(); }
                if (action === 'pay-now') { 
                    if (validateCheckoutForm()) { 
                        const customerPhone = document.getElementById('phone').value;
                        const orderPayload = { action: 'place_order', order: { customerInfo: { name: document.getElementById('full-name').value, phone: customerPhone, email: document.getElementById('email').value }, paymentInfo: { method: document.getElementById('payment-method').value, trx_id: document.getElementById('transaction-id').value }, items: state.checkoutItems, coupon: state.coupon } }; 
                        try { 
                            const response = await fetch('api.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(orderPayload) }); 
                            const result = await response.json(); 
                            if(result.success && result.order_id) { 
                                alert('Order placed successfully! We will verify your payment and contact you shortly.'); 
                                // Save order to local storage
                                let localOrders = JSON.parse(localStorage.getItem('myOrders')) || [];
                                localOrders.push({ order_id: result.order_id, phone: customerPhone });
                                localStorage.setItem('myOrders', JSON.stringify(localOrders));

                                if (state.checkoutItems === state.cart) { state.cart = []; updateHeaderCartCount(); } 
                                state.checkoutItems = []; 
                                document.getElementById('checkout-form').reset(); 
                                removeCoupon(false); 
                                switchView('my-orders'); 
                            } else { throw new Error(result.message || 'Order placement failed'); } 
                        } catch (err) { alert('There was an error placing your order. Please try again.'); } 
                    } else { alert('Please fill in all required fields, including payment details.'); } 
                }
                if (action === 'open-review-modal') { document.getElementById('review-modal').style.display = 'flex'; }
                if (action === 'close-review-modal') { document.getElementById('review-modal').style.display = 'none'; }
            }
        });
        document.getElementById('apply-coupon-btn').addEventListener('click', (e) => { e.preventDefault(); applyCoupon(); });
        document.getElementById('my-orders-form').addEventListener('submit', (e) => { e.preventDefault(); findMyOrdersByPhone(); });
        document.getElementById('review-form').addEventListener('submit', async (e) => {
            e.preventDefault(); const ratingInput = document.querySelector('input[name="rating"]:checked'); if (!ratingInput) { alert('Please select a star rating.'); return; }
            const reviewData = { productId: state.currentProductDetailId, name: document.getElementById('review-name').value, rating: parseInt(ratingInput.value), comment: document.getElementById('review-comment').value };
            const reviewPayload = { action: 'add_review', review: reviewData };
            try {
                const response = await fetch('api.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(reviewPayload) });
                const result = await response.json();
                if (result.success) {
                    alert('Thank you! Your review has been submitted.');
                    if (!productsDatabase[state.currentProductDetailId].reviews) { productsDatabase[state.currentProductDetailId].reviews = []; }
                    productsDatabase[state.currentProductDetailId].reviews.push(reviewData); renderReviews(state.currentProductDetailId); document.getElementById('review-form').reset(); document.getElementById('review-modal').style.display = 'none';
                } else { throw new Error(result.message || 'Review submission failed'); }
            } catch (err) { alert('Could not submit review. Please try again. Error: ' + err.message); }
        });
        sideMenuCategoryLinks.forEach(link => { link.addEventListener('click', (e) => { e.preventDefault(); const { view, categoryFilter } = link.dataset; switchView(view, { categoryFilter }); }); });
        switchView('home');
    });
    </script>
</body>
</html>
