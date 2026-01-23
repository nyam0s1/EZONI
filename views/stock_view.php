<?php

/**
 * STOCK VIEW - FINAL COMPLETE
 * - Feature: "Add Item" now asks for Initial Cost.
 * - Feature: "Restock" has Purchase vs Harvest toggle.
 * - Feature: "Low Stock" & "History" reports included.
 */

// --- BACKEND LOGIC ---

// 1. ADD NEW ITEM (Updated with Cost)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_stock'])) {
    $threshold = !empty($_POST['min_threshold']) ? intval($_POST['min_threshold']) : 10;

    // Calculate Initial Value
    $qty = floatval($_POST['quantity']);
    $unit_cost = floatval($_POST['unit_cost']); // <--- New: Capture Initial Cost
    $total_cost = $qty * $unit_cost;

    $stmt = $pdo->prepare("INSERT INTO stock (name, quantity, unit, category, min_threshold) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['quantity'], $_POST['unit'], $_POST['category'], $threshold]);
    $id = $pdo->lastInsertId();

    // Log creation with Cost Data
    $pdo->prepare("INSERT INTO stock_logs (item_id, item_name, action_type, quantity, unit, unit_cost, total_cost, user) VALUES (?, ?, 'Created', ?, ?, ?, ?, ?)")
        ->execute([$id, $_POST['name'], $_POST['quantity'], $_POST['unit'], $unit_cost, $total_cost, $_SESSION['username']]);

    echo "<script>window.location.href='?page=stock';</script>";
}

// 2. EDIT ITEM
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_stock']) && $_SESSION['role'] == 'admin') {
    $threshold = intval($_POST['min_threshold']);
    $pdo->prepare("UPDATE stock SET name = ?, category = ?, unit = ?, min_threshold = ? WHERE id = ?")
        ->execute([$_POST['name'], $_POST['category'], $_POST['unit'], $threshold, $_POST['id']]);
    echo "<script>window.location.href='?page=stock';</script>";
}

// 3. SUBMIT RESTOCK (Smart Logic: Purchase vs Harvest)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_restock'])) {
    $qty = floatval($_POST['quantity']);
    $source = $_POST['source_type'] ?? 'purchase'; // Default to purchase if missing

    // LOGIC: Calculate Cost based on Source
    if ($source === 'harvest') {
        // Harvest: User inputs TOTAL cost (Labor+Seeds), we calculate Unit Cost
        $total = floatval($_POST['total_cost_input']);
        $cost = ($qty > 0) ? ($total / $qty) : 0;
        $action_label = 'Harvested';
    } else {
        // Purchase: User inputs UNIT cost, we calculate Total
        $cost = floatval($_POST['unit_cost_input']);
        $total = $qty * $cost;
        $action_label = 'Restocked';
    }

    if ($qty > 0) {
        // A. Update Stock Level
        $pdo->prepare("UPDATE stock SET quantity = quantity + ? WHERE id = ?")->execute([$qty, $_POST['id']]);

        // B. Log it with the correct Action Label & Cost Data
        $pdo->prepare("INSERT INTO stock_logs (item_id, item_name, action_type, quantity, unit, unit_cost, total_cost, user) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$_POST['id'], $_POST['name'], $action_label, $qty, $_POST['unit'], $cost, $total, $_SESSION['username']]);

        echo "<script>window.location.href='?page=stock';</script>";
    }
}

// 4. USE ITEM
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_use'])) {
    $qty = floatval($_POST['quantity']);
    $curr = $pdo->query("SELECT quantity FROM stock WHERE id=" . $_POST['id'])->fetchColumn();

    if ($qty > 0 && $curr >= $qty) {
        $pdo->prepare("UPDATE stock SET quantity = quantity - ? WHERE id = ?")->execute([$qty, $_POST['id']]);
        $pdo->prepare("INSERT INTO stock_logs (item_id, item_name, action_type, quantity, unit, user) VALUES (?, ?, 'Used', ?, ?, ?)")
            ->execute([$_POST['id'], $_POST['name'], $qty, $_POST['unit'], $_SESSION['username']]);
        echo "<script>window.location.href='?page=stock';</script>";
    } else {
        echo "<script>alert('Error: Not enough stock!');</script>";
    }
}

