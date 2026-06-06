#!/bin/bash
# Database Setup for Izzamawy Pastry Shop

echo "========================================="
echo "Database Setup - Izzamawy Pastry Shop"
echo "========================================="
echo ""

# Check if MySQL is running
if ! pgrep -x "mysqld" > /dev/null; then
    echo "⚠️  MySQL is not running!"
    echo ""
    echo "Please start MySQL first:"
    echo "  - If using Homebrew: brew services start mysql"
    echo "  - If using MAMP: Start MAMP servers"
    echo "  - If using XAMPP: Start MySQL from XAMPP control panel"
    echo ""
    exit 1
fi

echo "Step 1: Creating database..."
echo ""
echo "Please enter your MySQL root password when prompted."
echo ""

# Create database and import schema
mysql -u root -p << 'EOF'
    CREATE DATABASE IF NOT EXISTS izzamawy_pastry_shop;
    USE izzamawy_pastry_shop;
SOURCE database/schema.sql;
SELECT 'Database created successfully!' as Status;
SHOW TABLES;
EOF

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Database setup complete!"
    echo ""
    echo "========================================="
    echo "Next Steps:"
    echo "========================================="
    echo "1. Start the PHP server:"
    echo "   php -S localhost:8000"
    echo ""
    echo "2. Open your browser:"
    echo "   http://localhost:8000"
    echo ""
    echo "3. Admin login:"
    echo "   http://localhost:8000/admin/login.php"
    echo "   Username: admin"
    echo "   Password: admin123"
    echo ""
else
    echo ""
    echo "❌ Database setup failed!"
    echo ""
    echo "Manual setup instructions:"
    echo "1. Open Terminal"
    echo "2. Run: mysql -u root -p"
    echo "3. Enter your MySQL password"
    echo "4. Run these commands:"
    echo "   CREATE DATABASE izzamawy_pastry_shop;"
    echo "   USE izzamawy_pastry_shop;"
    echo "   SOURCE database/schema.sql;"
    echo "   EXIT;"
fi
