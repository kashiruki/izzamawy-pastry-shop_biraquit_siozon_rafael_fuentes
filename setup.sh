#!/bin/bash
# Quick Setup Script for Izzamawy Pastry Shop
# Run this script to set up the project quickly

echo "========================================="
echo "Izzamawy Pastry Shop - Setup Script"
echo "========================================="
echo ""

# Create image directories
echo "Creating image directories..."
mkdir -p images/products
mkdir -p images/categories
echo "✓ Image directories created"
echo ""

# Set permissions (for Unix/Linux/Mac)
echo "Setting permissions..."
chmod -R 755 images
chmod -R 755 api
chmod -R 755 config
echo "✓ Permissions set"
echo ""

# Check if MySQL is available
echo "Checking MySQL..."
if command -v mysql &> /dev/null; then
    echo "✓ MySQL found"
    echo ""
    echo "To set up the database, run:"
    echo "mysql -u root -p izzamawy_pastry_shop < database/schema.sql"
else
    echo "⚠ MySQL not found in PATH"
    echo "Please install MySQL or use phpMyAdmin to import database/schema.sql"
fi
echo ""

# Check if PHP is available
echo "Checking PHP..."
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
    echo "✓ PHP $PHP_VERSION found"
    echo ""
    echo "To start the development server, run:"
    echo "php -S localhost:8000"
else
    echo "⚠ PHP not found in PATH"
    echo "Please install PHP 7.4 or higher"
fi
echo ""

echo "========================================="
echo "Setup Complete!"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Create database: izzamawy_pastry_shop"
echo "2. Import schema: database/schema.sql"
echo "3. Update config: config/database.php"
echo "4. Start server: php -S localhost:8000"
echo "5. Visit: http://localhost:8000"
echo ""
echo "Default Admin Credentials:"
echo "Username: admin"
echo "Password: admin123"
echo ""
echo "Happy selling! 🍪"
