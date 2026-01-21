<?php

/**
 * USERS MANAGEMENT VIEW - FINAL SECURE VERSION
 * - Features: Add User (w/ Email), Secure Suspend, Secure Delete, Secure Activate, Login Alerts, User History.
 * - Update: Activation now requires Admin Password confirmation.
 */

// --- LOGIC & LOGGING ---

// HELPER: Log User Action
function log_user_action($pdo, $target_id, $action)
{
    $performer = $_SESSION['username'];
    $stmt = $pdo->prepare("INSERT INTO user_logs (user_id, action_type, performed_by) VALUES (?, ?, ?)");
    $stmt->execute([$target_id, $action, $performer]);
}

// 1. ADD NEW USER
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Check if username/email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);

    if ($stmt->rowCount() > 0) {
        echo "<script>alert('Error: Username or Email already taken.');</script>";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, ?, 'active')";
        $pdo->prepare($sql)->execute([$username, $email, $hashed, $role]);

        $new_id = $pdo->lastInsertId();
        log_user_action($pdo, $new_id, 'Account Created');

        echo "<script>window.location.href='?page=users';</script>";
    }
}

// 2. SECURE ACTIVATE EXECUTION (Password Required)
// (Replaced the old GET logic with this secure POST logic)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_secure_activate'])) {
    $target_id = $_POST['target_id'];
    $admin_pass = $_POST['admin_password'];
    $admin_id = $_SESSION['user_id'];

    // Verify Admin Password
    $adm = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $adm->execute([$admin_id]);
    $real_hash = $adm->fetchColumn();

    if (password_verify($admin_pass, $real_hash)) {
        $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$target_id]);
        log_user_action($pdo, $target_id, 'Account Activated');
        echo "<script>alert('User activated successfully.'); window.location.href='?page=users';</script>";
    } else {
        echo "<script>alert('Error: Incorrect Admin Password.');</script>";
    }
}

// 3. SECURE SUSPEND EXECUTION (Password Required)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_secure_suspend'])) {
    $target_id = $_POST['target_id'];
    $admin_pass = $_POST['admin_password'];
    $admin_id = $_SESSION['user_id'];

    $chk = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $chk->execute([$target_id]);
    $target_role = $chk->fetchColumn();

    $adm = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $adm->execute([$admin_id]);
    $real_hash = $adm->fetchColumn();

    if (password_verify($admin_pass, $real_hash)) {
        if ($target_id == $admin_id) {
            echo "<script>alert('Error: You cannot suspend yourself.');</script>";
        } elseif ($target_role == 'admin') {
            echo "<script>alert('Error: Admins cannot be suspended.');</script>";
        } else {
            $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?")->execute([$target_id]);
            log_user_action($pdo, $target_id, 'Account Suspended');
            echo "<script>alert('User suspended successfully.'); window.location.href='?page=users';</script>";
        }
    } else {
        echo "<script>alert('Error: Incorrect Admin Password.');</script>";
    }
}

// 4. SECURE DELETE EXECUTION (Password Required)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_secure_delete'])) {
    $target_id = $_POST['target_id'];
    $admin_pass = $_POST['admin_password'];
    $admin_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$target_id]);
    $target_role = $stmt->fetchColumn();

    $adm = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $adm->execute([$admin_id]);
    $real_hash = $adm->fetchColumn();

    if (password_verify($admin_pass, $real_hash)) {
        if ($target_id == $admin_id) {
            echo "<script>alert('Error: You cannot delete yourself.');</script>";
        } elseif ($target_role == 'admin') {
            echo "<script>alert('Error: Admins cannot be deleted.');</script>";
        } else {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$target_id]);
            $pdo->prepare("DELETE FROM user_logs WHERE user_id = ?")->execute([$target_id]);
            echo "<script>alert('User deleted successfully.'); window.location.href='?page=users';</script>";
        }
    } else {
        echo "<script>alert('Error: Incorrect Admin Password.');</script>";
    }
}

// 5. FILTER LOGIC
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sql = "SELECT * FROM users ";
if ($filter == 'active') $sql .= "WHERE status = 'active' ";
if ($filter == 'suspended') $sql .= "WHERE status = 'suspended' ";
$sql .= "ORDER BY id DESC";
$users = $pdo->query($sql)->fetchAll();

// 6. STATS
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_active = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$total_suspended = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'suspended'")->fetchColumn();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
?>

