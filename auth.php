<?php
/**
 * Authentication Helper Functions
 * Provides session management and user authentication utilities
 */

require_once 'config.php';
// Note: db.php should be loaded by the calling script before using database-dependent functions

/**
 * Start a secure session with proper security settings
 */
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configure session security
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
        ini_set('session.cookie_samesite', 'Strict');
        
        session_name(SESSION_NAME);
        session_start();
        
        // Regenerate session ID periodically to prevent fixation
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    startSecureSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Get current user data
 * @return array|null User data or null if not logged in
 */
function getUserData() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, favorite_cities, preferences, created_at, last_login FROM weather_users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Parse JSON fields
            $user['favorite_cities'] = json_decode($user['favorite_cities'], true) ?? [];
            $user['preferences'] = json_decode($user['preferences'], true) ?? [];
        }
        
        return $user;
    } catch (PDOException $e) {
        error_log("Error fetching user data: " . $e->getMessage());
        return null;
    }
}

/**
 * Require login - redirect to login page if not authenticated
 * @param string $redirect_to URL to redirect after login
 */
function requireLogin($redirect_to = '') {
    if (!isLoggedIn()) {
        $redirect_url = 'login.php';
        if (!empty($redirect_to)) {
            $redirect_url .= '?redirect=' . urlencode($redirect_to);
        }
        header("Location: $redirect_url");
        exit;
    }
}

/**
 * Log in a user
 * @param int $user_id
 * @param bool $remember_me
 */
function loginUser($user_id, $remember_me = false) {
    global $pdo;
    
    startSecureSession();
    
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user_id;
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    // Update last login time
    try {
        $stmt = $pdo->prepare("UPDATE weather_users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        error_log("Error updating last login: " . $e->getMessage());
    }
    
    // Set remember me cookie if requested
    if ($remember_me) {
        setcookie(SESSION_NAME, session_id(), time() + SESSION_LIFETIME, '/', '', false, true);
    }
}

/**
 * Log out the current user
 */
function logoutUser() {
    startSecureSession();
    
    // Unset all session variables
    $_SESSION = [];
    
    // Delete the session cookie
    if (isset($_COOKIE[SESSION_NAME])) {
        setcookie(SESSION_NAME, '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Alias for isValidEmail (for backwards compatibility)
 */
function validateEmail($email) {
    return isValidEmail($email);
}

/**
 * Validate password strength
 * @param string $password
 * @return array ['valid' => bool, 'message' => string]
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return [
        'valid' => empty($errors),
        'message' => implode('. ', $errors)
    ];
}

/**
 * Sanitize user input
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

?>