// 5. DELETE ITEM
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM stock WHERE id = ?")->execute([$_GET['delete']]);
    echo "<script>window.location.href='?page=stock';</script>";
}

// --- FETCH DATA ---
$cats = $pdo->query("SELECT option_value FROM system_options WHERE option_type = 'stock_category' ORDER BY option_value ASC")->fetchAll(PDO::FETCH_COLUMN);
$units = $pdo->query("SELECT DISTINCT unit FROM stock WHERE unit != '' ORDER BY unit ASC")->fetchAll(PDO::FETCH_COLUMN);

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
?>

<?php if ($action == 'add' || ($action == 'edit' && isset($_GET['id']) && $_SESSION['role'] == 'admin')):
    $is_edit = ($action == 'edit');
    $item = $is_edit ? $pdo->query("SELECT * FROM stock WHERE id=" . $_GET['id'])->fetch() : null;
    $pre_cat = isset($_GET['pre_cat']) ? trim(urldecode($_GET['pre_cat'])) : '';
?>
    <div class="form-container">
        <div class="form-header"><a href="?page=stock" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h2><?php echo $is_edit ? 'Edit Item' : 'Add Inventory'; ?></h2>
        </div>
        <div class="card" style="text-align:left; border-top: 3px solid #4CAF50;">
            <form method="POST" style="display:flex; flex-direction:column; gap:15px;">
                <?php if ($is_edit): ?><input type="hidden" name="id" value="<?php echo $item['id']; ?>"><?php endif; ?>

                <label style="color:#aaa;">Item Name</label>
                <input type="text" name="name" value="<?php echo $is_edit ? $item['name'] : ''; ?>" placeholder="Item Name" required>

                <div style="display:flex; gap:10px;">
                    <div style="flex:1;">
                        <label style="color:#aaa;">Quantity</label>
                        <input type="number" step="0.01" name="quantity" value="<?php echo $is_edit ? $item['quantity'] : ''; ?>" placeholder="Qty" required <?php echo $is_edit ? 'readonly style="opacity:0.6"' : ''; ?>>
                    </div>
                    <div style="flex:1;">
                        <label style="color:#aaa;">Unit</label>
                        <input type="text" name="unit" list="unit_list" value="<?php echo $is_edit ? $item['unit'] : ''; ?>" placeholder="e.g. kg" required>
                        <datalist id="unit_list"><?php foreach ($units as $u) echo "<option value='$u'>"; ?></datalist>
                    </div>
                </div>

                <?php if (!$is_edit): ?>
                    <div style="flex:1;">
                        <label style="color:#aaa;">Cost per Unit (Initial Stock)</label>
                        <input type="number" step="0.01" name="unit_cost" placeholder="Price per kg/bag" required style="width:100%; box-sizing:border-box;">
                    </div>
                <?php endif; ?>

                <div style="flex:1;">
                    <label style="color:#aaa;">Low Stock Warning Level</label>
                    <input type="number" name="min_threshold" value="<?php echo $is_edit ? $item['min_threshold'] : '10'; ?>" placeholder="Alert me when below..." required>
                </div>

                <label style="color:#aaa;">Category</label>
                <select name="category" required style="padding:10px; background:rgba(0,0,0,0.4); color:white; border:1px solid #555; border-radius:5px;">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($cats as $c): ?>
                        <?php $selected = '';
                        if ($is_edit && $item['category'] == $c) $selected = 'selected';
                        elseif (!$is_edit && strcasecmp(trim($c), $pre_cat) == 0) $selected = 'selected'; ?>
                        <option value="<?php echo $c; ?>" <?php echo $selected; ?>><?php echo $c; ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" name="<?php echo $is_edit ? 'edit_stock' : 'add_stock'; ?>" style="background:#4CAF50;">
                    <?php echo $is_edit ? 'Update Details' : 'Save Item'; ?>
                </button>
            </form>
        </div>
    </div>

