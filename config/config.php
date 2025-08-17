<?php
// Base path configuration
define('BASE_PATH', '');  // Leave empty since we're at the root level
define('SITE_URL', '');   // Leave empty since we're at the root level

// Function to get correct path
function get_path($path) {
    return BASE_PATH . $path;
}
?> 