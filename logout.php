<?php
/**
 * Logout Script
 * Destroys session and redirects to home page
 */

require_once 'auth.php';

startSecureSession();
logoutUser();

header("Location: index.php?logout=1");
exit;

?>
