<?php
// Settings for the login/register module. Edit these values, or set them as environment variables.

function auth_setting($name, $default)
{
    $value = getenv($name);
    return ($value === false || $value === '') ? $default : $value;
}

// Database connection lives in ../config/db.php (shared with the landing page + admin).

// Account lock after repeated wrong passwords
define('MAX_FAILED_ATTEMPTS', (int) auth_setting('MAX_FAILED_ATTEMPTS', 5));
define('LOCK_MINUTES', (int) auth_setting('LOCK_MINUTES', 5));

// Roles that exist in the system
define('ROLES', ['customer', 'staff', 'admin']);
