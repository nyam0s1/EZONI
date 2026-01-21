<?php

/**
 * ALERTS VIEW - DEDICATED PAGE
 * - Displays Low Stock for Inventory, Produce, and Livestock.
 * - Update: Inventory items now only show "Restock" action.
 */

// 1. FETCH DATA
try {
    // A. Stock Alerts
    $stock_alerts = $pdo->query("SELECT id, name, category, quantity, unit, 'stock' as type FROM stock WHERE quantity <= min_threshold")->fetchAll();

    // B. Produce Alerts
    $produce_alerts = $pdo->query("SELECT id, name, category, quantity, unit, 'produce' as type FROM produce WHERE quantity <= min_threshold")->fetchAll();

    // C. Livestock Alerts
    $animal_sql = "
        SELECT a.species as name, 'Livestock' as category, COUNT(*) as quantity, 'Heads' as unit, 'livestock' as type
        FROM animals a 
        JOIN species_limits l ON a.species = l.species 
        WHERE a.status='Active' 
        GROUP BY a.species, l.min_threshold 
        HAVING quantity <= l.min_threshold
    ";
    try {
        $animal_alerts = $pdo->query($animal_sql)->fetchAll();
    } catch (Exception $e) {
        $animal_alerts = [];
    }

    $all_alerts = array_merge($stock_alerts, $produce_alerts, $animal_alerts);
} catch (Exception $e) {
    $all_alerts = [];
}
?>

<div class="form-container" style="max-width: 1000px;">
    <div class="form-header">
        <?php $dashboard = ($_SESSION['role'] == 'admin') ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>
        <a href="<?php echo $dashboard; ?>?page=overview" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
        <h2 style="color:#ff4b4b;">⚠️ Attention Needed</h2>
    </div>

    <div class="card" style="border-top: 3px solid #ff4b4b;">
        <p style="color:#aaa; margin-bottom:20px;">The following items have reached their low stock threshold:</p>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Qty</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_alerts as $row): ?>
                        <tr>
                            <td data-label="Item"><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>

                            <td data-label="Type">
                                <?php if ($row['type'] == 'stock'): ?>
                                    <span class="badge" style="background:#555;">Feed/Input</span>
                                <?php elseif ($row['type'] == 'livestock'): ?>
                                    <span class="badge" style="background:#8d6e63;">Animal</span>
                                <?php else: ?>
                                    <span class="badge" style="background:#2196F3;">Produce</span>
                                <?php endif; ?>
                                <br>
                                <small style="color:#aaa;"><?php echo htmlspecialchars($row['category']); ?></small>
                            </td>

                            <td data-label="Qty">
                                <span style="color:#ff4b4b; font-weight:bold;">
                                    <?php echo $row['quantity'] . ' ' . $row['unit']; ?>
                                </span>
                            </td>

                            <td data-label="Action" style="text-align:right;">
                                <?php if ($row['type'] == 'stock'): ?>
                                    <a href="?page=stock&action=restock&id=<?php echo $row['id']; ?>" class="badge badge-green">Restock</a>

                                <?php elseif ($row['type'] == 'livestock'): ?>
                                    <a href="?page=animals&action=add&pre_species=<?php echo urlencode($row['name']); ?>" class="badge badge-blue">+ Add</a>

                                <?php elseif ($row['type'] == 'produce'): ?>
                                    <a href="?page=sales&action=harvest&id=<?php echo $row['id']; ?>" class="badge badge-green">Harvest</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($all_alerts)): ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding:30px; color:#4CAF50;">✅ Everything is well stocked!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>