<?php if ($action == 'add'): ?>
    <div class="form-container">
        <div class="form-header">
            <a href="?page=users" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h2>Create New User</h2>
        </div>
        <div class="card" style="text-align:left;">
            <form method="POST" style="display:flex; flex-direction:column; gap:15px;">
                <label style="color:#aaa;">Username</label>
                <input type="text" name="username" placeholder="e.g. FarmManager" required autocomplete="off">

                <label style="color:#aaa;">Email Address</label>
                <input type="email" name="email" placeholder="user@ezonifarm.com" required autocomplete="off">

                <label style="color:#aaa;">Password</label>
                <div class="password-wrapper" style="margin-bottom:0;">
                    <input type="password" name="password" placeholder="Secure Password" required autocomplete="new-password">
                    <button type="button" class="toggle-password-btn" onclick="togglePassword(this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                <label style="color:#aaa;">Role / Access Level</label>
                <select name="role" style="background:rgba(0,0,0,0.4); color:white; border:1px solid #555; padding:12px; border-radius:5px;">
                    <option value="user">User (Standard Access)</option>
                    <option value="admin">Admin (Full Control)</option>
                </select>

                <button type="submit" name="add_user" style="background:#4CAF50; margin-top:10px;">+ Create Account</button>
            </form>
        </div>
    </div>

<?php elseif ($action == 'history'): ?>
    <?php
    $hist_id = $_GET['id'];

    // Get User Name
    $u_stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $u_stmt->execute([$hist_id]);
    $u_name = $u_stmt->fetchColumn();

    // Get Logs
    $l_stmt = $pdo->prepare("SELECT * FROM user_logs WHERE user_id = ? ORDER BY log_date DESC");
    $l_stmt->execute([$hist_id]);
    $logs = $l_stmt->fetchAll();
    ?>
    <div class="form-container" style="max-width: 800px;">
        <div class="form-header">
            <a href="?page=users" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h2>📜 History: <?php echo htmlspecialchars($u_name); ?></h2>
        </div>

        <div class="card" style="border-top: 3px solid #FF9800;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Action</th>
                            <th>Performed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td data-label="Date" style="color:#aaa;">
                                    <?php echo date('d M, H:i', strtotime($log['log_date'])); ?>
                                </td>
                                <td data-label="Action">
                                    <?php if ($log['action_type'] == 'Blocked Login Attempt'): ?>
                                        <span class="badge badge-red">⚠️ Blocked Login</span>
                                    <?php elseif (strpos($log['action_type'], 'Created') !== false): ?>
                                        <span class="badge badge-blue">✨ Created</span>
                                    <?php elseif (strpos($log['action_type'], 'Suspended') !== false): ?>
                                        <span class="badge badge-red">🚫 Suspended</span>
                                    <?php elseif (strpos($log['action_type'], 'Activated') !== false): ?>
                                        <span class="badge badge-green">✅ Activated</span>
                                    <?php else: ?>
                                        <span class="badge badge-orange"><?php echo $log['action_type']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="By">
                                    <span style="font-weight:bold; color:#fff;"><?php echo htmlspecialchars($log['performed_by']); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="3" style="text-align:center; padding:30px; color:#666;">No history recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($action == 'confirm_delete' || $action == 'confirm_suspend' || $action == 'confirm_activate'): ?>
    <?php
    $target_id = $_GET['id'];
    $chk = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $chk->execute([$target_id]);
    $target_name = $chk->fetchColumn();

    // --- NEW LOGIC: Switch between 3 modes (Delete, Suspend, Activate) ---
    if ($action == 'confirm_delete') {
        $color = '#ff4b4b';
        $title = 'Delete User?';
        $btn_text = 'Yes, Delete User';
        $icon = 'fa-user-times';
        $post_name = 'confirm_secure_delete';
    } elseif ($action == 'confirm_suspend') {
        $color = '#FF9800';
        $title = 'Suspend User?';
        $btn_text = 'Yes, Suspend User';
        $icon = 'fa-ban';
        $post_name = 'confirm_secure_suspend';
    } else { // confirm_activate
        $color = '#4CAF50';
        $title = 'Activate User?';
        $btn_text = 'Yes, Activate User';
        $icon = 'fa-check-circle';
        $post_name = 'confirm_secure_activate';
    }
    ?>
    <div class="form-container" style="max-width: 500px; margin-top:50px;">
        <div class="form-header"><a href="?page=users" class="back-btn"><i class="fas fa-arrow-left"></i> Cancel</a>
            <h2 style="color:<?php echo $color; ?>;">⚠️ <?php echo $title; ?></h2>
        </div>
        <div class="card" style="text-align:center; border-top: 3px solid <?php echo $color; ?>;">
            <i class="fas <?php echo $icon; ?>" style="font-size:3rem; color:<?php echo $color; ?>; margin-bottom:20px;"></i>
            <h3 style="color:#fff; margin-bottom:15px;"><?php echo htmlspecialchars($target_name); ?></h3>
            <p style="color:#aaa; font-size:0.9rem; margin-bottom:20px;">Enter Admin Password to confirm.</p>
            <form method="POST">
                <input type="hidden" name="target_id" value="<?php echo $target_id; ?>">
                <div class="password-wrapper" style="margin-bottom:20px; text-align:left;">
                    <input type="password" name="admin_password" placeholder="Admin Password" required style="border:1px solid <?php echo $color; ?>;">
                    <button type="button" class="toggle-password-btn" onclick="togglePassword(this)"><i class="fas fa-eye"></i></button>
                </div>
                <button type="submit" name="<?php echo $post_name; ?>" style="background:<?php echo $color; ?>; width:100%; font-weight:bold;"><?php echo $btn_text; ?></button>
            </form>
        </div>
    </div>

