<?php
/**
 * ============================================================================
 * WEATHER APP - CONFIGURATION FILE
 * ============================================================================
 * This file contains all the important settings for the weather app.
 * Think of this as the control panel for the entire application.
 */

// ============================================================================
// OPENWEATHERMAP API SETTINGS
// ============================================================================

// First, try to get the API key from environment variables (safer for production)
// If not found there, use the hardcoded key (fine for local development)
$env_key = getenv('WEATHER_API_KEY');
define('API_KEY', $env_key ?: 'c724801574a09099a28a564501af63d7');

// The main URL for fetching weather data
define('API_URL', 'https://api.openweathermap.org/data/2.5/weather');

// Temperature units: 'metric' = Celsius, 'imperial' = Fahrenheit
// We're using Celsius by default (most of the world uses this)
define('DEFAULT_UNIT', 'metric');

// How long to wait for API responses before giving up (in seconds)
define('API_TIMEOUT', 10);

// Quick check to make sure the API key is set up properly
// This helps catch configuration issues early
$api_key_warning = (API_KEY === 'YOUR_API_KEY_HERE');

// ============================================================================
// DATABASE SETTINGS (MySQL/XAMPP)
// ============================================================================

// Where is your database? For XAMPP, it's always 'localhost'
define('DB_HOST', 'localhost');

// What's the name of your database? We created this in setup_database.php
define('DB_NAME', 'weather_app');

// Username for database access (XAMPP default is 'root')
define('DB_USER', 'root');

// Password for database (XAMPP has no password by default)
define('DB_PASS', '');

// Character encoding - UTF-8 supports all languages and emojis! 🌍
define('DB_CHARSET', 'utf8mb4');

// ============================================================================
// SESSION & SECURITY SETTINGS
// ============================================================================

// How long should "Remember Me" sessions last? (24 hours in seconds)
define('SESSION_LIFETIME', 86400);

// Custom name for our session cookie (makes it unique to our app)
define('SESSION_NAME', 'weather_app_session');

?>
