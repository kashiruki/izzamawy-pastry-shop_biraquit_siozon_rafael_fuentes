<?php
require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/inc/header.php'; ?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>Contact Us</h1>
            <p>We'd love to hear from you</p>
        </div>
    </section>

    <!-- Contact Section -->
    <section style="padding: 60px 0;">
        <div class="container">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: start;">
                <!-- Contact Info -->
                <div>
                    <h2 style="font-size: 32px; margin-bottom: 30px; color: var(--primary-color);">Get In Touch</h2>
                    <p style="font-size: 18px; color: var(--text-light); margin-bottom: 40px;">Have questions about our products or need assistance with your order? Feel free to reach out to us!</p>
                    
                    <div style="margin-bottom: 30px;">
                        <div style="display: flex; align-items: start; gap: 20px; margin-bottom: 25px;">
                            <i class="fas fa-map-marker-alt" style="font-size: 24px; color: var(--primary-color); margin-top: 5px;"></i>
                            <div>
                                <h3 style="font-size: 20px; margin-bottom: 5px;">Address</h3>
                                <p style="color: var(--text-light);">Brgy. Sapang Maisac, Mexico, Pampanga, Philippines</p>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: start; gap: 20px; margin-bottom: 25px;">
                            <i class="fas fa-phone" style="font-size: 24px; color: var(--primary-color); margin-top: 5px;"></i>
                            <div>
                                <h3 style="font-size: 20px; margin-bottom: 5px;">Phone</h3>
                                <p style="color: var(--text-light);">0935-200-7268/0965-368-8096</p>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: start; gap: 20px; margin-bottom: 25px;">
                            <i class="fas fa-envelope" style="font-size: 24px; color: var(--primary-color); margin-top: 5px;"></i>
                            <div>
                                <h3 style="font-size: 20px; margin-bottom: 5px;">Email</h3>
                                <p style="color: var(--text-light);">izzamawy14@gmail.com</p>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: start; gap: 20px;">
                            <i class="fas fa-clock" style="font-size: 24px; color: var(--primary-color); margin-top: 5px;"></i>
                            <div>
                                <h3 style="font-size: 20px; margin-bottom: 5px;">Business Hours</h3>
                                <p style="color: var(--text-light);">10:00am - 9:00pm</p>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 40px;">
                        <h3 style="font-size: 20px; margin-bottom: 15px;">Follow Us</h3>
                        <div class="social-icons">
                            <a href="#" style="background-color: var(--primary-color); width: 50px; height: 50px;"><i class="fab fa-facebook"></i></a>
                            <a href="#" style="background-color: var(--primary-color); width: 50px; height: 50px;"><i class="fab fa-instagram"></i></a>
                            <a href="#" style="background-color: var(--primary-color); width: 50px; height: 50px;"><i class="fab fa-tiktok"></i></a>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div style="background-color: var(--bg-light); padding: 40px; border-radius: 10px;">
                    <h2 style="font-size: 28px; margin-bottom: 30px;">Send us a Message</h2>
                    
                    <form id="contactForm">
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Your Name *</label>
                            <input type="text" required style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 5px; font-size: 16px;">
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Email Address *</label>
                            <input type="email" required style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 5px; font-size: 16px;">
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Subject *</label>
                            <input type="text" required style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 5px; font-size: 16px;">
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Message *</label>
                            <textarea required rows="6" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 5px; font-size: 16px; font-family: inherit;"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/inc/footer.php'; ?>

    <script src="js/main.js"></script>
    <script>
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Thank you for your message! We will get back to you soon.');
            this.reset();
        });
    </script>
</body>
</html>
