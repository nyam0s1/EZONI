<?php
session_start();
// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') { header("Location: admin_dashboard.php"); }
    else { header("Location: user_dashboard.php"); }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EZONI Farm - Login</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0e1117">
    
    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="apple-touch-icon" href="images/logo.png">
</head>
<body class="login-body">
    <div class="login-container" style="text-align: center;">
        
        <img src="images/logo.png" alt="Ezoni Farm Logo" style="width: 120px; height: 120px; border-radius: 50%; margin-bottom: 15px; border: 3px solid #4CAF50; padding: 2px;">
        
        <h2 style="margin-bottom: 25px; color:#fff;">EZONI Farm Login</h2>
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'timeout'): ?>
            <div style="background: rgba(255, 152, 0, 0.2); color: #FF9800; padding: 10px; border-radius: 5px; margin-bottom: 15px; border: 1px solid #FF9800;">
                ⚠️ Session expired. Please login again.
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['registered'])): ?>
            <div style="background: rgba(76, 175, 80, 0.2); color: #4CAF50; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                ✅ Registration successful! Please login.
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['error'])): ?>
            <div style="background: rgba(255, 75, 75, 0.2); color: #ff4b4b; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                ⚠️ <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="auth_handler.php" style="display: flex; flex-direction: column; gap: 15px;">
            <input type="hidden" name="action" value="login"> 
            <input type="text" name="username" placeholder="Username or Email" required autocomplete="off">
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" style="background-color: #4CAF50; font-size: 1rem;">Sign In</button>
        </form>

        <p style="margin-top: 20px; color: #ccc;">
            New here? <a href="register.php" style="color: #4CAF50; text-decoration: none;">Create Account</a>
        </p>
    </div>
</body>
</html>