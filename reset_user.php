<?php
require 'includes/db.php';

$pass = "admin123"; // The password you want
$hash = password_hash($pass, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'Admin'");
if ($stmt->execute([$hash])) {
    echo "✅ Password for Admin reset to: $pass";
} else {
    echo "❌ Failed to update.";
}
?>