#!/bin/bash

# سكريبت إنشاء مشروع نظام إدارة مخازن المعهد
# Institute Warehouse Management System - Auto Setup Script

echo "🚀 بدء إنشاء مشروع نظام إدارة المخازن..."

# إنشاء المجلد الرئيسي
PROJECT_DIR="institute-warehouse"
mkdir -p "$PROJECT_DIR"
cd "$PROJECT_DIR"

# إنشاء هيكل المجلدات
echo "📁 إنشاء هيكل المجلدات..."
mkdir -p includes
mkdir -p admin/views/reports
mkdir -p assets/css
mkdir -p assets/js
mkdir -p languages
mkdir -p vendor

echo "✅ تم إنشاء هيكل المجلدات بنجاح!"
echo ""
echo "📝 الآن، قم بنسخ محتوى كل ملف من المحادثة وضعه في المكان الصحيح:"
echo ""
echo "المجلد الرئيسي (/):"
echo "  - institute-warehouse.php"
echo "  - composer.json"
echo "  - README.md"
echo "  - QUICK-START.md"
echo "  - INSTALLATION.md"
echo "  - CHANGELOG.md"
echo "  - PROJECT-SUMMARY.md"
echo ""
echo "مجلد includes/:"
echo "  - class-iw-database.php"
echo "  - class-iw-products.php"
echo "  - class-iw-transactions.php"
echo "  - class-iw-departments.php"
echo "  - class-iw-suppliers.php"
echo "  - class-iw-permissions.php"
echo "  - class-iw-reports.php"
echo "  - class-iw-excel-import.php"
echo ""
echo "مجلد admin/:"
echo "  - class-iw-admin.php"
echo ""
echo "مجلد admin/views/:"
echo "  - dashboard.php"
echo "  - products.php"
echo "  - add-stock.php"
echo "  - withdraw-stock.php"
echo "  - reports.php"
echo "  - departments.php"
echo "  - suppliers.php"
echo "  - import.php"
echo "  - settings.php"
echo "  - print-add-permit.php"
echo "  - print-withdraw-permit.php"
echo ""
echo "مجلد admin/views/reports/:"
echo "  - stock-report.php"
echo "  - low-stock-report.php"
echo "  - out-of-stock-report.php"
echo "  - transactions-report.php"
echo "  - department-consumption-report.php"
echo "  - product-movement-report.php"
echo ""
echo "مجلد assets/css/:"
echo "  - admin.css"
echo ""
echo "مجلد assets/js/:"
echo "  - admin.js"
echo ""
echo "✅ المجلدات جاهزة! ابدأ بنسخ الملفات."
echo ""
echo "بعد نسخ جميع الملفات، نفذ:"
echo "  composer install"
echo "  zip -r ../institute-warehouse.zip ."
echo ""
echo "🎉 ثم ارفع الملف المضغوط إلى WordPress!"
