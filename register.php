<?php
/**
 * User Registration Page
 * Allows new users to create an account
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
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!validateEmail($email)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $passwordValidation = validatePassword($password);
        if (!$passwordValidation['valid']) {
            $error = $passwordValidation['message'];
        } else {
            try {
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT id FROM weather_users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error = "Username already exists. Please choose another.";
                } else {
                    // Check if email already exists
                    $stmt = $pdo->prepare("SELECT id FROM weather_users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $error = "Email already registered. Please login or use another email.";
                    } else {
                        // Create new user
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO weather_users (username, email, password) VALUES (?, ?, ?)");
                        $stmt->execute([$username, $email, $hashed_password]);
                        
                        // Auto-login after registration
                        $user_id = $pdo->lastInsertId();
                        loginUser($user_id);
                        
                        header("Location: dashboard.php?welcome=1");
                        exit;
                    }
                }
            } catch (PDOException $e) {
                error_log("Registration error: " . $e->getMessage());
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Weather App</title>
    
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
                <h1 class="auth-title">Create Account</h1>
                <p class="auth-subtitle">Join to save your favorite locations</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form" novalidate>
                
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input" 
                        placeholder="Choose a username" 
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        required
                        autocomplete="username"
                    >
                    <span class="form-feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="your.email@example.com" 
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required
                        autocomplete="email"
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
                            placeholder="Create a strong password" 
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="password-toggle" tabindex="-1">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-bar-fill"></div>
                        </div>
                        <span class="strength-text"></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-input" 
                            placeholder="Re-enter your password" 
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="password-toggle" tabindex="-1">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <span class="form-feedback"></span>
                </div>
                
                <button type="submit" class="btn-submit">
                    Create Account
                </button>
                
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php" class="auth-link">Login here</a></p>
            </div>
            
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/auth.js"></script>
    
</body>
</html>
