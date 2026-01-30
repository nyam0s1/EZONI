<?php

/**
 * SALES / PRODUCE VIEW
 * - Update: "Sales Today" card is now a tappable link to the Daily Report.
 */

// --- BACKEND LOGIC ---

// 1. ADD NEW PRODUCE TYPE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_produce'])) {
    $threshold = !empty($_POST['min_threshold']) ? intval($_POST['min_threshold']) : 10;
    try {
        $stmt = $pdo->prepare("INSERT INTO produce (name, quantity, unit, category, min_threshold) VALUES (?, 0, ?, ?, ?)");
        $stmt->execute([$_POST['name'], $_POST['unit'], $_POST['category'], $threshold]);
        echo "<script>window.location.href='?page=sales';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Error: Product name might already exist.');</script>";
    }
}

// 2. HARVEST (Add Stock)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_harvest'])) {
    $qty = floatval($_POST['quantity']);
    if ($qty > 0) {
        $pdo->prepare("UPDATE produce SET quantity = quantity + ? WHERE id = ?")->execute([$qty, $_POST['id']]);

        // FIX APPLIED HERE: Added 'total_revenue' column set to 0
        $pdo->prepare("INSERT INTO sales_logs (product_name, action_type, quantity, unit, total_revenue, user) VALUES (?, 'Harvest', ?, ?, 0, ?)")
            ->execute([$_POST['name'], $qty, $_POST['unit'], $_SESSION['username']]);

        echo "<script>window.location.href='?page=sales';</script>";
    }
}

// 3. SELL (Remove Stock + Revenue + Customer)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_sell'])) {
    $qty = floatval($_POST['quantity']);
    $price = floatval($_POST['price']);
    // Capture Customer Name
    $customer = !empty($_POST['customer_name']) ? trim($_POST['customer_name']) : 'Walk-in';
    $total = $qty * $price;

    $stmt = $pdo->prepare("SELECT quantity FROM produce WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    $curr = $stmt->fetchColumn();

    if ($qty > 0 && $curr >= $qty) {
        $pdo->prepare("UPDATE produce SET quantity = quantity - ? WHERE id = ?")->execute([$qty, $_POST['id']]);

        $pdo->prepare("INSERT INTO sales_logs (product_name, action_type, quantity, unit, unit_price, total_revenue, customer_name, user) VALUES (?, 'Sold', ?, ?, ?, ?, ?, ?)")
            ->execute([$_POST['name'], $qty, $_POST['unit'], $price, $total, $customer, $_SESSION['username']]);

        echo "<script>window.location.href='?page=sales';</script>";
    } else {
        echo "<script>alert('Error: Not enough produce to sell!');</script>";
    }
}

// 4. DELETE
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM produce WHERE id = ?")->execute([$_GET['delete']]);
    echo "<script>window.location.href='?page=sales';</script>";
}

// --- FETCH SETTINGS ---
$cats = $pdo->query("SELECT option_value FROM system_options WHERE option_type = 'produce_category' ORDER BY option_value ASC")->fetchAll(PDO::FETCH_COLUMN);

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
?>

<?php if ($action == 'add'): ?>
    <div class="form-container">
        <div class="form-header"><a href="?page=sales" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h2>Add Produce Type</h2>
        </div>
        <div class="card" style="text-align:left; border-top: 3px solid #2196F3;">
            <form method="POST" style="display:flex; flex-direction:column; gap:15px;">
                <label style="color:#aaa;">Product Name</label>
                <input type="text" name="name" placeholder="e.g. Kales, Eggs, Milk" required>

                <div style="display:flex; gap:10px;">
                    <div style="flex:1;">
                        <label style="color:#aaa;">Category</label>
                        <select name="category" style="width:100%; padding:10px; background:rgba(0,0,0,0.4); color:white; border:1px solid #555; border-radius:5px;">
                            <?php foreach ($cats as $c) echo "<option value='$c'>$c</option>"; ?>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label style="color:#aaa;">Unit</label>
                        <input type="text" name="unit" placeholder="e.g. kg, trays" required>
                    </div>
                </div>

                <label style="color:#aaa;">Low Stock Alert Level</label>
                <input type="number" name="min_threshold" placeholder="10" value="10">

                <button type="submit" name="add_produce" style="background:#2196F3;">Create Product</button>
            </form>
        </div>
    </div>