<?php else: ?>

    <style>
        @keyframes flash {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }

        .login-alert {
            animation: flash 1.5s infinite;
            background: rgba(255, 152, 0, 0.2);
            color: #FF9800;
            border: 1px solid #FF9800;
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 5px;
        }
    </style>

    <div class="metrics-grid">
        <a href="?page=users&filter=all" style="text-decoration:none;">
            <div class="metric-card" style="border-left:4px solid #2196F3; transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                <h4>Total Users</h4>
                <div class="value" style="color:#2196F3;"><?php echo $total_users; ?></div>
                <div class="sub-text">All Accounts</div>
            </div>
        </a>
        <a href="?page=users&filter=active" style="text-decoration:none;">
            <div class="metric-card" style="border-left:4px solid #4CAF50; transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                <h4>Active Users</h4>
                <div class="value" style="color:#4CAF50;"><?php echo $total_active; ?></div>
                <div class="sub-text">Can Login</div>
            </div>
        </a>
        <a href="?page=users&filter=suspended" style="text-decoration:none;">
            <div class="metric-card" style="border-left:4px solid #ff4b4b; transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                <h4>Suspended</h4>
                <div class="value" style="color:#ff4b4b;"><?php echo $total_suspended; ?></div>
                <div class="sub-text">Access Blocked</div>
            </div>
        </a>
    </div>

    <div class="list-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>👥 User Management <?php if ($filter != 'all') echo "<span style='font-size:1rem; color:#aaa;'>(" . ucfirst($filter) . ")</span>"; ?></h2>
        <a href="?page=users&action=add" style="background:#4CAF50; color:white; padding:8px 15px; text-decoration:none; border-radius:5px;">+ Add User</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <?php
                        // CHECK FOR RECENT BLOCKED ATTEMPTS
                        $alert_sql = "SELECT id FROM user_logs WHERE user_id = ? AND action_type = 'Blocked Login Attempt' AND log_date > (NOW() - INTERVAL 1 DAY)";
                        $stmt = $pdo->prepare($alert_sql);
                        $stmt->execute([$u['id']]);
                        $has_attempt = $stmt->rowCount() > 0;
                        ?>
                        <tr>
                            <td data-label="Username">
                                <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                                <?php if ($u['id'] == $_SESSION['user_id']) echo " <small style='color:#4CAF50'>(You)</small>"; ?>
                            </td>

                            <td data-label="Email" style="color:#aaa; font-size:0.9rem;">
                                <?php echo htmlspecialchars($u['email']); ?>
                            </td>

                            <td data-label="Role">
                                <span class="badge <?php echo ($u['role'] == 'admin') ? 'badge-blue' : 'badge-orange'; ?>">
                                    <?php echo ucfirst($u['role']); ?>
                                </span>
                            </td>

                            <td data-label="Status">
                                <span class="badge <?php echo ($u['status'] == 'active') ? 'badge-green' : 'badge-red'; ?>">
                                    <?php echo ucfirst($u['status']); ?>
                                </span>
                                <?php if ($u['status'] == 'suspended' && $has_attempt): ?>
                                    <br><span class="login-alert">⚠️ Trying to Access</span>
                                <?php endif; ?>
                            </td>

                            <td data-label="Action" style="text-align:right;">
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>

                                    <a href="?page=users&action=history&id=<?php echo $u['id']; ?>" class="badge badge-orange" style="text-decoration:none; margin-right:5px; background:#555; border:1px solid #777;">
                                        <i class="fas fa-clock"></i>
                                    </a>

                                    <?php if ($u['role'] == 'admin'): ?>
                                        <span class="badge" style="background:#333; color:#aaa; border:1px solid #555;">Protected 🛡️</span>
                                    <?php else: ?>
                                        <?php if ($u['status'] == 'active'): ?>
                                            <a href="?page=users&action=confirm_suspend&id=<?php echo $u['id']; ?>" class="badge badge-orange" style="text-decoration:none; margin-right:5px;">Suspend 🚫</a>
                                        <?php else: ?>
                                            <a href="?page=users&action=confirm_activate&id=<?php echo $u['id']; ?>" class="badge badge-green" style="text-decoration:none; margin-right:5px;">Activate ✅</a>
                                        <?php endif; ?>

                                        <a href="?page=users&action=confirm_delete&id=<?php echo $u['id']; ?>" style="color:#ff4b4b;"><i class="fas fa-trash"></i></a>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <span style="color:#666; font-size:0.8rem; padding-right:10px;">Current Session</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>