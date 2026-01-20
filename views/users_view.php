<?php
/**
 * USERS MANAGEMENT VIEW - SECURE VERSION
 * - Features: Add User (w/ Toggle), Toggle Status, Secure Delete (w/ Password).
 * - Security: Admins cannot be suspended. Deletion requires Admin Password.
 */

// --- LOGIC ---

// 1. ADD NEW USER
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Check if username exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() > 0) {
        echo "<script>alert('Error: Username already taken.');</script>";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, 'active')";
        $pdo->prepare($sql)->execute([$username, $hashed, $role]);
        echo "<script>window.location.href='?page=users';</script>";
    }
}

// 2. TOGGLE STATUS (Suspend / Activate)
if (isset($_GET['toggle_id']) && isset($_GET['new_status'])) {
    $id = $_GET['toggle_id'];
    $status = $_GET['new_status'];
    
    // Check Target Role
    $chk = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $chk->execute([$id]);
    $target_role = $chk->fetchColumn();

    if ($id == $_SESSION['user_id']) {
        echo "<script>alert('You cannot suspend your own account.'); window.location.href='?page=users';</script>";
    } elseif ($target_role == 'admin') {
        echo "<script>alert('❌ Security Alert: Admins cannot be suspended.'); window.location.href='?page=users';</script>";
    } else {
        $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$status, $id]);
        echo "<script>window.location.href='?page=users';</script>";
    }
}

// 3. SECURE DELETE EXECUTION (New Logic)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_secure_delete'])) {
    $target_id = $_POST['target_id'];
    $admin_pass = $_POST['admin_password'];
    $admin_id = $_SESSION['user_id'];

    // Get Target Data for validation
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$target_id]);
    $target_role = $stmt->fetchColumn();

    // 1. Verify Admin Password
    $adm = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $adm->execute([$admin_id]);
    $real_hash = $adm->fetchColumn();

    if (password_verify($admin_pass, $real_hash)) {
        // 2. Apply Security Rules
        if ($target_id == $admin_id) {
            echo "<script>alert('❌ Error: You cannot delete your own account here.'); window.location.href='?page=users';</script>";
        } elseif ($target_role == 'admin') {
            echo "<script>alert('❌ Security: Administrators cannot be deleted.'); window.location.href='?page=users';</script>";
        } else {
            // 3. Perform Delete
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$target_id]);
            echo "<script>alert('User deleted successfully.'); window.location.href='?page=users';</script>";
        }
    } else {
        echo "<script>alert('❌ Error: Incorrect Admin Password. Deletion cancelled.');</script>";
    }
}

// 4. FILTER LOGIC
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

if ($filter == 'active') {
    $users = $pdo->query("SELECT * FROM users WHERE status = 'active' ORDER BY id DESC")->fetchAll();
} elseif ($filter == 'suspended') {
    $users = $pdo->query("SELECT * FROM users WHERE status = 'suspended' ORDER BY id DESC")->fetchAll();
} else {
    $users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
}

// 5. STATS COUNTS
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

<?php elseif ($action == 'confirm_delete'): ?>
    <?php 
        $del_id = $_GET['id']; 
        $chk = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $chk->execute([$del_id]);
        $del_name = $chk->fetchColumn();
    ?>
    <div class="form-container" style="max-width: 500px; margin-top:50px;">
        <div class="form-header">
            <a href="?page=users" class="back-btn"><i class="fas fa-arrow-left"></i> Cancel</a>
            <h2 style="color:#ff4b4b;">⚠️ Delete User?</h2>
        </div>
        <div class="card" style="text-align:center; border-top: 3px solid #ff4b4b;">
            <i class="fas fa-user-times" style="font-size:3rem; color:#ff4b4b; margin-bottom:20px;"></i>
            
            <p style="color:#ddd; margin-bottom:5px;">You are about to delete:</p>
            <h3 style="color:#fff; margin-bottom:20px;"><?php echo htmlspecialchars($del_name); ?></h3>
            
            <p style="color:#aaa; font-size:0.9rem; margin-bottom:20px;">
                To prevent accidents, please enter your <strong>Admin Password</strong> to confirm.
            </p>
            
            <form method="POST">
                <input type="hidden" name="target_id" value="<?php echo $del_id; ?>">
                
                <div class="password-wrapper" style="margin-bottom:20px; text-align:left;">
                    <input type="password" name="admin_password" placeholder="Enter YOUR Admin Password" required style="border:1px solid #ff4b4b;">
                    <button type="button" class="toggle-password-btn" onclick="togglePassword(this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <button type="submit" name="confirm_secure_delete" style="background:#ff4b4b; width:100%; font-weight:bold;">
                    <i class="fas fa-trash-alt"></i> Yes, Delete User
                </button>
            </form>
        </div>
    </div>

<?php else: ?>
    
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
        <h2>👥 User Management <?php if($filter!='all') echo "<span style='font-size:1rem; color:#aaa;'>(".ucfirst($filter).")</span>"; ?></h2>
        <a href="?page=users&action=add" style="background:#4CAF50; color:white; padding:8px 15px; text-decoration:none; border-radius:5px;">+ Add User</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td data-label="Username">
                            <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                            <?php if($u['id'] == $_SESSION['user_id']) echo " <small style='color:#4CAF50'>(You)</small>"; ?>
                        </td>
                        
                        <td data-label="Role">
                            <span class="badge <?php echo ($u['role']=='admin')?'badge-blue':'badge-orange'; ?>">
                                <?php echo ucfirst($u['role']); ?>
                            </span>
                        </td>
                        
                        <td data-label="Status">
                            <span class="badge <?php echo ($u['status']=='active')?'badge-green':'badge-red'; ?>">
                                <?php echo ucfirst($u['status']); ?>
                            </span>
                        </td>
                        
                        <td data-label="Action" style="text-align:right;">
                            <?php if($u['id'] != $_SESSION['user_id']): ?>
                                
                                <?php if($u['role'] == 'admin'): ?>
                                    <span class="badge" style="background:#333; color:#aaa; border:1px solid #555;">Protected 🛡️</span>
                                <?php else: ?>
                                    <?php if($u['status'] == 'active'): ?>
                                        <a href="?page=users&toggle_id=<?php echo $u['id']; ?>&new_status=suspended" class="badge badge-orange" style="text-decoration:none; margin-right:10px;">Suspend 🚫</a>
                                    <?php else: ?>
                                        <a href="?page=users&toggle_id=<?php echo $u['id']; ?>&new_status=active" class="badge badge-green" style="text-decoration:none; margin-right:10px;">Activate ✅</a>
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