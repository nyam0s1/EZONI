<?php
session_start();
require 'includes/db.php';

// If someone tries to access this file directly without posting data
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit();
}

$action = $_POST['action']; // 'login' or 'register'

// ==========================================
// 1. LOGIN LOGIC
// ==========================================
if ($action == 'login') {
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    // Check DB
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$user, $user]);
    $account = $stmt->fetch();

    // Verify Password
    if ($account && password_verify($pass, $account['password'])) {
        
        // CHECK 1: IS ACCOUNT SUSPENDED?
        if ($account['status'] === 'suspended') {
            header("Location: index.php?error=Your account is suspended. Contact Admin.");
            exit();
        }

        // CHECK 2: SUCCESS
        $_SESSION['user_id'] = $account['id'];
        $_SESSION['username'] = $account['username'];
        $_SESSION['role'] = $account['role'];

        // Redirect based on Role
        if ($account['role'] == 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: user_dashboard.php");
        }
        exit();

    } else {
        // FAIL
        header("Location: index.php?error=Invalid Username or Password");
        exit();
    }
}

// ==========================================
// 2. REGISTRATION LOGIC
// ==========================================
elseif ($action == 'register') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    // Validation
    if ($password !== $confirm) {
        header("Location: register.php?error=Passwords do not match");
        exit();
    }

    // Check availability
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check->execute([$username, $email]);
    
    if ($check->rowCount() > 0) {
        header("Location: register.php?error=Username or Email already taken");
        exit();
    }

    // Create User (Default role: user, Default status: active)
    $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'user', 'active')");
    
    if ($stmt->execute([$username, $email, $hashed_pass])) {
        // Success: Send to Login Page
        header("Location: index.php?registered=1");
        exit();
    } else {
        header("Location: register.php?error=Something went wrong");
        exit();
    }
}
?>