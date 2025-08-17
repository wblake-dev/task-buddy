<?php
session_start(); // Start the session

// Unset all of the session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear any remember me cookies
setcookie(session_name(), '', time() - 3600, "/");

// Set a logout success message
session_start();
$_SESSION['logout_success'] = "You have been successfully logged out.";

// Redirect to the login page
header("Location: login.php");
exit();
?>