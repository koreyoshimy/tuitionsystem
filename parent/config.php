<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'tuition_management_system');

// ToyyibPay API Credentials
define('TOYYIBPAY_SECRET_KEY', 'your-secret-key-here'); // Get from ToyyibPay dashboard
define('TOYYIBPAY_CATEGORY_CODE', 'your-category-code'); // Get from ToyyibPay dashboard

// Application Configuration
define('BASE_URL', 'https://yourdomain.com'); // Your application's base URL
define('SITE_NAME', 'Tuition Management System');

// Security Configuration
define('CSRF_TOKEN_SECRET', 'your-random-csrf-secret-here'); // For CSRF protection

// Environment Settings
define('ENVIRONMENT', 'development'); // 'development' or 'production'

// Error Reporting (adjust based on environment)
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone Settings
date_default_timezone_set('Asia/Kuala_Lumpur');

// Session Configuration
session_set_cookie_params([
    'lifetime' => 86400, // 1 day
    'path' => '/',
    'domain' => '', // your domain if in production
    'secure' => (ENVIRONMENT === 'production'), // HTTPS only in production
    'httponly' => true,
    'samesite' => 'Strict'
]);

// ToyyibPay API Endpoints
define('TOYYIBPAY_CREATE_BILL_URL', 'https://toyyibpay.com/index.php/api/createBill');
define('TOYYIBPAY_PAYMENT_URL', 'https://toyyibpay.com/');
define('TOYYIBPAY_RUNNING_MODE', (ENVIRONMENT === 'production') ? 1 : 0); // 1 for production, 0 for sandbox

// Session Configuration must come FIRST
session_set_cookie_params([
    'lifetime' => 86400, // 1 day
    'path' => '/',
    'domain' => '', // your domain if in production
    'secure' => (ENVIRONMENT === 'production'), // HTTPS only in production
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Then start the session
session_start();

// Rest of your config.php...?>