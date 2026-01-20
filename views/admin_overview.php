<?php
/**
 * ADMIN OVERVIEW VIEW - DYNAMIC ALERTS VERSION
 * - Update: "Low Stock" count now respects ALL custom thresholds:
 * 1. Inventory (quantity <= min_threshold)
 * 2. Produce (quantity <= min_threshold)
 * 3. Livestock (count < species_limit)
 */

// 1. CALCULATE METRICS
try {
    $today = date('Y-m-d');
    
    // Revenue Today
    $stmt = $pdo->prepare("SELECT SUM(total_revenue) FROM sales_logs WHERE DATE(log_date) = ? AND action_type='Sold'");
    $stmt->execute([$today]); 
    $rev = $stmt->fetchColumn() ?: 0;
    
    // Low Stock (Inventory + Produce) - DYNAMIC CHECK
    $stock_low = $pdo->query("SELECT COUNT(*) FROM stock WHERE quantity <= min_threshold")->fetchColumn() ?: 0;
    $prod_low = $pdo->query("SELECT COUNT(*) FROM produce WHERE quantity <= min_threshold")->fetchColumn() ?: 0;
    
    // Low Stock (Livestock) - DYNAMIC CHECK against species_limits
    // Query: Count animals by species, compare against limit
    $sql_animal_alerts = "
        SELECT COUNT(*) FROM (
            SELECT a.species, COUNT(*) as qty, l.min_threshold 
            FROM animals a 
            JOIN species_limits l ON a.species = l.species 
            WHERE a.status='Active' 
            GROUP BY a.species 
            HAVING qty <= l.min_threshold
        ) as alerts
    ";
    $animal_low = $pdo->query($sql_animal_alerts)->fetchColumn() ?: 0;
    
    $total_alerts = $stock_low + $prod_low + $animal_low;
    
    // Active Animals
    $animals = $pdo->query("SELECT COUNT(*) FROM animals WHERE status='Active'")->fetchColumn() ?: 0;
    
    // Total Users
    $users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0;
    
    // Recent Activity
    $recent_logs = $pdo->query("SELECT * FROM sales_logs ORDER BY id DESC LIMIT 8")->fetchAll();

} catch (Exception $e) { 
    $rev = 0; $total_alerts = 0; $animals = 0; $users = 0; $recent_logs = []; 
}
?>

<div class="metrics-grid">
    
    <a href="?page=reports" style="text-decoration:none;">
        <div class="metric-card" style="border-left:4px solid #4CAF50; transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
            <h4>Revenue Today</h4>
            <div class="value" style="color:#4CAF50;">Ksh <?php echo number_format($rev); ?></div>
            <div class="sub-text">Fresh income</div>
        </div>
    </a>
    
    <a href="?page=alerts" style="text-decoration:none;">
        <div class="metric-card" style="border-left:4px solid #FF9800; transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
            <h4>Low Stock</h4>
            <div class="value" style="color:#FF9800;"><?php echo $total_alerts; ?></div>
            <div class="sub-text">Items need restock</div>
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
                    <th style="text-align:right;">Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recent_logs as $l): ?>
                <tr>
                    <td data-label="Action">
                        <span class="badge <?php echo ($l['action_type']=='Sold')?'badge-green':(($l['action_type']=='Harvest')?'badge-blue':'badge-orange'); ?>">
                            <?php echo $l['action_type']; ?>
                        </span>
                    </td>
                    <td data-label="Product"><?php echo $l['product_name']; ?></td>
                    <td data-label="Time" style="color:#888; text-align:right;">
                        <?php echo date('H:i', strtotime($l['log_date'])); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($recent_logs)) echo "<tr><td colspan='3' style='text-align:center; color:#666;'>No recent activity</td></tr>"; ?>
            </tbody>
        </table>
    </div>
</div>