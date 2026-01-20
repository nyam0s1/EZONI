<?php
/**
 * SALES & PRODUCTION VIEW - DYNAMIC THRESHOLD VERSION
 * - Update: "Low Stock" uses the specific 'min_threshold'.
 * - Added: Input field for threshold in Add/Edit forms.
 * - Added: Low Stock Filter functionality.
 */

// --- BACKEND LOGIC ---

// 1. CREATE NEW PRODUCT
if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['add_product'])) {
    $price = floatval($_POST['price']);
    // Default threshold to 10 if empty
    $threshold = !empty($_POST['min_threshold']) ? intval($_POST['min_threshold']) : 10;
    
    $stmt = $pdo->prepare("INSERT INTO produce (name, quantity, unit, category, default_price, min_threshold) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['quantity'], $_POST['unit'], $_POST['category'], $price, $threshold]);
    
    // Log creation
    $pdo->prepare("INSERT INTO sales_logs (product_name, action_type, quantity, unit_price, total_revenue, user) VALUES (?, 'Created', ?, ?, 0, ?)")
        ->execute([$_POST['name'], $_POST['quantity'], $price, $_SESSION['username']]);
    
    echo "<script>window.location.href='?page=sales';</script>";
}

// 2. EDIT PRODUCT (Admin Only)
if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['edit_product']) && $_SESSION['role'] == 'admin') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $unit = $_POST['unit'];
    $price = floatval($_POST['price']);
    $category = $_POST['category'];
    $threshold = intval($_POST['min_threshold']);
    
    $pdo->prepare("UPDATE produce SET name = ?, unit = ?, default_price = ?, category = ?, min_threshold = ? WHERE id = ?")
        ->execute([$name, $unit, $price, $category, $threshold, $id]);
        
    echo "<script>window.location.href='?page=sales';</script>";
}

// 3. HARVEST (Standard)
if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['submit_harvest'])) {
    $qty = floatval($_POST['quantity']);
    if ($qty > 0) {
        $pdo->prepare("UPDATE produce SET quantity = quantity + ? WHERE id = ?")->execute([$qty, $_POST['id']]);
        $pdo->prepare("INSERT INTO sales_logs (product_name, action_type, quantity, unit_price, total_revenue, customer_name, user) VALUES (?, 'Harvest', ?, 0, 0, '-', ?)")
            ->execute([$_POST['name'], $qty, $_SESSION['username']]);
        echo "<script>window.location.href='?page=sales';</script>";
    }
}

// 4. SELL (Standard)
if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['submit_sale'])) {
    $qty = floatval($_POST['quantity']); 
    $price = floatval($_POST['price']); 
    $total = $qty * $price;
    $customer = !empty($_POST['customer']) ? $_POST['customer'] : 'Walk-in';
    
    $stmt = $pdo->prepare("SELECT quantity FROM produce WHERE id = ?"); 
    $stmt->execute([$_POST['id']]); 
    $current_stock = $stmt->fetchColumn();

    if ($qty > 0 && $current_stock >= $qty) {
        $pdo->prepare("UPDATE produce SET quantity = quantity - ?, default_price = ? WHERE id = ?")->execute([$qty, $price, $_POST['id']]);
        $pdo->prepare("INSERT INTO sales_logs (product_name, action_type, quantity, unit_price, total_revenue, customer_name, user) VALUES (?, 'Sold', ?, ?, ?, ?, ?)")->execute([$_POST['name'], $qty, $price, $total, $customer, $_SESSION['username']]);
        echo "<script>window.location.href='?page=sales';</script>";
    } else { echo "<script>alert('Error: Not enough stock!');</script>"; }
}

// 5. DELETE
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM produce WHERE id = ?")->execute([$_GET['delete']]);
    echo "<script>window.location.href='?page=sales';</script>";
}

