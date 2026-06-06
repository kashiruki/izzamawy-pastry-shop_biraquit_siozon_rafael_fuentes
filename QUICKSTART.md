# Quick Start Guide

## Fastest Way to Get Started

### 1. Create Database
```bash
mysql -u root -p
```
```sql
CREATE DATABASE izzamawy_pastry_shop;
USE izzamawy_pastry_shop;
SOURCE database/schema.sql;
EXIT;
```

### 2. Start Server
```bash
cd /Users/family/Desktop/Web/izzamawy-pastry-shop
php -S localhost:8000
```

### 3. Access Website
- **Frontend:** http://localhost:8000
- **Admin Panel:** http://localhost:8000/admin/login.php

### 4. Login to Admin
```
Username: admin
Password: admin123
```

## Troubleshooting

### Database Connection Failed
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'izzamawy_pastry_shop');
define('DB_USER', 'root');
define('DB_PASS', ''); // Your MySQL password
```

### Port Already in Use
Try a different port:
```bash
php -S localhost:8080
```

### Images Not Showing
Make sure these directories exist:
```bash
mkdir -p images/products images/categories
```

## Project Structure Quick Reference

```
izzamawy-pastry-shop/
├── index.php          → Homepage
├── products.php       → Product listing
├── cart.php           → Shopping cart
├── checkout.php       → Checkout
├── admin/login.php    → Admin login
├── admin/dashboard.php → Admin dashboard
├── api/               → Backend APIs
├── config/            → Configuration
├── css/               → Stylesheets
├── js/                → JavaScript
└── database/          → Database schema
```

## Quick Commands

### Start Development Server
```bash
php -S localhost:8000
```

### Import Database
```bash
mysql -u root -p izzamawy_pastry_shop < database/schema.sql
```

### Make Setup Script Executable
```bash
chmod +x setup.sh
./setup.sh
```

## Default Test Data

The database includes:
- 4 product categories
- 9 sample products
- 1 admin account (admin/admin123)

## Features Checklist

- [x] Product catalog with categories
- [x] Search functionality
- [x] Shopping cart
- [x] Checkout system
- [x] Order management
- [x] Admin dashboard
- [x] Responsive design
- [x] Mobile-friendly

## Support

For issues, check:
1. Database connection in `config/database.php`
2. PHP error logs
3. Browser console for JavaScript errors
4. MySQL is running
5. Correct file permissions

## Security Reminders

1. Change admin password immediately
2. Update database credentials
3. Set error_reporting(0) in production
4. Use HTTPS in production
5. Keep software updated

---

**Ready to start selling! 🎉**
