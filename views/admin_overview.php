<?php

/**
 * ADMIN OVERVIEW VIEW - VISUAL FIX
 * - Update: "Low Stock" card now matches User Dashboard style (Red/Attention Needed).
 */

// 1. CALCULATE METRICS
try {
    $today = date('Y-m-d');

    // Revenue Today
    $stmt = $pdo->prepare("SELECT SUM(total_revenue) FROM sales_logs WHERE DATE(log_date) = ? AND action_type='Sold'");
    $stmt->execute([$today]);
    $rev = $stmt->fetchColumn() ?: 0;

    // Low Stock (Inventory + Produce)
    $stock_low = $pdo->query("SELECT COUNT(*) FROM stock WHERE quantity <= min_threshold")->fetchColumn() ?: 0;
    $prod_low = $pdo->query("SELECT COUNT(*) FROM produce WHERE quantity <= min_threshold")->fetchColumn() ?: 0;

    // Low Stock (Livestock)
    $sql_animal_alerts = "
        SELECT COUNT(*) FROM (
            SELECT a.species, COUNT(*) as qty, l.min_threshold 
            FROM animals a 
            JOIN species_limits l ON a.species = l.species 
            WHERE a.status='Active' 
            GROUP BY a.species, l.min_threshold 
            HAVING qty <= l.min_threshold
        ) as alerts
    ";
    $animal_low = $pdo->query($sql_animal_alerts)->fetchColumn() ?: 0;

    $total_alerts = $stock_low + $prod_low + $animal_low;

    // Active Animals
    $animals = $pdo->query("SELECT COUNT(*) FROM animals WHERE status='Active'")->fetchColumn() ?: 0;

    // Total Users
    $users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0;

    // Check Pending Users (For Alert)
    $pending_users = $pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn() ?: 0;

    // RECENT ACTIVITY
    $sql_recent = "
        SELECT s.*, p.id as pid 
        FROM sales_logs s 
        LEFT JOIN produce p ON s.product_name = p.name 
        ORDER BY s.id DESC LIMIT 8
    ";
    $recent_logs = $pdo->query($sql_recent)->fetchAll();
} catch (Exception $e) {
    $rev = 0;
    $total_alerts = 0;
    $animals = 0;
    $users = 0;
    $recent_logs = [];
}
?>

<div class="metrics-grid">

    <?php if ($pending_users > 0): ?>
        <a href="?page=users&filter=pending" style="text-decoration:none;">
            <div class="metric-card" style="border-left:4px solid #FF9800; background: rgba(255, 152, 0, 0.15); transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                <h4 style="color:#FF9800;">New Registrations</h4>
                <div class="value" style="color:#FF9800;"><?php echo $pending_users; ?></div>
                <div class="sub-text">Pending Approval</div>
            </div>
        </a>
    <?php endif; ?>

    <a href="?page=reports" style="text-decoration:none;">
        <div class="metric-card" style="border-left:4px solid #4CAF50; transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
            <h4>Revenue Today</h4>
            <div class="value" style="color:#4CAF50;">Ksh <?php echo number_format($rev); ?></div>
            <div class="sub-text">Fresh income</div>
        </div>
    </a>

    <a href="?page=alerts" style="text-decoration:none;">
        <div class="metric-card" style="border-left: 4px solid #ff4b4b; transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
            <h4>Attention Needed</h4>
            <div class="value" style="color:#ff4b4b;"><?php echo $total_alerts; ?></div>
            <div class="sub-text">Click to view Low Stock</div>
        </div>
    </a>

    <a href="?page=animals" style="text-decoration:none;">
        <div class="metric-card" style="border-left:4px solid #2196F3; transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
            <h4>Livestock</h4>
            <div class="value"><?php echo $animals; ?></div>
            <div class="sub-text">Active Animals</div>
        </div>
    </a>

    <a href="?page=users" style="text-decoration:none;">
        <div class="metric-card" style="transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
            <h4>Users</h4>
            <div class="value"><?php echo $users; ?></div>
            <div class="sub-text">System Access</div>
        </div>
    </a>
</div>

<div class="card">
    <h3>🕒 Recent Activity</h3>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Product</th>
                    <th style="text-align:right;">Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_logs as $l): ?>
                    <tr
                        <?php if ($l['pid']): ?>
                        onclick="window.location.href='?page=sales&action=history&id=<?php echo $l['pid']; ?>'"
                        style="cursor:pointer;"
                        title="View History for <?php echo $l['product_name']; ?>"
                        <?php endif; ?>>
                        <td data-label="Action">
                            <span class="badge <?php echo ($l['action_type'] == 'Sold') ? 'badge-green' : (($l['action_type'] == 'Harvest') ? 'badge-blue' : 'badge-orange'); ?>">
                                <?php echo $l['action_type']; ?>
                            </span>
                        </td>
                        <td data-label="Product"><?php echo $l['product_name']; ?></td>
                        <td data-label="Date & Time" style="color:#888; text-align:right; font-size:0.9rem;">
                            <?php echo date('d M, H:i', strtotime($l['log_date'])); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($recent_logs)) echo "<tr><td colspan='3' style='text-align:center; color:#666;'>No recent activity</td></tr>"; ?>
            </tbody>
        </table>
    </div>
</div>