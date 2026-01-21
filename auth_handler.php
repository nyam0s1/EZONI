<?php
session_start();
require 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit();
}

$action = $_POST['action'];

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
            try {
                $pdo->prepare("INSERT INTO user_logs (user_id, action_type, performed_by) VALUES (?, 'Blocked Login Attempt', 'System')")->execute([$account['id']]);
            } catch (Exception $e) {
            }
            header("Location: index.php?error=Your account is suspended. Contact Admin.");
            exit();
        }

        // CHECK 2: IS ACCOUNT PENDING? (The Update)
        if ($account['status'] === 'pending') {
            header("Location: index.php?error=Awaiting Validation");
            exit();
        }

        // CHECK 3: SUCCESS
        $_SESSION['user_id'] = $account['id'];
        $_SESSION['username'] = $account['username'];
        $_SESSION['role'] = $account['role'];

        if ($account['role'] == 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: user_dashboard.php");
        }
        exit();
    } else {
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

    if ($password !== $confirm) {
        header("Location: register.php?error=Passwords do not match");
        exit();
    }

    $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check->execute([$username, $email]);

    if ($check->rowCount() > 0) {
        header("Location: register.php?error=Username or Email already taken");
        exit();
    }

    $hashed_pass = password_hash($password, PASSWORD_DEFAULT);

    // Status is 'pending' by default
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'user', 'pending')");

    if ($stmt->execute([$username, $email, $hashed_pass])) {

        $new_id = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO user_logs (user_id, action_type, performed_by) VALUES (?, 'Registration Request', 'Self-Register')")->execute([$new_id]);

        // UPDATE: Specific pending flag
        header("Location: index.php?registered=pending");
        exit();
    } else {
        header("Location: register.php?error=Something went wrong");
        exit();
    }
}
