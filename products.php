<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/api/products.php';

$productsAPI = new ProductsAPI();
$categories = $productsAPI->getCategories();

// Desired category order and preview images (display-only)
$desired_categories = [
    ['name' => 'Chips', 'image' => 'product_pictures/Camote_Chips.jpg'],
    ['name' => 'Cashew', 'image' => 'product_pictures/Himalayan_Salted_Cashew.jpg'],
    ['name' => 'Nuts', 'image' => 'product_pictures/Premium_Mix_Nuts.jpg'],
    ['name' => 'Dilis', 'image' => 'product_pictures/Sweet_And_Spicy_Dilis.jpg'],
    ['name' => 'Assorted Products', 'image' => 'product_pictures/Assorted_Products_1.jpg'],
    ['name' => 'Bundle', 'image' => 'product_pictures/Bundle_1.jpg'],
    ['name' => 'Gift Box', 'image' => 'product_pictures/Gift_Box_1.jpg'],
];

// Build display categories array keeping links to DB where possible
$display_categories = [];
foreach ($desired_categories as $dc) {
    $cat = $productsAPI->getCategoryByName($dc['name']);
    if (is_array($cat) && isset($cat['id'])) {
        $display_categories[] = [
            'id' => $cat['id'],
            'name' => $cat['name'],
            'description' => $cat['description'] ?? '',
            'image_url' => $dc['image']
        ];
    } else {
        $display_categories[] = [
            'id' => $dc['name'],
            'name' => $dc['name'],
            'description' => '',
            'image_url' => $dc['image']
        ];
    }
}

// Get filter parameters
$category_param = $_GET['category'] ?? null;
$search = $_GET['search'] ?? '';