// --- FETCH DATA ---
$cats = $pdo->query("SELECT option_value FROM system_options WHERE option_type = 'produce_category' ORDER BY option_value ASC")->fetchAll(PDO::FETCH_COLUMN);
$units = $pdo->query("SELECT DISTINCT unit FROM produce WHERE unit != '' ORDER BY unit ASC")->fetchAll(PDO::FETCH_COLUMN);

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
?>

<?php if ($action == 'add' || ($action == 'edit' && isset($_GET['id']) && $_SESSION['role'] == 'admin')): 
    // ADD / EDIT FORM
    $is_edit = ($action == 'edit'); 
    $p = $is_edit ? $pdo->query("SELECT * FROM produce WHERE id=".$_GET['id'])->fetch() : null; ?>
    
    <div class="form-container">
        <div class="form-header"><a href="?page=sales" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a><h2><?php echo $is_edit?'Edit Product':'Add Produce'; ?></h2></div>
        <div class="card" style="text-align:left; border-top: 3px solid #2196F3;">
            <form method="POST" style="display:flex; flex-direction:column; gap:15px;">
                <?php if($is_edit): ?><input type="hidden" name="id" value="<?php echo $p['id']; ?>"><?php endif; ?>
                
                <label style="color:#aaa;">Product Name</label>
                <input type="text" name="name" value="<?php echo $is_edit?$p['name']:''; ?>" placeholder="Product Name (e.g. Milk)" required>
                
                <div style="display:flex; gap:10px;">
                    <div style="flex:1;">
                        <label style="color:#aaa;">Unit</label>
                        <input type="text" name="unit" list="prod_units" value="<?php echo $is_edit?$p['unit']:''; ?>" placeholder="e.g. Kg" required>
                        <datalist id="prod_units">
                            <?php foreach($units as $u) echo "<option value='$u'>"; ?>
                        </datalist>
                    </div>
                    <div style="flex:1;">
                        <label style="color:#aaa;">Default Price</label>
                        <input type="number" step="0.01" name="price" value="<?php echo $is_edit?$p['default_price']:''; ?>" placeholder="Price" required>
                    </div>
                </div>

                <div style="flex:1;">
                    <label style="color:#aaa;">Low Stock Warning Level</label>
                    <input type="number" name="min_threshold" value="<?php echo $is_edit ? $p['min_threshold'] : '10'; ?>" placeholder="Alert me when below..." required>
                    <small style="color:#666;">Default is 10. Change this to customize alerts.</small>
                </div>

                <label style="color:#aaa;">Category</label>
                <select name="category" required style="padding:10px; background:rgba(0,0,0,0.4); color:white; border:1px solid #555; border-radius:5px;">
                    <option value="">-- Select Category --</option>
                    <?php foreach($cats as $c): ?>
                        <option value="<?php echo $c; ?>" <?php echo ($is_edit && $p['category'] == $c) ? 'selected' : ''; ?>><?php echo $c; ?></option>
                    <?php endforeach; ?>
                </select>

                <div style="margin-top:5px;">
                    <?php if(!$is_edit): ?>
                        <label style="color:#aaa;">Starting Quantity</label>
                        <input type="number" step="0.01" name="quantity" value="0" placeholder="0">
                    <?php endif; ?>
                </div>

                <button type="submit" name="<?php echo $is_edit?'edit_product':'add_product'; ?>" style="background:#2196F3; margin-top:10px;"><?php echo $is_edit?'Update Product':'Create Product'; ?></button>
            </form>
        </div>
    </div>

<?php elseif ($action == 'harvest' && isset($_GET['id'])): 
    // HARVEST FORM (Standard)
    $p = $pdo->query("SELECT * FROM produce WHERE id=".$_GET['id'])->fetch(); ?>
    <div class="form-container">
        <div class="form-header"><a href="?page=sales" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a><h2>Record Harvest</h2></div>
        <div class="card" style="text-align:left; border-top: 3px solid #2196F3;">
            <h3 style="margin-bottom:5px;"><?php echo $p['name']; ?></h3>
            <p style="color:#aaa; margin-bottom:20px;">Current Stock: <strong><?php echo $p['quantity'].' '.$p['unit']; ?></strong></p>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                <input type="hidden" name="name" value="<?php echo $p['name']; ?>">
                <label style="display:block; margin-bottom:5px; color:#ccc;">Quantity Collected</label>
                <input type="number" step="0.01" name="quantity" placeholder="0.00" required style="margin-bottom:20px; font-size:1.2rem;">
                <button type="submit" name="submit_harvest" style="background:#2196F3; width:100%;">+ Add to Stock</button>
            </form>
        </div>
    </div>