<?php elseif ($action == 'harvest' && isset($_GET['id'])):
    $p = $pdo->prepare("SELECT * FROM produce WHERE id = ?");
    $p->execute([$_GET['id']]);
    $item = $p->fetch(); ?>
    <div class="form-container">
        <div class="form-header"><a href="?page=sales" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h2>Record Harvest</h2>
        </div>
        <div class="card" style="text-align:left; border-top: 3px solid #4CAF50;">
            <h3 style="margin-bottom:5px;"><?php echo $item['name']; ?></h3>
            <p style="color:#aaa; margin-bottom:20px;">Current Stock: <strong><?php echo $item['quantity'] . ' ' . $item['unit']; ?></strong></p>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                <input type="hidden" name="name" value="<?php echo $item['name']; ?>">
                <input type="hidden" name="unit" value="<?php echo $item['unit']; ?>">
                <label style="display:block; margin-bottom:5px; color:#ccc;">Quantity Harvested</label>
                <input type="number" step="0.01" name="quantity" placeholder="0.00" required style="margin-bottom:20px; font-size:1.2rem;">
                <button type="submit" name="submit_harvest" style="background:#4CAF50; width:100%;">+ Add to Stock</button>
            </form>
        </div>
    </div>

<?php elseif ($action == 'sell' && isset($_GET['id'])):
    $p = $pdo->prepare("SELECT * FROM produce WHERE id = ?");
    $p->execute([$_GET['id']]);
    $item = $p->fetch(); ?>
    <div class="form-container">
        <div class="form-header"><a href="?page=sales" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h2>Record Sale</h2>
        </div>
        <div class="card" style="text-align:left; border-top: 3px solid #FF9800;">
            <h3 style="margin-bottom:5px;"><?php echo $item['name']; ?></h3>
            <p style="color:#aaa; margin-bottom:20px;">Available: <strong><?php echo $item['quantity'] . ' ' . $item['unit']; ?></strong></p>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                <input type="hidden" name="name" value="<?php echo $item['name']; ?>">
                <input type="hidden" name="unit" value="<?php echo $item['unit']; ?>">

                <label style="display:block; margin-bottom:5px; color:#ccc;">Customer Name</label>
                <input type="text" name="customer_name" placeholder="Who is buying?" style="margin-bottom:15px;">

                <div style="display:flex; gap:15px; margin-bottom:15px;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:5px; color:#ccc;">Qty Sold</label>
                        <input type="number" step="0.01" name="quantity" id="qty" placeholder="0" required oninput="calcTotal()">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:5px; color:#ccc;">Price/Unit</label>
                        <input type="number" step="0.01" name="price" id="price" placeholder="0.00" required oninput="calcTotal()">
                    </div>
                </div>
                <div style="background:rgba(0,0,0,0.3); padding:15px; border-radius:5px; margin-bottom:20px; text-align:center;">
                    <span style="color:#aaa;">Total Revenue:</span> <strong style="color:#4CAF50; font-size:1.5rem;">Ksh <span id="total">0.00</span></strong>
                </div>
                <button type="submit" name="submit_sell" style="background:#FF9800; width:100%;">Confirm Sale</button>
            </form>
        </div>
        <script>
            function calcTotal() {
                document.getElementById('total').innerText = ((document.getElementById('qty').value || 0) * (document.getElementById('price').value || 0)).toFixed(2);
            }
        </script>
    </div>