<?php elseif ($action == 'restock' && isset($_GET['id'])): ?>
    <?php
    $i = $pdo->prepare("SELECT * FROM stock WHERE id = ?");
    $i->execute([$_GET['id']]);
    $item = $i->fetch();
    ?>
    <div class="form-container">
        <div class="form-header"><a href="?page=stock" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h2>Add Inventory</h2>
        </div>
        <div class="card" style="text-align:left; border-top: 3px solid #4CAF50;">
            <h3 style="margin:0; margin-bottom:15px;"><?php echo $item['name']; ?></h3>

            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                <input type="hidden" name="name" value="<?php echo $item['name']; ?>">
                <input type="hidden" name="unit" value="<?php echo $item['unit']; ?>">

                <div style="margin-bottom:20px; background:#2c3e50; padding:10px; border-radius:8px; display:flex; gap:10px;">
                    <label style="flex:1; cursor:pointer;">
                        <input type="radio" name="source_type" value="purchase" checked onchange="toggleSource()" style="margin-right:5px;">
                        🛒 Purchase (Buy)
                    </label>
                    <label style="flex:1; cursor:pointer;">
                        <input type="radio" name="source_type" value="harvest" onchange="toggleSource()" style="margin-right:5px;">
                        🌾 Harvest (Create)
                    </label>
                </div>

                <div style="display:flex; gap:15px; margin-bottom:15px;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:5px; color:#ccc;">Add Quantity</label>
                        <input type="number" step="0.01" name="quantity" id="qty" placeholder="0" required oninput="calcCost()">
                    </div>

                    <div style="flex:1;">
                        <label id="cost_label" style="display:block; margin-bottom:5px; color:#ccc;">Cost per Unit</label>

                        <input type="number" step="0.01" name="unit_cost_input" id="unit_cost_input" placeholder="Price per item" oninput="calcCost()">

                        <input type="number" step="0.01" name="total_cost_input" id="total_cost_input" placeholder="Total Expense" style="display:none;" oninput="calcCost()">
                    </div>
                </div>

                <div style="background:rgba(0,0,0,0.3); padding:15px; border-radius:5px; margin-bottom:20px; text-align:center;">
                    <span id="summary_text" style="color:#aaa;">Total Expense:</span>
                    <strong style="color:#ff4b4b; font-size:1.5rem;">Ksh <span id="display_total">0.00</span></strong>
                    <div id="unit_summary" style="font-size:0.9rem; color:#4CAF50; display:none;">
                        (approx Ksh <span id="calc_unit_cost">0.00</span> / unit)
                    </div>
                </div>

                <button type="submit" name="submit_restock" style="background:#4CAF50; width:100%;">+ Confirm Add</button>
            </form>
        </div>

        <script>
            function toggleSource() {
                const isHarvest = document.querySelector('input[name="source_type"][value="harvest"]').checked;

                // Toggle Fields
                document.getElementById('unit_cost_input').style.display = isHarvest ? 'none' : 'block';
                document.getElementById('total_cost_input').style.display = isHarvest ? 'block' : 'none';

                // Toggle Labels
                document.getElementById('cost_label').innerText = isHarvest ? 'Total Production Cost' : 'Cost per Unit';
                document.getElementById('summary_text').innerText = isHarvest ? 'Recorded Value:' : 'Total Expense:';

                // Reset Values to avoid confusion
                document.getElementById('unit_cost_input').value = '';
                document.getElementById('total_cost_input').value = '';
                document.getElementById('display_total').innerText = '0.00';

                calcCost();
            }

            function calcCost() {
                const qty = parseFloat(document.getElementById('qty').value) || 0;
                const isHarvest = document.querySelector('input[name="source_type"][value="harvest"]').checked;

                if (isHarvest) {
                    // Logic: User inputs TOTAL, we display that directly
                    const total = parseFloat(document.getElementById('total_cost_input').value) || 0;
                    document.getElementById('display_total').innerText = total.toFixed(2);

                    // Show calculated unit cost
                    if (qty > 0) {
                        document.getElementById('unit_summary').style.display = 'block';
                        document.getElementById('calc_unit_cost').innerText = (total / qty).toFixed(2);
                    } else {
                        document.getElementById('unit_summary').style.display = 'none';
                    }
                } else {
                    // Logic: User inputs UNIT, we calc Total
                    const unitCost = parseFloat(document.getElementById('unit_cost_input').value) || 0;
                    const total = qty * unitCost;
                    document.getElementById('display_total').innerText = total.toFixed(2);
                    document.getElementById('unit_summary').style.display = 'none';
                }
            }
        </script>
    </div>

