<?php
session_start();

// --- SECURITY CHANGE: AUTO-LOGOUT ON APP OPEN ---
// Instead of redirecting to the dashboard, we now KILL the session.
// This ensures that opening the app (landing here) always requires a fresh login.
if (isset($_SESSION['user_id'])) {
    // 1. Clear all session variables
    session_unset();
    // 2. Destroy the session completely
    session_destroy();
    // 3. Start a new, clean session for the next login
    session_start();
    session_regenerate_id(true);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EZONI Farm - Login</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="manifest" href="manifest.json?v=3">
    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="apple-touch-icon" href="images/logo.png">
    <meta name="theme-color" content="#0e1117">
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

        <?php if (isset($_GET['registered'])): ?>
            <div style="background: rgba(76, 175, 80, 0.2); color: #4CAF50; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                ✅ Registration successful! Please login.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div style="background: rgba(255, 75, 75, 0.2); color: #ff4b4b; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                ⚠️ <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="auth_handler.php" style="display: flex; flex-direction: column; gap: 15px;">
            <input type="hidden" name="action" value="login">
            <input type="text" name="username" placeholder="Username or Email" required autocomplete="off">
            
            <div class="password-wrapper" style="margin-bottom:20px;">
                <input type="password" name="password" placeholder="Password" required>
                <button type="button" class="toggle-password-btn" onclick="togglePassword(this)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            
            <button type="submit" style="background-color: #4CAF50; font-size: 1rem;">Sign In</button>
        </form>

        <p style="margin-top: 20px; color: #ccc;">
            New here? <a href="register.php" style="color: #4CAF50; text-decoration: none;">Create Account</a>
        </p>
    </div>

    <script>
        function togglePassword(btn) {
            const input = btn.previousElementSibling;
            const icon = btn.querySelector('i');
            
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>