<?php elseif ($action == 'history' && isset($_GET['id'])):
    // HISTORY VIEW (For specific product)
    $n = $pdo->prepare("SELECT name FROM produce WHERE id = ?");
    $n->execute([$_GET['id']]);
    $name = $n->fetchColumn();
    $logs = $pdo->prepare("SELECT * FROM sales_logs WHERE product_name = ? ORDER BY id DESC");
    $logs->execute([$name]);
    $history = $logs->fetchAll(); ?>
    <div class="form-container" style="max-width:800px;">
        <div class="form-header"><a href="?page=sales" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h2>History: <?php echo $name; ?></h2>
        </div>
        <div class="card" style="text-align:left;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Action</th>
                            <th>Customer</th>
                            <th>Qty</th>
                            <th>Revenue</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td data-label="Date" style="color:#888;"><?php echo date('d M, H:i', strtotime($h['log_date'])); ?></td>
                                <td data-label="Action"><span class="badge <?php echo ($h['action_type'] == 'Sold') ? 'badge-green' : 'badge-blue'; ?>"><?php echo $h['action_type']; ?></span></td>
                                <td data-label="Customer" style="color:#ccc;">
                                    <?php echo ($h['action_type'] == 'Sold' && !empty($h['customer_name'])) ? htmlspecialchars($h['customer_name']) : '-'; ?>
                                </td>
                                <td data-label="Qty"><strong><?php echo $h['quantity'] . ' ' . $h['unit']; ?></strong></td>
                                <td data-label="Revenue"><?php echo ($h['total_revenue'] > 0) ? '+' . $h['total_revenue'] : '-'; ?></td>
                                <td data-label="User"><?php echo $h['user']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($action == 'low_stock'): ?>
    <?php
    $low_items = $pdo->query("SELECT * FROM produce WHERE quantity <= min_threshold ORDER BY quantity ASC")->fetchAll();
    ?>
    <div class="form-container" style="max-width: 800px;">
        <div class="form-header">
            <a href="?page=sales" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h2 style="color:#ff4b4b;">⚠️ Low Produce Report</h2>
        </div>

        <div class="card" style="border-top: 3px solid #ff4b4b;">
            <p style="color:#aaa; margin-bottom:15px;">Produce items below threshold:</p>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Current Qty</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_items as $item): ?>
                            <tr>
                                <td data-label="Item"><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                <td data-label="Category"><span class="badge badge-blue"><?php echo htmlspecialchars($item['category']); ?></span></td>
                                <td data-label="Qty">
                                    <span style="color:#ff4b4b; font-weight:bold;">
                                        <?php echo $item['quantity'] . ' ' . $item['unit']; ?>
                                    </span>
                                </td>
                                <td data-label="Action" style="text-align:right;">
                                    <a href="?page=sales&action=harvest&id=<?php echo $item['id']; ?>" class="badge badge-green" style="text-decoration:none;">Harvest 📥</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($low_items)): ?>
                            <tr>
                                <td colspan="4" style="text-align:center; padding:30px; color:#4CAF50;">✅ All produce levels are healthy!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php else:
    // MAIN LIST
    $low_count = $pdo->query("SELECT COUNT(*) FROM produce WHERE quantity <= min_threshold")->fetchColumn();
    $today = date('Y-m-d');
    $sales_today = $pdo->prepare("SELECT SUM(total_revenue) FROM sales_logs WHERE action_type='Sold' AND DATE(log_date) = ?");
    $sales_today->execute([$today]);
    $rev = $sales_today->fetchColumn() ?: 0;
    $products = $pdo->query("SELECT * FROM produce ORDER BY id DESC")->fetchAll(); ?>

    <div class="metrics-grid">

        <a href="?page=reports&start=<?php echo $today; ?>&end=<?php echo $today; ?>" style="text-decoration:none; color:inherit; display:block;">
            <div class="metric-card" style="border-left:4px solid #4CAF50; transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                <h4>Sales Today <i class="fas fa-external-link-alt" style="font-size:0.8rem; margin-left:5px; opacity:0.5;"></i></h4>
                <div class="value" style="color:#4CAF50;">Ksh <?php echo number_format($rev); ?></div>
                <div class="sub-text">Tap to view Details</div>
            </div>
        </a>

        <a href="?page=sales&action=low_stock" style="text-decoration:none; color:inherit; display:block;">
            <div class="metric-card" style="border-left:4px solid #FF9800; cursor:pointer; transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                <h4>Low Stock</h4>
                <div class="value" style="<?php echo ($low_count > 0) ? 'color:#FF9800' : ''; ?>"><?php echo $low_count; ?></div>
                <div class="sub-text">Items needing harvest</div>
            </div>
        </a>
    </div>

    <div class="list-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2>🥦 Farm Produce</h2>
        <a href="?page=sales&action=add" style="background:#2196F3; color:white; padding:8px 15px; text-decoration:none; border-radius:5px;">+ New Product</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Stock</th>
                        <th>Category</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td data-label="Name"><?php echo $p['name']; ?></td>
                            <td data-label="Stock">
                                <span style="font-weight:bold; color:<?php echo ($p['quantity'] <= $p['min_threshold']) ? '#FF9800' : '#4CAF50'; ?>;">
                                    <?php echo $p['quantity'] . ' ' . $p['unit']; ?>
                                </span>
                            </td>
                            <td data-label="Category"><span class="badge" style="background:#444;"><?php echo $p['category']; ?></span></td>
                            <td data-label="Actions" style="text-align:right;">
                                <a href="?page=sales&action=harvest&id=<?php echo $p['id']; ?>" class="badge badge-green" style="text-decoration:none; margin-right:5px;">Harvest</a>
                                <a href="?page=sales&action=sell&id=<?php echo $p['id']; ?>" class="badge badge-orange" style="text-decoration:none; margin-right:10px;">Sell</a>
                                <a href="?page=sales&action=history&id=<?php echo $p['id']; ?>" style="color:#ccc; margin-right:10px;" title="History"><i class="fas fa-history"></i></a>
                                <a href="?page=sales&delete=<?php echo $p['id']; ?>" onclick="return confirm('Delete?')" style="color:#ff4b4b;"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>