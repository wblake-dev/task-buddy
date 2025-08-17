<?php
/**
 * Session Helper Functions
 * Include this file in pages that require session management
 */

// Configure session settings for better security and persistence
if (!session_id()) {
    ini_set('session.cookie_lifetime', 0);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

/**
 * Check if user is logged in and session is valid
 * @param bool $redirect Whether to redirect to login page if not logged in
 * @return bool True if user is logged in and session is valid
 */
function isLoggedIn($redirect = true)
{
    if (!isset($_SESSION['user_id'])) {
        if ($redirect) {
            header("Location: " . getLoginUrl());
            exit();
        }
        return false;
    }

    // Check for session timeout
    $timeout_duration = isset($_SESSION['remember_me']) && $_SESSION['remember_me'] ?
        (30 * 24 * 60 * 60) : (24 * 60 * 60); // 30 days vs 24 hours

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        // Session expired
        logoutUser();
        if ($redirect) {
            header("Location: " . getLoginUrl() . "?timeout=1");
            exit();
        }
        return false;
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();

    // Regenerate session ID periodically for security
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // Every 5 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }

    return true;
}

/**
 * Get the login URL based on current location
 * @return string Login URL
 */
function getLoginUrl()
{
    $current_dir = dirname($_SERVER['REQUEST_URI']);

    // Determine the correct path to login.php based on current location
    if (strpos($current_dir, '/user/auth') !== false) {
        return 'login.php';
    } elseif (strpos($current_dir, '/user') !== false) {
        return 'auth/login.php';
    } elseif (
        strpos($current_dir, '/dashboard') !== false ||
        strpos($current_dir, '/surveys') !== false ||
        strpos($current_dir, '/withdrawals') !== false ||
        strpos($current_dir, '/tickets') !== false
    ) {
        return '../user/auth/login.php';
    } else {
        return 'user/auth/login.php';
    }
}

/**
 * Logout user and clean up session
 */
function logoutUser()
{
    // Unset all session variables
    $_SESSION = array();

    // Delete session cookie
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

    // Destroy session
    session_destroy();

    // Clear any remember me cookies
    setcookie(session_name(), '', time() - 3600, "/");
}

/**
 * Get current user ID
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId()
{
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Get current username
 * @return string|null Username or null if not logged in
 */
function getCurrentUsername()
{
    return isset($_SESSION['username']) ? $_SESSION['username'] : null;
}

/**
 * Check if user has "remember me" enabled
 * @return bool True if remember me is enabled
 */
function hasRememberMe()
{
    return isset($_SESSION['remember_me']) && $_SESSION['remember_me'];
}

/**
 * Set flash message for next page load
 * @param string $type Message type (success, error, info, warning)
 * @param string $message The message to display
 */
function setFlashMessage($type, $message)
{
    if (!session_id()) {
        session_start();
    }
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Get and clear flash message
 * @param string $type Message type (success, error, info, warning)
 * @return string|null The flash message or null if none exists
 */
function getFlashMessage($type)
{
    if (!session_id()) {
        session_start();
    }

    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $message = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $message;
    }

    return null;
}
?>