<?php
/**
 * PROFILE SETTINGS VIEW
 * ---------------------
 * Features: Update Username, Update Email, Change Password, Logout, Delete Account.
 * Security: Prevents duplicate usernames/emails.
 */

$id = $_SESSION['user_id'];
$success = '';
$error = '';

// 1. UPDATE INFO LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_info'])) {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    
    // CHECK 1: Is Username taken?
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$new_username, $id]);
    if ($stmt->rowCount() > 0) {
        $error = "❌ Username '$new_username' is already taken.";
    } 
    // CHECK 2: Is Email taken?
    else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$new_email, $id]);
        
        if ($stmt->rowCount() > 0) {
            $error = "❌ Email '$new_email' is already in use.";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            if ($stmt->execute([$new_username, $new_email, $id])) {
                $_SESSION['username'] = $new_username; 
                $success = "✅ Profile details updated successfully.";
            } else {
                $error = "❌ Database error.";
            }
        }
    }
}

// 2. CHANGE PASSWORD LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_pass'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user_data = $stmt->fetch();

    if (!password_verify($current_pass, $user_data['password'])) {
        $error = "❌ Current password is incorrect.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "❌ New passwords do not match.";
    } elseif (strlen($new_pass) < 6) {
        $error = "❌ Password must be at least 6 characters.";
    } else {
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$new_hash, $id]);
        $success = "✅ Password changed successfully.";
    }
}

// Fetch current details
$stmt = $pdo->prepare("SELECT username, email, role FROM users WHERE id = ?");
$stmt->execute([$id]);
$current_user = $stmt->fetch();
?>

<div class="form-container" style="max-width: 1000px;">
    
    <div class="form-header">
        <?php if($_SESSION['role'] == 'admin'): ?>
            <a href="?page=overview" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
        <?php else: ?>
            <a href="?page=home" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
        <?php endif; ?>
        <h2>👤 My Profile</h2>
    </div>

    <?php if($success): ?>
        <div style="background: rgba(76, 175, 80, 0.2); color: #4CAF50; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #4CAF50;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div style="background: rgba(255, 75, 75, 0.2); color: #ff4b4b; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ff4b4b;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        
        <div class="card" style="flex: 1; min-width: 300px;">
            <h3 style="border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 15px; color: #2196F3;">Edit Details</h3>
            <form method="POST">
                <label style="color: #aaa; font-size: 0.9rem;">Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($current_user['username']); ?>" required autocomplete="off" style="margin-bottom: 15px;">
                
                <label style="color: #aaa; font-size: 0.9rem;">Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($current_user['email']); ?>" required autocomplete="off" style="margin-bottom: 15px;">

                <label style="color: #aaa; font-size: 0.9rem;">Role (Read Only)</label>
                <input type="text" value="<?php echo ucfirst($current_user['role']); ?>" disabled style="background: #222; color: #666; cursor: not-allowed; border: 1px solid #333;">
                
                <button type="submit" name="update_info" class="badge-blue" style="width: 100%; margin-top: 10px; border: none; padding: 12px; font-size: 1rem; cursor: pointer;">Update Profile</button>
            </form>
        </div>

        <div class="card" style="flex: 1; min-width: 300px;">
            <h3 style="border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 15px; color: #4CAF50;">Security</h3>
            <form method="POST">
                <label style="color: #aaa; font-size: 0.9rem;">Current Password</label>
                <input type="password" name="current_password" placeholder="Verify it's you" required style="margin-bottom: 15px;">
                
                <label style="color: #aaa; font-size: 0.9rem;">New Password</label>
                <input type="password" name="new_password" placeholder="New strong password" required style="margin-bottom: 15px;">
                
                <label style="color: #aaa; font-size: 0.9rem;">Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="Repeat password" required>
                
                <button type="submit" name="update_pass" class="badge-green" style="width: 100%; margin-top: 20px; border: none; padding: 12px; font-size: 1rem; cursor: pointer;">Change Password</button>
            </form>
        </div>

        <div class="card" style="flex: 1; min-width: 300px;">
            <h3 style="border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 15px; color: #FF9800;">Account Actions</h3>
            
            <p style="color:#aaa; font-size:0.9rem; margin-bottom:10px;">End your current session:</p>
            <a href="logout.php" class="badge-orange" style="display:block; text-align:center; padding:12px; text-decoration:none; margin-bottom:20px;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
            
            <p style="color:#aaa; font-size:0.9rem; margin-bottom:10px;">Permanently remove account:</p>
            <a href="?page=delete_account" style="display:block; text-align:center; padding:12px; background:rgba(255, 75, 75, 0.1); color:#ff4b4b; border:1px solid #ff4b4b; border-radius:5px; text-decoration:none; transition: all 0.2s;" onmouseover="this.style.background='rgba(255, 75, 75, 0.2)'" onmouseout="this.style.background='rgba(255, 75, 75, 0.1)'">
                <i class="fas fa-trash-alt"></i> Delete Account
            </a>
        </div>

    </div>
</div>