<?php elseif ($action == 'use' && isset($_GET['id'])): ?>
    <?php
    $i = $pdo->prepare("SELECT * FROM stock WHERE id = ?");
    $i->execute([$_GET['id']]);
    $item = $i->fetch();
    ?>
    <div class="form-container">
        <div class="form-header"><a href="?page=stock" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h2>Record Usage</h2>
        </div>
        <div class="card" style="text-align:left; border-top: 3px solid #ff4b4b;">
            <h3 style="margin-bottom:5px;"><?php echo $item['name']; ?></h3>
            <p style="color:#aaa; margin-bottom:20px;">Available: <strong><?php echo $item['quantity'] . ' ' . $item['unit']; ?></strong></p>

            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                <input type="hidden" name="name" value="<?php echo $item['name']; ?>">
                <input type="hidden" name="unit" value="<?php echo $item['unit']; ?>">

                <label style="display:block; margin-bottom:5px; color:#ccc;">Quantity Used / Removed</label>
                <input type="number" step="0.01" name="quantity" placeholder="0.00" required style="margin-bottom:20px; font-size:1.2rem;">

                <button type="submit" name="submit_use" style="background:#ff4b4b; width:100%;">- Confirm Usage</button>
            </form>
        </div>
    </div>

<?php elseif ($action == 'report'): ?>
    <?php
    $today = date('Y-m-d');
    $records = $pdo->query("SELECT * FROM stock_logs WHERE DATE(log_date) = '$today' ORDER BY id DESC")->fetchAll();
    ?>
    <div class="form-container" style="max-width:800px;">
        <div class="form-header"><a href="?page=stock" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h2>Stock Activity: Today</h2>
        </div>
        <div class="card" style="text-align:left;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Item</th>
                            <th>Action</th>
                            <th>Qty</th>
                            <th>Cost</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $r): ?>
                            <tr>
                                <td data-label="Time" style="color:#888;"><?php echo date('H:i', strtotime($r['log_date'])); ?></td>
                                <td data-label="Item"><?php echo $r['item_name']; ?></td>
                                <td data-label="Action">
                                    <span class="badge <?php echo ($r['action_type'] == 'Restocked' || $r['action_type'] == 'Created' || $r['action_type'] == 'Harvested') ? 'badge-green' : 'badge-red'; ?>">
                                        <?php echo $r['action_type']; ?>
                                    </span>
                                </td>
                                <td data-label="Qty"><strong><?php echo $r['quantity'] . ' ' . $r['unit']; ?></strong></td>
                                <td data-label="Cost">
                                    <?php echo ($r['total_cost'] > 0) ? '<span style="color:#ff4b4b">-' . $r['total_cost'] . '</span>' : '-'; ?>
                                </td>
                                <td data-label="User"><?php echo $r['user']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($action == 'history' && isset($_GET['id'])): ?>
    <?php
    $n = $pdo->prepare("SELECT name FROM stock WHERE id = ?");
    $n->execute([$_GET['id']]);
    $name = $n->fetchColumn();
    $logs = $pdo->prepare("SELECT * FROM stock_logs WHERE item_id = ? ORDER BY id DESC");
    $logs->execute([$_GET['id']]);
    $history = $logs->fetchAll();
    ?>
    <div class="form-container" style="max-width:800px;">
        <div class="form-header"><a href="?page=stock" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h2>History: <?php echo $name; ?></h2>
        </div>
        <div class="card" style="text-align:left;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Action</th>
                            <th>Qty</th>
                            <th>Cost</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td data-label="Date" style="color:#888;"><?php echo date('d M Y, H:i', strtotime($h['log_date'])); ?></td>
                                <td data-label="Action">
                                    <span class="badge <?php echo ($h['action_type'] == 'Restocked' || $h['action_type'] == 'Created' || $h['action_type'] == 'Harvested') ? 'badge-green' : 'badge-red'; ?>">
                                        <?php echo $h['action_type']; ?>
                                    </span>
                                </td>
                                <td data-label="Qty"><strong><?php echo $h['quantity'] . ' ' . $h['unit']; ?></strong></td>
                                <td data-label="Cost"><?php echo ($h['total_cost'] > 0) ? $h['total_cost'] : '-'; ?></td>
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
    $low_items = $pdo->query("SELECT * FROM stock WHERE quantity <= min_threshold ORDER BY quantity ASC")->fetchAll();
    ?>
    <div class="form-container" style="max-width: 800px;">
        <div class="form-header"><a href="?page=stock" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h2 style="color:#ff4b4b;">⚠️ Low Stock Report</h2>
        </div>
        <div class="card" style="border-top: 3px solid #ff4b4b;">
            <p style="color:#aaa; margin-bottom:15px;">Inventory items requiring restocking:</p>
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
                                <td data-label="Qty"><span style="color:#ff4b4b; font-weight:bold;"><?php echo $item['quantity'] . ' ' . $item['unit']; ?></span></td>
                                <td data-label="Action" style="text-align:right;">
                                    <a href="?page=stock&action=restock&id=<?php echo $item['id']; ?>" class="badge badge-green" style="text-decoration:none;">Restock 📥</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($low_items)): ?>
                            <tr>
                                <td colspan="4" style="text-align:center; padding:30px; color:#4CAF50;">✅ All stock levels are healthy!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php else: ?>
    <?php
    $low_stock = $pdo->query("SELECT COUNT(*) FROM stock WHERE quantity <= min_threshold")->fetchColumn();
    $today = date('Y-m-d');
    $act_today = $pdo->query("SELECT COUNT(*) FROM stock_logs WHERE DATE(log_date) = '$today'")->fetchColumn();
    $items = $pdo->query("SELECT * FROM stock ORDER BY id DESC")->fetchAll();
    ?>

    <div class="metrics-grid">
        <a href="?page=stock&action=report" style="text-decoration:none;">
            <div class="metric-card" style="border-left:4px solid #2196F3;">
                <h4>Activity Today</h4>
                <div class="value" style="color:#2196F3;"><?php echo $act_today; ?> <small>actions</small></div>
                <div class="sub-text">Click for report</div>
            </div>
        </a>

        <a href="?page=stock&action=low_stock" style="text-decoration:none; color:inherit;">
            <div class="metric-card" style="border-left:4px solid #FF9800; cursor:pointer;">
                <h4>Low Stock</h4>
                <div class="value" style="<?php echo ($low_stock > 0) ? 'color:#FF9800' : ''; ?>"><?php echo $low_stock; ?></div>
                <div class="sub-text">Items needing attention</div>
            </div>
        </a>
    </div>

    <div class="list-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2>📦 Stock Inventory</h2>
        <a href="?page=stock&action=add" style="background:#4CAF50; color:white; padding:8px 15px; text-decoration:none; border-radius:5px;">+ Add Item</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Qty</th>
                        <th>Category</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td data-label="Name"><?php echo $item['name']; ?></td>
                            <td data-label="Quantity">
                                <span style="font-weight:bold; color:<?php echo ($item['quantity'] <= $item['min_threshold']) ? '#FF9800' : '#4CAF50'; ?>;">
                                    <?php echo $item['quantity'] . ' ' . $item['unit']; ?>
                                </span>
                            </td>
                            <td data-label="Category"><span class="badge" style="background:#444;"><?php echo $item['category']; ?></span></td>
                            <td data-label="Actions" style="text-align:right;">
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <a href="?page=stock&action=edit&id=<?php echo $item['id']; ?>" class="badge badge-blue" style="text-decoration:none; margin-right:5px;">Edit ✏️</a>
                                <?php endif; ?>
                                <a href="?page=stock&action=restock&id=<?php echo $item['id']; ?>" class="badge badge-green" style="text-decoration:none; margin-right:5px;">Restock 📥</a>
                                <a href="?page=stock&action=use&id=<?php echo $item['id']; ?>" class="badge badge-red" style="text-decoration:none; margin-right:10px;">Use 📉</a>
                                <a href="?page=stock&action=history&id=<?php echo $item['id']; ?>" style="color:#ccc; margin-right:10px;" title="History"><i class="fas fa-history"></i></a>
                                <a href="?page=stock&delete=<?php echo $item['id']; ?>" onclick="return confirm('Delete?')" style="color:#ff4b4b;"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>