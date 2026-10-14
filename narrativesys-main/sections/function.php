<?php
// Helper functions (e() is shared with includes/helpers.php, so define it only once)
if (!function_exists('e')) {
    /** Escape text for safe output in HTML. */
    function e($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}
