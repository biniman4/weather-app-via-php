<?php
/**
 * Quick debug page to check auth status
 */

require_once 'config.php';

echo "<h2>Auth Debug Info</h2>";

$authAvailable = false;
$errorMsg = '';

try {
    require_once 'auth.php';
    echo "<p>✓ auth.php loaded successfully</p>";
    
    startSecureSession();
    echo "<p>✓ Session started</p>";
    
    $currentUser = getUserData();
    echo "<p>✓ getUserData() called</p>";
    
    $authAvailable = true;
    echo "<p><strong>✓ Auth Available: TRUE</strong></p>";
    
    if ($currentUser) {
        echo "<p>User logged in: " . htmlspecialchars($currentUser['username']) . "</p>";
    } else {
        echo "<p>No user logged in (this is expected before login)</p>";
    }
    
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($errorMsg) . "</p>";
}

echo "<hr>";
echo "<h3>What should happen:</h3>";
echo "<ul>";
echo "<li>authAvailable = " . ($authAvailable ? 'TRUE' : 'FALSE') . "</li>";
echo "<li>isLoggedIn = " . ($authAvailable && isLoggedIn() ? 'TRUE' : 'FALSE') . "</li>";
echo "<li>currentUser = " . ($currentUser ? 'SET' : 'NULL') . "</li>";
echo "</ul>";

echo "<h3>Buttons to show:</h3>";
if ($authAvailable && isLoggedIn() && $currentUser) {
    echo "<p>✓ Should show: User Menu (Dashboard + Logout)</p>";
} elseif ($authAvailable) {
    echo "<p>✓ Should show: Get Started + Login buttons</p>";
} else {
    echo "<p style='color: red;'>✗ Auth not available - buttons hidden</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Go to Homepage</a></p>";
?>
