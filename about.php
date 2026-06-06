<?php
require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/inc/header.php'; ?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>About Us</h1>
            <p>Learn more about our story and passion</p>
        </div>
    </section>

    <!-- About Content -->
    <section class="about-preview">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>Our Story</h2>
                    <p>Izzamawy Pastry and Delicacies was founded with a simple mission: to bring the authentic taste of Filipino pasalubong and delicacies to every household. Our journey began with a passion for traditional Filipino flavors and a commitment to quality that has never wavered.</p>
                    
                    <p>Every product we create is made with love, using time-honored recipes passed down through generations. From our crispy fish crackers to our melt-in-your-mouth cookies and traditional local delicacies, each item tells a story of Filipino culinary heritage.</p>
                    
                    <h2 style="margin-top: 30px;">What We Offer</h2>
                    <p><strong>Chips:</strong> Crispy, flavorful, and made from the freshest ingredients. Perfect for snacking or as pasalubong.</p>
                    
                    <p><strong>Cashew:</strong> From classic butter cookies to chocolate-studded treats, our cookies are baked fresh and packed with flavor.</p>
                    
                    <p><strong>Nuts:</strong> Traditional Filipino sweets like polvoron, yema, and pastillas that remind you of home.</p>
                    
                    <p><strong>Dilis</strong> Beautifully packaged assortments perfect for any occasion.</p>
                    
                    <h2 style="margin-top: 30px;">Our Promise</h2>
                    <p>We are committed to:</p>
                    <ul style="margin-left: 20px; margin-bottom: 20px;">
                        <li>✓ Using only the finest ingredients</li>
                        <li>✓ Maintaining traditional recipes and flavors</li>
                        <li>✓ Ensuring freshness in every product</li>
                        <li>✓ Providing excellent customer service</li>
                        <li>✓ Supporting local communities</li>
                    </ul>
                </div>
                <div class="about-image">
                    <img src="logo.jpg" alt="Izzamawy Pastry and Delicacies" style="border-radius: 10px;">
                </div>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/inc/footer.php'; ?>

    <script src="js/main.js"></script>
</body>
</html>
