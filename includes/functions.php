<?php
// Prevent XSS (Cross-Site Scripting)
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>