<?php elseif ($action == 'sell' && isset($_GET['id'])): 
    // SELL FORM (Standard)
    $p = $pdo->query("SELECT * FROM produce WHERE id=".$_GET['id'])->fetch(); ?>
    <div class="form-container">
        <div class="form-header"><a href="?page=sales" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a><h2>Record Sale</h2></div>
        <div class="card" style="text-align:left; border-top: 3px solid #4CAF50;">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <h3 style="margin:0;"><?php echo $p['name']; ?></h3>
                <span class="badge badge-blue">Stock: <?php echo $p['quantity'].' '.$p['unit']; ?></span>
            </div>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                <input type="hidden" name="name" value="<?php echo $p['name']; ?>">
                <div style="display:flex; gap:15px; margin-bottom:15px;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:5px; color:#ccc;">Quantity</label>
                        <input type="number" step="0.01" name="quantity" id="qty" placeholder="0" required oninput="calcTotal()">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:5px; color:#ccc;">Price / Unit</label>
                        <input type="number" step="0.01" name="price" id="price" value="<?php echo $p['default_price']; ?>" required oninput="calcTotal()">
                    </div>
                </div>
                <input type="text" name="customer" placeholder="Customer Name (Optional)" style="margin-bottom:20px;">
                <div style="background:rgba(0,0,0,0.3); padding:15px; border-radius:5px; margin-bottom:20px; text-align:center;">
                    <span style="color:#aaa;">Total:</span> <strong style="color:#4CAF50; font-size:1.5rem;">Ksh <span id="total">0.00</span></strong>
                </div>
                <button type="submit" name="submit_sale" style="background:#4CAF50; width:100%;">$ Confirm Sale</button>
            </form>
        </div>
        <script>function calcTotal(){ document.getElementById('total').innerText = ((document.getElementById('qty').value||0)*(document.getElementById('price').value||0)).toFixed(2); }</script>
    </div>

