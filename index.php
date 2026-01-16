<?php
session_start();
require 'includes/db.php';

// --- SECURITY: AUTO-LOGOUT ON APP OPEN ---
// If a user lands on this page, we assume they are opening the app fresh.
// We destroy any old session to ensure a secure "fresh start".
if (isset($_SESSION['user_id'])) {
    session_unset();
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

$error = '';

// --- LOGIN LOGIC ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check if input is Email or Username
    if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    }
    
    $stmt->execute([$input]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: user_dashboard.php");
        }
        exit();
    } else {
        $error = "❌ Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ezoni Farm - Login</title>
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0e1117">
    
    <link rel="manifest" href="manifest.json?v=4">
    
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Ezoni Farm">
    <link rel="apple-touch-icon" href="images/logo.png">

    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="login-page">

    <div class="login-container">
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="images/logo.png" alt="Ezoni Farm Logo" style="width: 120px; border-radius: 50%; box-shadow: 0 4px 15px rgba(0,0,0,0.5);">
        </div>

        <?php if($error): ?>
            <div class="error-msg" style="background: rgba(255, 75, 75, 0.2); color: #ff4b4b; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 15px; border: 1px solid #ff4b4b;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <input type="text" name="username" placeholder="Username or Email" required autocomplete="off">
            </div>
            
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required autocomplete="off">
            </div>

            <button type="submit" class="login-btn">Sign In</button>
        </form>
        
        <div class="login-footer">
            New here? <a href="register.php">Create Account</a>
        </div>
    </div>

</body>
</html>