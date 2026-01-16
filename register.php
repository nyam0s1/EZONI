<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Ezoni Farm</title>
    <link rel="stylesheet" href="CSS/style.css">
    
    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="apple-touch-icon" href="images/logo.png">
</head>
<body class="login-body">
    <div class="login-container" style="text-align: center;">
        
        <img src="images/logo.png" alt="Ezoni Farm Logo" style="width: 100px; height: 100px; border-radius: 50%; margin-bottom: 15px; border: 3px solid #2196F3; padding: 2px;">
        
        <h2 style="margin-bottom: 20px; color: #fff;">Join Ezoni Farm</h2>
        
        <?php if(isset($_GET['error'])): ?>
            <div style="background: rgba(255, 75, 75, 0.2); color: #ff4b4b; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                ⚠️ <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="auth_handler.php" style="display: flex; flex-direction: column; gap: 15px; text-align: left;">
            <input type="hidden" name="action" value="register"> 
            
            <input type="text" name="username" placeholder="Username" required autocomplete="off">
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            
            <button type="submit" style="background-color: #2196F3; font-size: 1rem;">Create Account</button>
        </form>

        <p style="margin-top: 20px; color: #ccc;">
            Already have an account? <a href="index.php" style="color: #4CAF50; text-decoration: none;">Login here</a>
        </p>
    </div>
</body>
</html>