<?php elseif ($action == 'revenue_report'): 
    // REVENUE REPORT (Standard)
    $today = date('Y-m-d'); 
    $sales = $pdo->prepare("SELECT * FROM sales_logs WHERE action_type = 'Sold' AND DATE(log_date) = ? ORDER BY id DESC"); 
    $sales->execute([$today]); 
    $records = $sales->fetchAll(); 
    $day_total = 0; foreach($records as $r) $day_total += $r['total_revenue']; ?>
    <div class="form-container" style="max-width:800px;">
        <div class="form-header"><a href="?page=sales" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a><h2>Sales Report: Today</h2></div>
        <div class="card" style="text-align:left;">
            <div style="margin-bottom:20px; text-align:center;">
                <h1 style="color:#4CAF50; font-size:2.5rem; margin:0;">Ksh <?php echo number_format($day_total, 2); ?></h1>
                <p style="color:#aaa;">Total Revenue</p>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Date</th><th>Item</th><th>Qty</th><th>Price</th><th>Total</th><th>Customer</th></tr></thead>
                    <tbody>
                        <?php foreach ($records as $r): ?>
                        <tr><td data-label="Date" style="color:#888;"><?php echo date('d M Y, H:i', strtotime($r['log_date'])); ?></td><td data-label="Item"><?php echo $r['product_name']; ?></td><td data-label="Qty"><?php echo $r['quantity']; ?></td><td data-label="Price"><?php echo $r['unit_price']; ?></td><td data-label="Total" style="color:#4CAF50; font-weight:bold;"><?php echo $r['total_revenue']; ?></td><td data-label="Customer"><?php echo $r['customer_name']; ?></td></tr>
                        <?php endforeach; ?>
                        <?php if(empty($records)) echo "<tr><td colspan='6' style='text-align:center; padding:20px;'>No sales yet today.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($action == 'harvest_report'): 
    // HARVEST REPORT (Standard)
    $today = date('Y-m-d'); 
    $harvests = $pdo->prepare("SELECT * FROM sales_logs WHERE action_type = 'Harvest' AND DATE(log_date) = ? ORDER BY id DESC"); 
    $harvests->execute([$today]); 
    $records = $harvests->fetchAll(); 
    $day_qty = 0; foreach($records as $r) $day_qty += $r['quantity']; ?>
    <div class="form-container" style="max-width:800px;">
        <div class="form-header"><a href="?page=sales" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a><h2>Production Report: Today</h2></div>
        <div class="card" style="text-align:left; border-top: 3px solid #2196F3;">
            <div style="margin-bottom:20px; text-align:center;">
                <h1 style="color:#2196F3; font-size:2.5rem; margin:0;"><?php echo $day_qty; ?> <span style="font-size:1rem; color:#aaa;">units</span></h1>
                <p style="color:#aaa;">Total Produced</p>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Date</th><th>Item</th><th>Quantity</th><th>User</th></tr></thead>
                    <tbody>
                        <?php foreach ($records as $r): ?>
                        <tr><td data-label="Date" style="color:#888;"><?php echo date('d M Y, H:i', strtotime($r['log_date'])); ?></td><td data-label="Item"><?php echo $r['product_name']; ?></td><td data-label="Quantity" style="font-weight:bold; color:#2196F3;"><?php echo $r['quantity']; ?></td><td data-label="User"><?php echo $r['user']; ?></td></tr>
                        <?php endforeach; ?>
                        <?php if(empty($records)) echo "<tr><td colspan='4' style='text-align:center; padding:20px;'>No harvest recorded today.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($action == 'history' && isset($_GET['id'])): 
    // HISTORY VIEW (Standard)
    $n = $pdo->prepare("SELECT name FROM produce WHERE id = ?"); $n->execute([$_GET['id']]); $name = $n->fetchColumn();
    $logs = $pdo->prepare("SELECT * FROM sales_logs WHERE product_name = ? ORDER BY id DESC"); $logs->execute([$name]); $history = $logs->fetchAll(); ?>
    <div class="form-container" style="max-width:800px;">
        <div class="form-header"><a href="?page=sales" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a><h2>History: <?php echo $name; ?></h2></div>
        <div class="card" style="text-align:left;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Date</th><th>Action</th><th>Qty</th><th>Price</th><th>Total</th><th>Customer</th></tr></thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                        <tr><td data-label="Date" style="color:#888; font-size:0.85rem;"><?php echo date('d M Y, H:i', strtotime($h['log_date'])); ?></td><td data-label="Action"><span class="badge <?php echo ($h['action_type']=='Sold')?'badge-green':(($h['action_type']=='Harvest')?'badge-blue':''); ?>"><?php echo $h['action_type']; ?></span></td><td data-label="Qty"><strong><?php echo $h['quantity']; ?></strong></td><td data-label="Price"><?php echo ($h['unit_price']>0)?$h['unit_price']:'-'; ?></td><td data-label="Total"><?php echo ($h['total_revenue']>0)?'<span style="color:#4CAF50">'.$h['total_revenue'].'</span>':'-'; ?></td><td data-label="Customer" style="color:#ccc;"><?php echo ($h['customer_name'] == '-') ? '' : $h['customer_name']; ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php else: 
    // MAIN LIST - UPDATED FOR DYNAMIC ALERTS
    $low_prod = $pdo->query("SELECT COUNT(*) FROM produce WHERE quantity <= min_threshold")->fetchColumn();
    
    $today = date('Y-m-d'); 
    $revenue = $pdo->prepare("SELECT SUM(total_revenue) FROM sales_logs WHERE DATE(log_date) = ?"); $revenue->execute([$today]); $rev_today = $revenue->fetchColumn() ?: 0;
    $harvest = $pdo->prepare("SELECT SUM(quantity) FROM sales_logs WHERE action_type='Harvest' AND DATE(log_date) = ?"); $harvest->execute([$today]); $har_today = $harvest->fetchColumn() ?: 0;

    // Filter Logic
    $filter_low = isset($_GET['filter']) && $_GET['filter'] == 'low';
    $sql = "SELECT * FROM produce";
    if ($filter_low) $sql .= " WHERE quantity <= min_threshold";
    $sql .= " ORDER BY id DESC";
    $products = $pdo->query($sql)->fetchAll(); ?>
    
    <div class="metrics-grid">
        <a href="?page=sales&action=revenue_report" style="text-decoration:none;">
            <div class="metric-card" style="border-left:4px solid #4CAF50;">
                <h4>Revenue Today</h4>
                <div class="value" style="color:#4CAF50;">Ksh <?php echo number_format($rev_today); ?></div>
                <div class="sub-text">Click for details</div>
            </div>
        </a>
        
        <a href="?page=sales&filter=low" style="text-decoration:none;">
            <div class="metric-card" style="border-left:4px solid #FF9800; cursor:pointer;">
                <h4>Low Stock</h4>
                <div class="value" style="<?php echo ($low_prod > 0) ? 'color:#FF9800' : ''; ?>"><?php echo $low_prod; ?></div>
                <div class="sub-text">Items needing attention</div>
            </div>
        </a>

        <a href="?page=sales&action=harvest_report" style="text-decoration:none;">
            <div class="metric-card" style="border-left:4px solid #2196F3;">
                <h4>Harvest Today</h4>
                <div class="value" style="color:#2196F3;"><?php echo $har_today; ?> <small>units</small></div>
                <div class="sub-text">Click for details</div>
            </div>
        </a>
    </div>

    <div class="list-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>
            💰 Produce & Sales
            <?php if ($filter_low): ?>
                <a href="?page=sales" style="font-size:0.8rem; color:#2196F3; text-decoration:none; margin-left:10px;">(Show All)</a>
            <?php endif; ?>
        </h2>
        <a href="?page=sales&action=add" style="background:#4CAF50; color:white; padding:8px 15px; text-decoration:none; border-radius:5px;">+ New Product</a>
    </div>
    
    <div class="card">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Stock</th><th>Std Price</th><th>Category</th><th style="text-align:right;">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td data-label="Name"><?php echo $p['name']; ?></td>
                        <td data-label="Stock">
                            <div style="text-align:right;">
                                <span style="font-weight:bold; font-size:1.1rem; color:<?php echo ($p['quantity'] <= $p['min_threshold']) ? '#FF9800' : '#fff'; ?>;">
                                    <?php echo $p['quantity']; ?>
                                </span> 
                                <small style="color:#888;"><?php echo $p['unit']; ?></small>
                            </div>
                        </td>
                        <td data-label="Std Price"><?php echo ($p['default_price'] > 0) ? 'Ksh '.$p['default_price'] : '-'; ?></td>
                        <td data-label="Category"><span class="badge" style="background:#444;"><?php echo $p['category']; ?></span></td>
                        <td data-label="Actions" style="text-align:right;">
                            <?php if($_SESSION['role'] == 'admin'): ?>
                                <a href="?page=sales&action=edit&id=<?php echo $p['id']; ?>" class="badge badge-blue" style="text-decoration:none; margin-right:5px;">Edit ✏️</a>
                            <?php endif; ?>
                            <a href="?page=sales&action=harvest&id=<?php echo $p['id']; ?>" class="badge badge-blue" style="text-decoration:none; margin-right:5px;">Harvest 🌾</a>
                            <a href="?page=sales&action=sell&id=<?php echo $p['id']; ?>" class="badge badge-green" style="text-decoration:none; margin-right:10px;">Sell 💰</a>
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