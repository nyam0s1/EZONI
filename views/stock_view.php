<?php

/**
 * STOCK VIEW - FULL VERSION
 * - Categories: Strict Dropdown (fetched from Settings).
 * - Units: Smart Input (Type new or select existing).
 * - Features: Add, Edit, Restock, Use, History, Reports, Delete.
 */

// --- BACKEND LOGIC ---

// 1. ADD NEW ITEM
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_stock'])) {
    $stmt = $pdo->prepare("INSERT INTO stock (name, quantity, unit, category) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['quantity'], $_POST['unit'], $_POST['category']]);

    // Log Creation
    $id = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO stock_logs (item_id, item_name, action_type, quantity, unit, user) VALUES (?, ?, 'Created', ?, ?, ?)")
        ->execute([$id, $_POST['name'], $_POST['quantity'], $_POST['unit'], $_SESSION['username']]);

    echo "<script>window.location.href='?page=stock';</script>";
}

// 2. EDIT ITEM (Admin Only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_stock']) && $_SESSION['role'] == 'admin') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $category = $_POST['category'];
    $unit = $_POST['unit'];

    $pdo->prepare("UPDATE stock SET name = ?, category = ?, unit = ? WHERE id = ?")
        ->execute([$name, $category, $unit, $id]);

    echo "<script>window.location.href='?page=stock';</script>";
}

// 3. RESTOCK (Green Action)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_restock'])) {
    $qty = floatval($_POST['quantity']);
    $cost = floatval($_POST['cost']);
    $total = $qty * $cost;

    if ($qty > 0) {
        // Update Quantity
        $pdo->prepare("UPDATE stock SET quantity = quantity + ? WHERE id = ?")->execute([$qty, $_POST['id']]);

        // Log Transaction
        $pdo->prepare("INSERT INTO stock_logs (item_id, item_name, action_type, quantity, unit, unit_cost, total_cost, user) VALUES (?, ?, 'Restocked', ?, ?, ?, ?, ?)")
            ->execute([$_POST['id'], $_POST['name'], $qty, $_POST['unit'], $cost, $total, $_SESSION['username']]);

        echo "<script>window.location.href='?page=stock';</script>";
    }
}

// 4. USE (Red Action)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_use'])) {
    $qty = floatval($_POST['quantity']);

    // Check current stock first
    $stmt = $pdo->prepare("SELECT quantity FROM stock WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    $curr = $stmt->fetchColumn();

    if ($qty > 0 && $curr >= $qty) {
        // Deduct Quantity
        $pdo->prepare("UPDATE stock SET quantity = quantity - ? WHERE id = ?")->execute([$qty, $_POST['id']]);

        // Log Usage
        $pdo->prepare("INSERT INTO stock_logs (item_id, item_name, action_type, quantity, unit, user) VALUES (?, ?, 'Used', ?, ?, ?)")
            ->execute([$_POST['id'], $_POST['name'], $qty, $_POST['unit'], $_SESSION['username']]);

        echo "<script>window.location.href='?page=stock';</script>";
    } else {
        echo "<script>alert('Error: Not enough stock available!');</script>";
    }
}

// 5. DELETE
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM stock WHERE id = ?")->execute([$_GET['delete']]);
    echo "<script>window.location.href='?page=stock';</script>";
}

// --- FETCH DATA FOR DROPDOWNS ---
// Categories: From Settings (Fixed List)
$cats = $pdo->query("SELECT option_value FROM system_options WHERE option_type = 'stock_category' ORDER BY option_value ASC")->fetchAll(PDO::FETCH_COLUMN);
// Units: From Stock Table (Self-Learning List)
$units = $pdo->query("SELECT DISTINCT unit FROM stock WHERE unit != '' ORDER BY unit ASC")->fetchAll(PDO::FETCH_COLUMN);

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
?>

<?php if ($action == 'add' || ($action == 'edit' && isset($_GET['id']) && $_SESSION['role'] == 'admin')):
    // ADD / EDIT FORM
    $is_edit = ($action == 'edit');
    $item = $is_edit ? $pdo->query("SELECT * FROM stock WHERE id=" . $_GET['id'])->fetch() : null; ?>

    <div class="form-container">
        <div class="form-header"><a href="?page=stock" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h2><?php echo $is_edit ? 'Edit Item' : 'Add Inventory'; ?></h2>
        </div>
        <div class="card" style="text-align:left; border-top: 3px solid #4CAF50;">
            <form method="POST" style="display:flex; flex-direction:column; gap:15px;">
                <?php if ($is_edit): ?><input type="hidden" name="id" value="<?php echo $item['id']; ?>"><?php endif; ?>

                <label style="color:#aaa;">Item Name</label>
                <input type="text" name="name" value="<?php echo $is_edit ? $item['name'] : ''; ?>" placeholder="Item Name (e.g. Dairy Meal)" required>

                <div style="display:flex; gap:10px;">
                    <div style="flex:1;">
                        <label style="color:#aaa;">Quantity</label>
                        <input type="number" step="0.01" name="quantity" value="<?php echo $is_edit ? $item['quantity'] : ''; ?>" placeholder="Qty" required <?php echo $is_edit ? 'readonly style="opacity:0.6"' : ''; ?>>
                    </div>
                    <div style="flex:1;">
                        <label style="color:#aaa;">Unit (Type or Select)</label>
                        <input type="text" name="unit" list="unit_list" value="<?php echo $is_edit ? $item['unit'] : ''; ?>" placeholder="e.g. kg" required>
                        <datalist id="unit_list">
                            <?php foreach ($units as $u) echo "<option value='$u'>"; ?>
                        </datalist>
                    </div>
                </div>

                <label style="color:#aaa;">Category</label>
                <select name="category" required style="padding:10px; background:rgba(0,0,0,0.4); color:white; border:1px solid #555; border-radius:5px;">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($cats as $c): ?>
                        <option value="<?php echo $c; ?>" <?php echo ($is_edit && $item['category'] == $c) ? 'selected' : ''; ?>><?php echo $c; ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" name="<?php echo $is_edit ? 'edit_stock' : 'add_stock'; ?>" style="background:#4CAF50;"><?php echo $is_edit ? 'Update Details' : 'Save Item'; ?></button>
            </form>
        </div>
    </div>

