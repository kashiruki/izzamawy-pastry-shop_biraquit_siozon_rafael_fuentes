# 🚀 Easy Setup Guide - Izzamawy Pastry Shop

Follow these simple steps to get your system running!

---

## Step 1: Setup Database (Choose ONE method)

### Method A: Using Terminal (Recommended)

1. **Open a new Terminal window**

2. **Run these commands one by one:**

```bash
# Connect to MySQL (you'll be asked for password)
mysql -u root -p
```

3. **After entering MySQL password, copy and paste these commands:**

```sql
CREATE DATABASE IF NOT EXISTS izzamawy_pastry_shop;
USE izzamawy_pastry_shop;
```

4. **Now import the database (still in MySQL prompt):**

```sql
SOURCE /Users/family/Desktop/Web/izzamawy-pastry-shop/database/schema.sql;
```

5. **Verify it worked:**

```sql
SHOW TABLES;
```

You should see 7 tables listed. Then type:

```sql
EXIT;
```

---

### Method B: Using phpMyAdmin (If you have MAMP/XAMPP)

1. Open phpMyAdmin in browser (usually http://localhost:8888/phpMyAdmin or http://localhost/phpMyAdmin)
2. Click "New" to create a database
3. Name it: `izzamawy_pastry_shop`
4. Click "Import" tab
5. Choose file: `database/schema.sql` from your project folder
6. Click "Go"

---

## Step 2: Start the PHP Server

1. **Open Terminal**

2. **Navigate to project folder:**

```bash
cd /Users/family/Desktop/Web/izzamawy-pastry-shop
```

3. **Start the server:**

```bash
php -S localhost:8000
```

You should see:
```
PHP 8.4.12 Development Server (http://localhost:8000) started
```

**⚠️ Keep this terminal window open!**

---

## Step 3: Access the Website

Open your web browser and visit:

### 🌐 **Customer Website:**
```
http://localhost:8000
```

### 👨‍💼 **Admin Panel:**
```
http://localhost:8000/admin/login.php
```

**Admin Credentials:**
- Username: `admin`
- Password: `admin123`

---

## 🎯 Quick Test Checklist

- [ ] Homepage loads with categories
- [ ] Click "Products" - see product listing
- [ ] Click "Add to Cart" on a product
- [ ] View cart icon (should show item count)
- [ ] Go to cart page
- [ ] Try checkout
- [ ] Login to admin panel

---

## 🐛 Troubleshooting

### Database Connection Error?

**Check 1:** Is MySQL running?
```bash
pgrep mysqld
```
If no output, start MySQL:
```bash
brew services start mysql
```

**Check 2:** Update database password
Edit: `config/database.php`
```php
define('DB_PASS', 'your_mysql_password');
```

### Port 8000 Already in Use?

Use a different port:
```bash
php -S localhost:8080
```
Then access: http://localhost:8080

### Images Not Showing?

The system uses placeholder images. You can add real images to:
- `images/products/` - for product photos
- `images/categories/` - for category images

---

## 📱 What to Try

1. **Browse Products**
   - Visit homepage
   - Click different categories
   - Use search box

2. **Shopping Cart**
   - Add items to cart
   - Update quantities
   - Remove items

3. **Place an Order**
   - Go through checkout
   - Fill in form
   - Complete order

4. **Admin Panel**
   - Login with admin/admin123
   - View dashboard statistics
   - Check recent orders
   - See low stock alerts

---

## 🎉 You're All Set!

Your Izzamawy Pastry Shop is now running locally!

**Need help?** Check:
- `README.md` - Full documentation
- `PROJECT_SUMMARY.md` - Feature overview

---

## 💡 Pro Tips

1. **Keep Terminal Open:** Don't close the terminal running PHP server
2. **Test on Phone:** Access from your phone using `http://[your-computer-ip]:8000`
3. **Add Products:** Use admin panel to manage inventory
4. **Customize:** Edit CSS colors in `css/style.css`

---

**Happy Selling! 🍪🥮🎁**
