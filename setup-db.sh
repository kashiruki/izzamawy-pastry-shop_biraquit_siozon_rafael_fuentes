#!/bin/bash
# Database Setup with Password Prompt

echo "========================================="
echo "Database Setup - Izzamawy Pastry Shop"
echo "========================================="
echo ""
echo "Enter your MySQL root password (press Enter if no password):"
read -s MYSQL_PASSWORD

# Resolve project directory and schema path
PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
SQL_FILE="$PROJECT_DIR/database/schema.sql"

if [ ! -f "$SQL_FILE" ]; then
    echo "ERROR: schema file not found at $SQL_FILE"
    exit 1
fi

if [ -z "$MYSQL_PASSWORD" ]; then
    # Try without password
    mysql -u root <<EOFMYSQL
CREATE DATABASE IF NOT EXISTS izzamawy_pastry_shop;
USE izzamawy_pastry_shop;
SOURCE $SQL_FILE;
SELECT '✅ Database setup complete!' as Status;
SHOW TABLES;
EOFMYSQL
else
    # Use provided password
    mysql -u root -p"$MYSQL_PASSWORD" <<EOFMYSQL
CREATE DATABASE IF NOT EXISTS izzamawy_pastry_shop;
USE izzamawy_pastry_shop;
SOURCE $SQL_FILE;
SELECT '✅ Database setup complete!' as Status;
SHOW TABLES;
EOFMYSQL
fi

if [ $? -eq 0 ]; then
    echo ""
    echo "========================================="
    echo "✅ SUCCESS! Database is ready!"
    echo "========================================="
    echo ""
    # Run migrations if present
    MIG_DIR="$PROJECT_DIR/database/migrations"
    if [ -d "$MIG_DIR" ]; then
        echo "Applying migrations from $MIG_DIR"
        if [ -z "$MYSQL_PASSWORD" ]; then
            MYSQL_CMD="mysql -u root"
        else
            MYSQL_CMD="mysql -u root -p$MYSQL_PASSWORD"
        fi
        for f in "$MIG_DIR"/*.sql; do
            if [ -f "$f" ]; then
                echo "Applying $(basename "$f")..."
                $MYSQL_CMD izzamawy_pastry_shop < "$f"
                if [ $? -ne 0 ]; then
                    echo "Warning: migration $f failed"
                fi
            fi
        done
    fi
    echo "Now update the database password in:"
    echo "config/database.php"
    echo ""
    echo "Change this line:"
    echo "  define('DB_PASS', '');"
    echo ""
    echo "To (if you have a password):"
    echo "  define('DB_PASS', 'your_password');"
    echo ""
    echo "Then start the server:"
    echo "  php -S localhost:8000"
    echo ""
else
    echo ""
    echo "========================================="
    echo "❌ Database setup failed"
    echo "========================================="
    echo ""
    echo "Possible issues:"
    echo "1. Wrong MySQL password"
    echo "2. MySQL not running"
    echo "3. Permission issues"
    echo ""
    echo "Try manually:"
    echo "  mysql -u root -p"
    echo "  CREATE DATABASE izzamawy_pastry_shop;"
    echo "  USE izzamawy_pastry_shop;"
    echo "  SOURCE /Users/family/Desktop/Web/izzamawy-pastry-shop/database/schema.sql;"
    echo ""
fi
