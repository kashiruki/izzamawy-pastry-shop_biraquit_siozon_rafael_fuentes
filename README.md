# Izzamawy Pastry Shop

A complete e-commerce system for a Filipino pastry shop specializing in local delicacies and pasalubong items (fish crackers, cookies, and traditional Filipino treats).

## 🍪 Features

- **Modern, Responsive Design** - Inspired by contemporary e-commerce sites with a clean, professional look
- **Product Catalog** - Browse products by category with search functionality
- **Shopping Cart** - Add, update, and remove items with real-time updates
- **Checkout System** - Complete order processing with shipping calculations
- **Admin Panel** - Manage products, orders, and inventory
- **Mobile-Friendly** - Fully responsive design for all devices

## 🛠️ Tech Stack

### Frontend
- HTML5
- CSS3 (Custom styling with CSS variables)
- Vanilla JavaScript (ES6+)

### Backend
- PHP 7.4+
- MySQL 5.7+

### Libraries
- Font Awesome 6.4.0 (Icons)

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Web browser (Chrome, Firefox, Safari, Edge)

## 🚀 Installation

### 1. Clone or Download the Project

```bash
cd /Users/family/Desktop/Web/izzamawy-pastry-shop
```

### 2. Database Setup

1. **Create the database:**
   - Open phpMyAdmin or MySQL command line
   - Create a new database named `izzamawy_pastry_shop`

2. **Import the schema:**
   ```bash
   mysql -u root -p izzamawy_pastry_shop < database/schema.sql
   ```

   Or through phpMyAdmin:
   - Select the database
   - Go to "Import" tab
   - Choose `database/schema.sql`
   - Click "Go"

### 3. Configure Database Connection

Edit `config/database.php` if needed (default settings):

```php
define('DB_HOST', 'localhost');
   define('DB_NAME', 'izzamawy_pastry_shop');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 4. Set Up Web Server

#### Using XAMPP/MAMP:
1. Move the project folder to `htdocs` (XAMPP) or `htdocs` (MAMP)
2. Start Apache and MySQL
3. Access: `http://localhost/izzamawy-pastry-shop`

#### Using Built-in PHP Server:
```bash
cd /Users/family/Desktop/Web/izzamawy-pastry-shop
php -S localhost:8000
```
Access: `http://localhost:8000`

### 5. Create Images Directories

```bash
mkdir -p images/products images/categories
```

Add placeholder images or your product photos to these directories.

## 🔑 Default Admin Credentials

```
Username: admin
Password: admin123
```

**⚠️ Important:** Change these credentials after first login!

## 📁 Project Structure

```
izzamawy-pastry-shop/
├── admin/                  # Admin panel
│   ├── login.php          # Admin login
│   ├── dashboard.php      # Admin dashboard
│   └── logout.php         # Logout handler
├── api/                   # Backend API
│   ├── products.php       # Product operations
│   ├── cart.php           # Cart operations
│   └── orders.php         # Order processing
├── config/                # Configuration
│   ├── config.php         # General settings
│   └── database.php       # Database connection
├── css/                   # Stylesheets
│   └── style.css          # Main stylesheet
├── database/              # Database files
│   └── schema.sql         # Database schema
├── images/                # Image assets
│   ├── products/          # Product images
│   └── categories/        # Category images
├── js/                    # JavaScript files
│   ├── main.js            # Main functionality
│   ├── cart.js            # Cart operations
│   └── checkout.js        # Checkout logic
├── index.php              # Homepage
├── products.php           # Products listing
├── cart.php               # Shopping cart
├── checkout.php           # Checkout page
└── README.md              # This file
```

## 🎯 Usage

### Customer Features

1. **Browse Products**
   - Visit homepage to see featured products
   - Click "Products" to view all items
   - Use category filters to narrow results
   - Search for specific products

2. **Shopping Cart**
   - Click "Add to Cart" on any product
   - View cart by clicking cart icon
   - Update quantities or remove items
   - Proceed to checkout

3. **Place Order**
   - Fill in customer information
   - Enter shipping address
   - Select payment method
   - Review and submit order

### Admin Features

1. **Login**
   - Go to `http://localhost/izzamawy-pastry-shop/admin/login.php`
   - Use admin credentials

2. **Dashboard**
   - View statistics (products, orders, revenue)
   - Monitor recent orders
   - Check low stock alerts

3. **Manage Products**
   - Add new products
   - Edit existing products
   - Update stock levels
   - Manage categories

## 🔧 Configuration

### Shipping Settings

Edit `config/config.php`:

```php
define('SHIPPING_FEE_METRO_MANILA', 100);
define('SHIPPING_FEE_PROVINCIAL', 200);
define('FREE_SHIPPING_THRESHOLD', 1000);
```

### Payment Methods

Update payment methods in `config/config.php`:

```php
define('PAYMENT_METHODS', [
    'cod' => 'Cash on Delivery',
    'bank' => 'Bank Transfer',
    'gcash' => 'GCash',
    'paymaya' => 'PayMaya'
]);
```

## 🎨 Customization

### Colors

Edit CSS variables in `css/style.css`:

```css
:root {
    --primary-color: #d4a574;
    --secondary-color: #8b6f47;
    --accent-color: #e8c4a0;
    /* ... more colors */
}
```

### Site Information

Edit `config/config.php`:

```php
define('SITE_NAME', 'Izzamawy Pastry Shop');
define('SITE_TAGLINE', 'Authentic Filipino Pasalubong & Delicacies');
define('ADMIN_EMAIL', 'admin@izzamawy.com');
```

## 📱 Responsive Breakpoints

- Desktop: 1200px+
- Tablet: 768px - 1199px
- Mobile: < 768px

## 🐛 Troubleshooting

### Database Connection Error
- Check database credentials in `config/database.php`
- Ensure MySQL is running
- Verify database exists

### Images Not Loading
- Check image paths in database
- Ensure images directory has proper permissions
- Use placeholder images if product images are missing

### Cart Not Working
- Check if PHP sessions are enabled
- Clear browser cookies
- Check browser console for JavaScript errors

### Admin Login Issues
- Verify admin user exists in database
- Check password hash (use `password_hash()` in PHP)
- Clear browser cache

## 🔒 Security Notes

1. **Change default admin password immediately**
2. **Use HTTPS in production**
3. **Update database credentials**
4. **Set proper file permissions**
5. **Keep PHP and MySQL updated**
6. **Validate and sanitize all inputs**

## 📝 Sample Data

The database schema includes sample data:
- 4 categories (Fish Crackers, Cookies, Local Delicacies, Gift Boxes)
- 9 products with various prices
- 1 admin user (admin/admin123)

## 🚀 Deployment

### For Production:

1. **Update configuration:**
   ```php
   error_reporting(0);
   ini_set('display_errors', 0);
   ```

2. **Secure database:**
   - Use strong database password
   - Limit database user privileges

3. **Enable HTTPS:**
   - Get SSL certificate
   - Update SITE_URL in config

4. **Optimize images:**
   - Compress product images
   - Use WebP format

5. **Set up backups:**
   - Regular database backups
   - File system backups

## 📞 Support

For issues or questions:
- Email: admin@izzamawy.com
- Check database schema for structure
- Review error logs

## 📄 License

This project is open source and available for educational purposes.

## 👨‍💻 Development

Built with vanilla technologies:
- No frameworks
- No build tools required
- Simple, clean code
- Easy to understand and modify

## 🎉 Credits

Design inspired by modern e-commerce platforms like Mod & Noble Design Studio.

---

**Happy Selling! 🍪🥮🎁**
# izzamawy-pastry-shop_biraquit_siozon_rafael_fuentess
