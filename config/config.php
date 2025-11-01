<?php

// Load environment variables
require_once __DIR__ . '/env.php';

// Database constants (fallback to env)
define('DB_HOST', Env::get('DB_HOST', 'localhost'));
define('DB_NAME', Env::get('DB_NAME', 'unSpend'));
define('DB_USER', Env::get('DB_USER', 'root'));
define('DB_PASS', Env::get('DB_PASS', ''));

// API constants
define('OPENAI_API_KEY', Env::get('OPENAI_API_KEY'));
define('GEMINI_API_KEY', Env::get('GEMINI_API_KEY'));
define('API_URL', Env::get('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-09-2025:generateContent'));

// Application settings
define('UPLOAD_DIR', Env::get('UPLOAD_DIR', 'uploads/'));
define('APP_URL', Env::get('APP_URL', 'https://unspend.me'));

// Legacy constants for backward compatibility
define('API_KEY', GEMINI_API_KEY);