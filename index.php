<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/api/products.php';

$productsAPI = new ProductsAPI();
$featuredProducts = $productsAPI->getProducts(null, 1, 8);
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
        // Use name as slug if DB category not found
        $display_categories[] = [
            'id' => $dc['name'],
            'name' => $dc['name'],
            'description' => '',
            'image_url' => $dc['image']
        ];
    }
}

// Build quick lookup for images by category name
$image_map = [];
foreach ($desired_categories as $dc) {
    $image_map[$dc['name']] = $dc['image'];
}

// Prepare featured products to show specific categories in order
// Prepare featured products by specific product records (search by product name)
$featured_searches = [
    ['search' => 'Camote', 'image' => 'product_pictures/Camote_Chips.jpg'],
    ['search' => 'Himalayan Salted Cashew', 'image' => 'product_pictures/Himalayan_Salted_Cashew.jpg'],
    ['search' => 'Premium Mix Nuts', 'image' => 'product_pictures/Premium_Mix_Nuts.jpg'],
    ['search' => 'Sweet And Spicy Dilis', 'image' => 'product_pictures/Sweet_And_Spicy_Dilis.jpg'],
];

$featured_override = [];
foreach ($featured_searches as $fs) {
    $p = $productsAPI->getProductByName($fs['search']);
    if (!empty($p) && !isset($p['error'])) {
        // override preview image with requested one
        $p['image_url'] = $fs['image'];
        $featured_override[] = $p;
        continue;
    }
    // fallback: try find by category name
    $found = $productsAPI->getProductsByCategoryName($fs['search'], 1);
    if (!empty($found) && !isset($found['error'])) {
        $prod = $found[0];
        $prod['image_url'] = $fs['image'];
        $featured_override[] = $prod;
    }
}

if (!empty($featured_override)) {
    $featuredProducts = $featured_override;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo SITE_TAGLINE; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/inc/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h2>Authentic Filipino Pasalubong</h2>
            <p>Bringing the taste of home to your table</p>
            <a href="products.php" class="btn btn-primary">Shop Now</a>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories-section">
        <div class="container">
            <h2 class="section-title">Shop by Category</h2>
            <div class="categories-grid">
                <?php foreach ($display_categories as $category): ?>
                <a href="products.php?category=<?php echo urlencode($category['id']); ?>" class="category-card">
                    <div class="category-image">
                        <img src="<?php echo get_product_image($category['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($category['name']); ?>">
                    </div>
                    <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                    <p><?php echo htmlspecialchars($category['description']); ?></p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Best Sellers Section -->
    <section class="products-section">
        <div class="container">
            <h2 class="section-title">Best Sellers</h2>
            <div class="products-grid">
                <?php if (!empty($featuredProducts) && !isset($featuredProducts['error'])): ?>
                    <?php foreach ($featuredProducts as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                                <?php
                                    $display_image = $product['image_url'];
                                    if (!empty($product['category_name']) && isset($image_map[$product['category_name']])) {
                                        $display_image = $image_map[$product['category_name']];
                                    }
                                ?>
                                <img src="<?php echo get_product_image($display_image); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php if ($product['original_price']): ?>
                            <span class="badge sale">Sale</span>
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
                            <button class="btn btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                            <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn btn-view">
                                View Details
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No featured products available at the moment.</p>
                <?php endif; ?>
            </div>
            <div class="text-center">
                <a href="products.php" class="btn btn-secondary">View All Products</a>
            </div>
        </div>
    </section>

    <!-- About Preview Section -->
    <section class="about-preview">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>About Izzamawy Pastry and Delicacies</h2>
                    <p>We specialize in authentic Filipino pasalubong and delicacies, crafted with love and tradition. From crispy fish crackers to delectable cookies and local sweets, every product is made with the finest ingredients to bring the taste of home to your table.</p>
                    <p>Perfect for gifts, celebrations, or simply treating yourself to the flavors of the Philippines.</p>
                    <a href="about.php" class="btn btn-outline">Learn More</a>
                </div>
                <div class="about-image">
                    <img src="pictures_of_the_owner/OWNER WITH STAFF.jpg" alt="Izzamawy Pastry and Delicacies">
                </div>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/inc/footer.php'; ?>

    <script src="js/main.js"></script>
</body>
</html>

