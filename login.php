<?php
/**
 * User Login Page
 * Allows users to authenticate and access their account
 */

require_once 'config.php';
require_once 'auth.php';
require_once 'db.php';

startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$redirect = $_GET['redirect'] ?? 'dashboard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = sanitizeInput($_POST['login'] ?? ''); // Can be email or username
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    if (empty($login) || empty($password)) {
        $error = "Please enter both email/username and password.";
    } else {
        try {
            // Check if login is email or username
            $stmt = $pdo->prepare("SELECT id, username, password FROM weather_users WHERE email = ? OR username = ?");
            $stmt->execute([$login, $login]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Successful login
                loginUser($user['id'], $remember_me);
                
                // Redirect to intended page or dashboard
                $redirect_url = filter_var($redirect, FILTER_SANITIZE_URL);
                header("Location: " . $redirect_url);
                exit;
            } else {
                $error = "Invalid email/username or password.";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "Login failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Weather App</title>
    
    <!-- External Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    
    <div class="back-home">
        <a href="index.php">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Home</span>
        </a>
    </div>
    
    <div class="auth-container">
        <div class="auth-card">
            
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="bi bi-cloud-haze2"></i>
                </div>
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Login to access your weather dashboard</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Registration successful! Please login.</span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['logout'])): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>You have been logged out successfully.</span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form" novalidate>
                
                <div class="form-group">
                    <label for="login" class="form-label">Email or Username</label>
                    <input 
                        type="text" 
                        id="login" 
                        name="login" 
                        class="form-input" 
                        placeholder="Enter your email or username" 
                        value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>"
                        required
                        autocomplete="username"
                    >
                    <span class="form-feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Enter your password" 
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="password-toggle" tabindex="-1">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <span class="form-feedback"></span>
                </div>
                
                <div class="form-checkbox">
                    <input type="checkbox" id="remember_me" name="remember_me">
                    <label for="remember_me">Remember me</label>
                </div>
                
                <div class="forgot-password">
                    <a href="#" onclick="alert('Password reset feature coming soon!'); return false;">Forgot password?</a>
                </div>
                
                <button type="submit" class="btn-submit">
                    Login
                </button>
                
            </form>
            
            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php" class="auth-link">Get started</a></p>
            </div>
            
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/auth.js"></script>
    
</body>
</html>
