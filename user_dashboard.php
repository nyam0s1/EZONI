<?php
session_start();
require 'includes/db.php';

// --- SESSION CHECK ---
$timeout_duration = 1800; 
if (isset($_SESSION['last_activity'])) {
    if ((time() - $_SESSION['last_activity']) > $timeout_duration) {
        session_unset(); session_destroy(); header("Location: index.php?msg=timeout"); exit();
    }
}
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
if ($_SESSION['role'] == 'admin') { header("Location: admin_dashboard.php"); exit(); }
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ezoni Farm - User</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="apple-touch-icon" href="images/logo.png">
    <meta name="theme-color" content="#0e1117">
</head>
<body class="dashboard-body">

    <div class="main-content">
        
        <div class="top-bar">
            <?php if($page == 'home'): ?><div class="brand-title">EZONI FARM</div>
            <?php else: ?><a href="?page=home" class="home-btn"><i class="fas fa-arrow-left"></i> Home</a><?php endif; ?>

            <a href="?page=profile" style="text-decoration:none;">
                <div style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <span style="color:#aaa; font-size:0.9rem;"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <div style="width:35px; height:35px; background:#4CAF50; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold; color:white;">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                </div>
            </a>
        </div>

        <?php
        if ($page == 'home') {
            ?>
            <div style="text-align:center; margin-bottom:30px; margin-top:20px;">
                <h1 style="margin:0; font-weight:300;">Hello, <span style="font-weight:700; color:#4CAF50;"><?php echo htmlspecialchars($_SESSION['username']); ?></span></h1>
                <p style="color:#888; margin-top:5px;">Select an app to get started</p>
            </div>
            <div class="launchpad-grid">
                <a href="?page=overview" class="app-card card-overview"><i class="fas fa-tachometer-alt"></i><span>Overview Stats</span></a>
                <a href="?page=stock" class="app-card card-stock"><i class="fas fa-boxes"></i><span>Inventory</span></a>
                <a href="?page=animals" class="app-card card-animals"><i class="fas fa-paw"></i><span>Livestock</span></a>
                <a href="?page=sales" class="app-card card-sales"><i class="fas fa-coins"></i><span>Produce</span></a>
                <a href="?page=reports" class="app-card card-reports"><i class="fas fa-chart-line"></i><span>Reports</span></a>
                <a href="logout.php" class="app-card card-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
                <a href="?page=delete_account" class="app-card card-delete"><i class="fas fa-trash-alt"></i><span>Delete Account</span></a>
            </div>
            <?php
        }

        elseif ($page == 'overview') { include 'views/user_overview.php'; }
        elseif ($page == 'profile') { include 'views/profile_view.php'; } // Profile View

        elseif ($page == 'alerts') {
            $stock_alerts = $pdo->query("SELECT id, name, quantity, unit, 'Inventory' as type FROM stock WHERE quantity < 10")->fetchAll(PDO::FETCH_ASSOC);
            $produce_alerts = $pdo->query("SELECT id, name, quantity, unit, 'Produce' as type FROM produce WHERE quantity < 10")->fetchAll(PDO::FETCH_ASSOC);
            $animal_alerts = $pdo->query("SELECT NULL as id, species as name, COUNT(*) as quantity, 'Heads' as unit, 'Livestock' as type FROM animals WHERE status='Active' GROUP BY species HAVING quantity < 5")->fetchAll(PDO::FETCH_ASSOC);
            $all_alerts = array_merge($stock_alerts, $produce_alerts, $animal_alerts);
            ?>
            <div class="form-container" style="max-width:900px;">
                <div class="form-header"><a href="?page=overview" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a><h2>⚠️ Low Stock Report</h2></div>
                <div class="card"><div class="table-responsive"><table class="data-table"><thead><tr><th>Type</th><th>Item Name</th><th>Current Qty</th><th style="text-align:right;">Action</th></tr></thead><tbody>
                    <?php foreach ($all_alerts as $alert): ?>
                    <tr>
                        <td data-label="Type"><span class="badge <?php echo ($alert['type']=='Inventory')?'badge-orange':(($alert['type']=='Produce')?'badge-green':'badge-blue'); ?>"><?php echo $alert['type']; ?></span></td>
                        <td data-label="Item Name" style="font-weight:bold; color:#fff;"><?php echo $alert['name']; ?></td>
                        <td data-label="Current Qty"><span style="color:#ff4b4b; font-weight:bold; font-size:1.1rem;"><?php echo $alert['quantity']; ?></span> <small style="color:#888;"><?php echo $alert['unit']; ?></small></td>
                        <td data-label="Action" style="text-align:right;">
                            <?php if($alert['type'] == 'Inventory'): ?><a href="?page=stock&action=restock&id=<?php echo $alert['id']; ?>" class="badge badge-green" style="text-decoration:none;">Restock 📥</a>
                            <?php elseif($alert['type'] == 'Produce'): ?><a href="?page=sales&action=harvest&id=<?php echo $alert['id']; ?>" class="badge badge-blue" style="text-decoration:none;">Harvest 🌾</a>
                            <?php else: ?><a href="?page=animals&action=add" class="badge badge-green" style="text-decoration:none;">+ Add</a><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($all_alerts)) echo "<tr><td colspan='4' style='text-align:center; padding:30px; color:#aaa;'>No low stock items found. Great job! 🎉</td></tr>"; ?>
                </tbody></table></div></div>
            </div>
            <?php
        }
        
        elseif ($page == 'delete_account') {
            $error = '';
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $pass = $_POST['password']; $id = $_SESSION['user_id'];
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?"); $stmt->execute([$id]); $stored_hash = $stmt->fetchColumn();
                if (password_verify($pass, $stored_hash)) {
                    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]); session_destroy();
                    echo "<script>alert('Account deleted.'); window.location.href='index.php';</script>";
                } else { $error = "Incorrect Password."; }
            }
            ?>
            <div class="form-container">
                <div class="form-header"><a href="?page=home" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a><h2>Delete Account</h2></div>
                <div class="card" style="text-align:center; border-top: 3px solid #ff4b4b;"><h3 style="color:#ff4b4b;">Delete Account?</h3><p style="color:#aaa; margin-bottom:20px;">This action is permanent.</p><?php if($error): ?><p style="color:#ff4b4b; margin-bottom:10px;"><?php echo $error; ?></p><?php endif; ?><form method="POST"><input type="password" name="password" placeholder="Confirm Password" required style="text-align:center; margin-bottom:15px;"><button type="submit" style="background:#ff4b4b; width:100%;">Confirm Delete</button></form></div>
            </div>
            <?php
        }

        elseif ($page == 'stock') { include 'views/stock_view.php'; }
        elseif ($page == 'animals') { include 'views/animals_view.php'; }
        elseif ($page == 'sales') { include 'views/sales_view.php'; }
        elseif ($page == 'reports') { include 'views/reports_view.php'; }
        else { echo "<div class='card' style='text-align:center;'><h2>404 - Page Not Found</h2><br><a href='?page=home' class='badge badge-blue'>Go Home</a></div>"; }
        ?>
    </div>
</body>
</html>