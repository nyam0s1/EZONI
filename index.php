<?php
session_start();

// AUTO-LOGOUT ON APP OPEN
if (isset($_SESSION['user_id'])) {
    session_unset();
    session_destroy();
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

    <div class="login-container">

        <img src="images/logo.png" alt="Ezoni Farm Logo" style="width: 100px; height: 100px; border-radius: 50%; margin-bottom: 20px; border: 3px solid #4CAF50; padding: 2px;">

        <h2 style="margin-bottom: 25px; color:#fff; font-weight:700; letter-spacing:1px;">EZONI FARM</h2>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'timeout'): ?>
            <div style="background: rgba(255, 152, 0, 0.2); color: #FF9800; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(255, 152, 0, 0.3); font-size: 0.9rem;">
                ⚠️ Session expired. Please login again.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['registered'])): ?>
            <div style="background: rgba(76, 175, 80, 0.2); color: #4CAF50; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(76, 175, 80, 0.3); font-size: 0.9rem;">
                ✅ Registration successful, awaiting validation.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div style="background: rgba(255, 75, 75, 0.2); color: #ff4b4b; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(255, 75, 75, 0.3); font-size: 0.9rem;">
                ⚠️ <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="auth_handler.php" style="display: flex; flex-direction: column; gap: 20px;">
            <input type="hidden" name="action" value="login">

            <input type="text" name="username" placeholder="Username or Email" required autocomplete="off" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1);">

            <div class="password-wrapper" style="margin-bottom:0;">
                <input type="password" name="password" placeholder="Password" required style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1);">
                <button type="button" class="toggle-password-btn" onclick="togglePassword(this)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <button type="submit" style="background-color: #4CAF50; font-size: 1rem; margin-top: 10px; box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);">Sign In</button>
        </form>

        <p style="margin-top: 25px; color: #888; font-size: 0.9rem;">
            New here? <a href="register.php" style="color: #4CAF50; text-decoration: none; font-weight: 600;">Create Account</a>
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