<?php elseif ($action == 'restock' && isset($_GET['id'])):
    // RESTOCK FORM
    $i = $pdo->prepare("SELECT * FROM stock WHERE id = ?");
    $i->execute([$_GET['id']]);
    $item = $i->fetch(); ?>

    <div class="form-container">
        <div class="form-header"><a href="?page=stock" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h2>Restock Inventory</h2>
        </div>
        <div class="card" style="text-align:left; border-top: 3px solid #4CAF50;">
            <h3 style="margin:0; margin-bottom:15px;"><?php echo $item['name']; ?></h3>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                <input type="hidden" name="name" value="<?php echo $item['name']; ?>">
                <input type="hidden" name="unit" value="<?php echo $item['unit']; ?>">

                <div style="display:flex; gap:15px; margin-bottom:15px;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:5px; color:#ccc;">Add Qty</label>
                        <input type="number" step="0.01" name="quantity" id="qty" placeholder="0" required oninput="calcCost()">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:5px; color:#ccc;">Cost/Unit</label>
                        <input type="number" step="0.01" name="cost" id="cost" placeholder="0.00" oninput="calcCost()">
                    </div>
                </div>

                <div style="background:rgba(0,0,0,0.3); padding:15px; border-radius:5px; margin-bottom:20px; text-align:center;">
                    <span style="color:#aaa;">Total Expense:</span> <strong style="color:#ff4b4b; font-size:1.5rem;">Ksh <span id="total">0.00</span></strong>
                </div>

                <button type="submit" name="submit_restock" style="background:#4CAF50; width:100%;">+ Confirm Restock</button>
            </form>
        </div>
        <script>
            function calcCost() {
                document.getElementById('total').innerText = ((document.getElementById('qty').value || 0) * (document.getElementById('cost').value || 0)).toFixed(2);
            }
        </script>
    </div>

<?php elseif ($action == 'use' && isset($_GET['id'])):
    // USE FORM
    $i = $pdo->prepare("SELECT * FROM stock WHERE id = ?");
    $i->execute([$_GET['id']]);
    $item = $i->fetch(); ?>

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

<?php elseif ($action == 'report'):
    // DAILY REPORT
    $today = date('Y-m-d');
    $records = $pdo->query("SELECT * FROM stock_logs WHERE DATE(log_date) = '$today' ORDER BY id DESC")->fetchAll(); ?>

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
                                <td data-label="Action"><span class="badge <?php echo ($r['action_type'] == 'Restocked' || $r['action_type'] == 'Created') ? 'badge-green' : 'badge-red'; ?>"><?php echo $r['action_type']; ?></span></td>
                                <td data-label="Qty"><strong><?php echo $r['quantity'] . ' ' . $r['unit']; ?></strong></td>
                                <td data-label="Cost"><?php echo ($r['total_cost'] > 0) ? '<span style="color:#ff4b4b">-' . $r['total_cost'] . '</span>' : '-'; ?></td>
                                <td data-label="User"><?php echo $r['user']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($records)) echo "<tr><td colspan='6' style='text-align:center; padding:20px;'>No activity today.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($action == 'history' && isset($_GET['id'])):
    // ITEM HISTORY
    $n = $pdo->prepare("SELECT name FROM stock WHERE id = ?");
    $n->execute([$_GET['id']]);
    $name = $n->fetchColumn();
    $logs = $pdo->prepare("SELECT * FROM stock_logs WHERE item_id = ? ORDER BY id DESC");
    $logs->execute([$_GET['id']]);
    $history = $logs->fetchAll(); ?>

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
                                <td data-label="Action"><span class="badge <?php echo ($h['action_type'] == 'Restocked' || $h['action_type'] == 'Created') ? 'badge-green' : 'badge-red'; ?>"><?php echo $h['action_type']; ?></span></td>
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

<?php else:
    // MAIN LIST (DEFAULT)
    $items = $pdo->query("SELECT * FROM stock ORDER BY id DESC")->fetchAll();
    $low_stock = $pdo->query("SELECT COUNT(*) FROM stock WHERE quantity < 10")->fetchColumn();
    $today = date('Y-m-d');
    $act_today = $pdo->query("SELECT COUNT(*) FROM stock_logs WHERE DATE(log_date) = '$today'")->fetchColumn(); ?>

    <div class="metrics-grid">
        <a href="?page=stock&action=report" style="text-decoration:none;">
            <div class="metric-card" style="border-left:4px solid #2196F3;">
                <h4>Activity Today</h4>
                <div class="value" style="color:#2196F3;"><?php echo $act_today; ?> <small>actions</small></div>
                <div class="sub-text">Click for report</div>
            </div>
        </a>
        <div class="metric-card" style="border-left:4px solid #FF9800;">
            <h4>Low Stock</h4>
            <div class="value" style="<?php echo ($low_stock > 0) ? 'color:#FF9800' : ''; ?>"><?php echo $low_stock; ?></div>
            <div class="sub-text">Items need attention</div>
        </div>
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
                                <span style="font-weight:bold; color:<?php echo ($item['quantity'] < 10) ? '#FF9800' : '#4CAF50'; ?>;">
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