// Get products based on filters
if ($search) {
    $products = $productsAPI->searchProducts($search);
    $current_category = null;
} else {
    if ($category_param) {
        if (is_numeric($category_param)) {
            $category_id = (int)$category_param;
            $products = $productsAPI->getProducts($category_id);
            $current_category = $productsAPI->getCategory($category_id);
        } else {
            // Attempt to find DB category by name; otherwise fetch by category name
            $cat = $productsAPI->getCategoryByName($category_param);
            if (is_array($cat) && isset($cat['id'])) {
                $category_id = $cat['id'];
                $products = $productsAPI->getProducts($category_id);
                $current_category = $cat;
            } else {
                // Fetch products by category name (may return empty if DB has no such category)
                $products = $productsAPI->getProductsByCategoryName($category_param);
                $current_category = ['name' => $category_param, 'description' => ''];
            }
        }
    } else {
        $products = $productsAPI->getProducts(null);
        $current_category = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/inc/header.php'; ?>

    <section class="page-header">
        <div class="container">
            <h1><?php echo $current_category ? htmlspecialchars($current_category['name']) : 'All Products'; ?></h1>
            <p><?php echo $current_category ? htmlspecialchars($current_category['description']) : 'Browse our complete selection'; ?></p>
        </div>
    </section>

    <section class="products-page">
        <div class="container">
            <div class="products-layout">
                <!-- Sidebar Filters -->
                <aside class="products-sidebar">
                    <h3>Categories</h3>
                    <ul class="category-filter">
                        <li>
                            <a href="products.php" class="<?php echo empty($category_param) ? 'active' : ''; ?>">All Products</a>
                        </li>
                        <?php foreach ($display_categories as $category): ?>
                        <li>
                            <a href="products.php?category=<?php echo urlencode($category['id']); ?>" class="<?php echo (isset($category_param) && (string)$category_param === (string)$category['id']) ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </aside>

                <!-- Products Grid -->
                <div class="products-main">
                    <div class="products-header">
                        <p class="products-count"><?php echo count($products); ?> products found</p>
                    </div>

                    <div class="products-grid">
                        <!-- React products mount point (hidden for now, server rendering preserved) -->
                        <div id="react-products-root" data-products-count="<?php echo count($products); ?>" style="display:none"></div>
                        <?php if (!empty($products) && !isset($products['error'])): ?>
                            <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                            <?php
                                                // Determine display image by exact product name (preferred), then keyword fallback
                                                $display_image = $product['image_url'];
                                                $name = $product['name'] ?? '';
                                                $name_l = strtolower($name);

                                                // Exact name -> image map for specific products
                                                $exact_map = [
                                                    'adobo garlic cashew' => 'product_pictures/Adobo_Garlic_Cashew.jpg',
                                                    'camote chips' => 'product_pictures/Camote_Chips.jpg',
                                                    'camote truffle chips' => 'product_pictures/Camote_Truffle_Chips.jpg',
                                                    'gabi taro chips' => 'product_pictures/Gabi_Taro_Chips.jpg',
                                                    'banana chips' => 'product_pictures/Banana_Chips.jpg',
                                                    'taro adobo gata chips' => 'product_pictures/Taro_Adobo_Gata_Chips.jpg',
                                                    'taro adobo gabi chips' => 'product_pictures/Taro_Adobo_Gabi_Chips.jpg',
                                                    'crunchy garlic chips' => 'product_pictures/Crunchy_Garlic_Chips.jpg',
                                                    'baked king cashew' => 'product_pictures/Baked_KIng_Cashew.jpg',
                                                    'premium whole cashew' => 'product_pictures/Premium_Whole_Cashew.jpg',
                                                    'himalayan salted cashew' => 'product_pictures/Himalayan_Salted_Cashew.jpg',
                                                    'creamy mixed nuts' => 'product_pictures/Creamy_Mixed_Nuts.jpg',
                                                    'premium mixed nuts' => 'product_pictures/Premium_Mix_Nuts.jpg',
                                                    'premium mix nuts' => 'product_pictures/Premium_Mix_Nuts.jpg',
                                                    'crispy dilis' => 'product_pictures/Crispy_Dilis.jpg',
                                                    'crispy dilis adobo flavor' => 'product_pictures/Crispy_Dilis_Adobo_Flavor_2.jpg',
                                                    'sweet and spicy dilis' => 'product_pictures/Sweet_And_Spicy_Dilis.jpg',
                                                    'assorted product #1' => 'product_pictures/Assorted_Products_1.jpg',
                                                    'bundle #1' => 'product_pictures/Bundle_1.jpg',
                                                    'bundle #2' => 'product_pictures/Bundle_2.jpg',
                                                    'bundle #3' => 'product_pictures/Bundle_3.jpg',
                                                    'gift box #1' => 'product_pictures/Gift_Box_1.jpg',
                                                ];

                                                foreach ($exact_map as $k => $v) {
                                                    if (trim($name_l) === $k || strpos($name_l, $k) !== false) {
                                                        $display_image = $v;
                                                        break;
                                                    }
                                                }

                                                // Keyword fallback map
                                                $mapping = [
                                                    'camote' => 'product_pictures/Camote_Chips.jpg',
                                                    'cashew' => 'product_pictures/Himalayan_Salted_Cashew.jpg',
                                                    'premium' => 'product_pictures/Premium_Mix_Nuts.jpg',
                                                    'nuts' => 'product_pictures/Premium_Mix_Nuts.jpg',
                                                    'dilis' => 'product_pictures/Sweet_And_Spicy_Dilis.jpg',
                                                    'assorted' => 'product_pictures/Assorted_Products_1.jpg',
                                                    'bundle' => 'product_pictures/Bundle_1.jpg',
                                                    'gift' => 'product_pictures/Gift_Box_1.jpg',
                                                ];

                                                foreach ($mapping as $k => $v) {
                                                    if (strpos($name_l, $k) !== false) {
                                                        $display_image = $v;
                                                        break;
                                                    }
                                                }
                                            ?>
                                            <img src="<?php echo get_product_image($display_image); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php if ($product['original_price']): ?>
                                    <span class="badge sale">Sale</span>
                                    <?php endif; ?>
                                    <?php if ($product['stock_quantity'] <= 0): ?>
                                    <span class="badge out-of-stock">Out of Stock</span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                                    <div class="product-price">
                                        <span class="price"><?php echo format_price($product['price']); ?></span>
                                        <?php if ($product['original_price']): ?>
                                        <span class="original-price"><?php echo format_price($product['original_price']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                    <button class="btn btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-disabled" disabled>Out of Stock</button>
                                    <?php endif; ?>

                                    <!-- View Details toggle -->
                                    <button type="button" class="btn btn-view js-toggle-details" data-target="details-<?php echo $product['id']; ?>">
                                        View Details
                                    </button>
                                    <div id="details-<?php echo $product['id']; ?>" class="product-details" aria-hidden="true">
                                        <p><?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-products">
                                <i class="fas fa-box-open"></i>
                                <p>No products found.</p>
                                <a href="products.php" class="btn btn-primary">View All Products</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/inc/footer.php'; ?>

    <script src="js/main.js"></script>
    <script>
        // Toggle product details panels
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.js-toggle-details');
            if (!btn) return;
            var targetId = btn.getAttribute('data-target');
            var panel = document.getElementById(targetId);
            if (!panel) return;
            var isOpen = panel.classList.contains('open');
            if (isOpen) {
                panel.classList.remove('open');
                panel.setAttribute('aria-hidden', 'true');
                btn.textContent = 'View Details';
            } else {
                panel.classList.add('open');
                panel.setAttribute('aria-hidden', 'false');
                btn.textContent = 'Hide Details';
            }
        });
    </script>
</body>
</html>
