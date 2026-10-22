<?php

/**
 * ANIMALS VIEW - FINAL
 * --------------------
 * 1. Supports Batch Quantity (for Flocks).
 * 2. Manage -> Sell records to Sales Reports.
 * 3. Includes Validation (Error if selling > available).
 * 4. ALL Columns included (Breed, Mother, DOB, etc).
 */

// =========================================================
// SECTION 1: BACKEND LOGIC
// =========================================================

// 1.1 REGISTER ANIMAL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_animal'])) {
    try {
        $cost = !empty($_POST['buying_price']) ? floatval($_POST['buying_price']) : 0.00;
        $dob = !empty($_POST['dob']) ? $_POST['dob'] : NULL;
        $qty = !empty($_POST['quantity']) ? intval($_POST['quantity']) : 1;

        $stmt = $pdo->prepare("INSERT INTO animals (tag_id, name, species, gender, breed, buying_price, dob, mother_id, quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['tag_id'],
            $_POST['name'],
            $_POST['species'],
            $_POST['gender'],
            $_POST['breed'],
            $cost,
            $dob,
            $_POST['mother_id'] ?: NULL,
            $qty
        ]);

        $pdo->prepare("INSERT INTO animal_logs (animal_tag, action_type, details, performed_by) VALUES (?, 'Registered', ?, ?)")
            ->execute([$_POST['tag_id'], "Initial Qty: $qty | Value: KES $cost", $_SESSION['username']]);

        echo "<script>window.location.href='?page=animals';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Error: Tag ID already exists');</script>";
    }
}

// 1.2 EDIT ANIMAL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_animal'])) {
    $mother_id = $_POST['mother_id'] ?: NULL;
    $cost = floatval($_POST['buying_price']);
    $dob = !empty($_POST['dob']) ? $_POST['dob'] : NULL;
    $qty = intval($_POST['quantity']);

    $pdo->prepare("UPDATE animals SET tag_id = ?, name = ?, species = ?, breed = ?, buying_price = ?, dob = ?, mother_id = ?, quantity = ? WHERE id = ?")
        ->execute([
            $_POST['tag_id'],
            $_POST['name'],
            $_POST['species'],
            $_POST['breed'],
            $cost,
            $dob,
            $mother_id,
            $qty,
            $_POST['id']
        ]);
    echo "<script>window.location.href='?page=animals';</script>";
}

// 1.3 MANAGE / SELL ANIMAL (With Validation Error)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['manage_animal'])) {
    $id = $_POST['id'];
    $tag = $_POST['tag'];
    $action = $_POST['action_type'];

    if ($action == 'record_sale') {
        // --- SALE LOGIC ---
        $qty_sold = intval($_POST['qty_sold']);
        $sale_price = floatval($_POST['sale_price']);
        $customer = $_POST['customer_name'] ?? 'Walk-in';

        // VALIDATION CHECK: Get current quantity first
        $check = $pdo->prepare("SELECT quantity FROM animals WHERE id = ?");
        $check->execute([$id]);
        $available_qty = $check->fetchColumn();

        // If trying to sell more than available -> ERROR
        if ($qty_sold > $available_qty) {
            echo "<script>alert('Error: You cannot sell $qty_sold! Only $available_qty available.'); window.location.href='?page=animals';</script>";
            exit;
        }

        // 1. Reduce Quantity
        $pdo->prepare("UPDATE animals SET quantity = quantity - ? WHERE id = ?")->execute([$qty_sold, $id]);

        // 2. If 0 remain, mark as "Sold"
        $new_qty = $available_qty - $qty_sold;
        if ($new_qty <= 0) {
            $pdo->prepare("UPDATE animals SET status = 'Sold' WHERE id = ?")->execute([$id]);
        }

        // 3. Record in SALES LOGS
        $pdo->prepare("INSERT INTO sales_logs (product_name, quantity, unit, total_revenue, customer_name, action_type, performed_by) VALUES (?, ?, 'Heads', ?, ?, 'Sold', ?)")
            ->execute(["$tag (Livestock)", $qty_sold, $sale_price, $customer, $_SESSION['username']]);

        // 4. Record in ANIMAL HISTORY
        $pdo->prepare("INSERT INTO animal_logs (animal_tag, action_type, details, performed_by) VALUES (?, 'Sold', ?, ?)")
            ->execute([$tag, "Sold $qty_sold for KES $sale_price", $_SESSION['username']]);
    } else {
        // --- STATUS CHANGE ---
        $status = $_POST['status'];
        $pdo->prepare("UPDATE animals SET status = ? WHERE id = ?")->execute([$status, $id]);
        $pdo->prepare("INSERT INTO animal_logs (animal_tag, action_type, details, performed_by) VALUES (?, 'Status Change', ?, ?)")
            ->execute([$tag, $status, $_SESSION['username']]);
    }

    echo "<script>window.location.href='?page=animals';</script>";
}

// 1.4 DELETE
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM animals WHERE id = ?")->execute([$_GET['delete']]);
    echo "<script>window.location.href='?page=animals';</script>";
}

$species_list = $pdo->query("SELECT option_value FROM system_options WHERE option_type = 'animal_species' ORDER BY option_value ASC")->fetchAll(PDO::FETCH_COLUMN);
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
?>

<?php if ($action == 'add'):
    $mothers = $pdo->query("SELECT id, name, species FROM animals WHERE gender='Female'")->fetchAll();
    $pre_species = isset($_GET['pre_species']) ? trim(urldecode($_GET['pre_species'])) : '';
?>
    <div class="form-container">
        <div class="form-header"><a href="?page=animals" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h2>Register Animal</h2>
        </div>
        <div class="card" style="text-align:left;">
            <form method="POST" style="display:flex; flex-direction:column; gap:15px;">
                <input type="text" name="tag_id" placeholder="Tag ID / Batch Name (e.g. FLOCK-A)" required>
                <input type="text" name="name" placeholder="Name / Description" required>

                <div style="display:flex; gap:10px;">
                    <select name="species" id="species" style="flex:1; padding:10px; background:rgba(0,0,0,0.4); color:white; border:1px solid #555; border-radius:5px;">
                        <?php foreach ($species_list as $s): ?>
                            <?php $selected = (strcasecmp(trim($s), $pre_species) == 0) ? 'selected' : ''; ?>
                            <option value='<?php echo $s; ?>' <?php echo $selected; ?>><?php echo $s; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="flex:1;">
                        <input type="number" name="quantity" value="1" placeholder="Qty" style="background:rgba(0,0,0,0.4); color:white; border:1px solid #555;" required>
                    </div>
                </div>

                <select name="gender" style="width:100%; padding:10px; background:rgba(0,0,0,0.4); color:white; border:1px solid #555; border-radius:5px;">
                    <option value="Female">Female</option>
                    <option value="Male">Male</option>
                    <option value="Mixed">Mixed (For Flocks)</option>
                </select>

                <div style="display:flex; gap:10px;">
                    <input type="text" name="breed" placeholder="Breed" style="flex:1; background:rgba(0,0,0,0.4); color:white; border:1px solid #555;">
                    <input type="number" step="0.01" name="buying_price" placeholder="Total Cost (KES)" style="flex:1; background:rgba(0,0,0,0.4); color:white; border:1px solid #555;">
                </div>

                <select name="mother_id" id="mother_id" style="padding:10px; background:rgba(0,0,0,0.4); color:white; border:1px solid #555; border-radius:5px;" onchange="toggleDob()">
                    <option value="">-- Select Mother (If Born Here) --</option>
                    <?php foreach ($mothers as $m) echo "<option value='{$m['id']}' data-species='{$m['species']}'>{$m['name']} ({$m['species']})</option>"; ?>
                </select>

                <div id="dob_container" style="display:none; animation: fadeIn 0.5s;">
                    <label style="color:#4CAF50; font-size:0.9rem;">Date of Birth (Pop-up)</label>
                    <input type="date" name="dob" id="dob_field" style="background:rgba(0,0,0,0.4); color:white; border:1px solid #4CAF50;">
                </div>

                <button type="submit" name="add_animal" style="background:#4CAF50;">Register</button>
            </form>
        </div>
        <script>
            function toggleDob() {
                const motherSelect = document.getElementById('mother_id');
                const dobContainer = document.getElementById('dob_container');
                if (motherSelect.value !== "") {
                    dobContainer.style.display = 'block';
                } else {
                    dobContainer.style.display = 'none';
                }
            }
            document.addEventListener("DOMContentLoaded", function() {
                const speciesSelect = document.getElementById('species');
                const motherSelect = document.getElementById('mother_id');
                const allMothers = Array.from(motherSelect.options);

                function filterMothers() {
                    const selectedSpecies = speciesSelect.value;
                    motherSelect.innerHTML = '';
                    motherSelect.appendChild(allMothers[0]);
                    for (let i = 1; i < allMothers.length; i++) {
                        const option = allMothers[i];
                        if (option.getAttribute('data-species') === selectedSpecies) {
                            motherSelect.appendChild(option);
                        }
                    }
                }
                speciesSelect.addEventListener('change', filterMothers);
                filterMothers();
            });
        </script>
        <style>
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        </style>
    </div>

<?php elseif ($action == 'edit' && isset($_GET['id'])):
    $a = $pdo->prepare("SELECT * FROM animals WHERE id = ?");
    $a->execute([$_GET['id']]);
    $animal = $a->fetch();
    $mothers = $pdo->query("SELECT id, name FROM animals WHERE gender='Female' AND id != {$_GET['id']}")->fetchAll();
?>
    <div class="form-container">
        <div class="form-header"><a href="?page=animals" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h2>Edit Details</h2>
        </div>
        <div class="card" style="text-align:left; border-top: 3px solid #2196F3;">
            <form method="POST" style="display:flex; flex-direction:column; gap:15px;">
                <input type="hidden" name="id" value="<?php echo $animal['id']; ?>">
                <label style="color:#aaa;">Tag ID</label>
                <input type="text" name="tag_id" value="<?php echo $animal['tag_id']; ?>" required>
                <label style="color:#aaa;">Name</label>
                <input type="text" name="name" value="<?php echo $animal['name']; ?>" required>

                <div style="display:flex; gap:10px;">
                    <div style="flex:1;"><label style="color:#aaa;">Species</label><select name="species" style="width:100%; padding:10px; background:rgba(0,0,0,0.4); color:white; border:1px solid #555; border-radius:5px;"><?php foreach ($species_list as $s): ?><option value="<?php echo $s; ?>" <?php echo ($animal['species'] == $s) ? 'selected' : ''; ?>><?php echo $s; ?></option><?php endforeach; ?></select></div>
                    <div style="flex:1;"><label style="color:#aaa;">Quantity</label><input type="number" name="quantity" value="<?php echo $animal['quantity']; ?>"></div>
                </div>

                <div style="display:flex; gap:10px;">
                    <div style="flex:1;"><label style="color:#aaa;">Breed</label><input type="text" name="breed" value="<?php echo $animal['breed']; ?>"></div>
                    <div style="flex:1;"><label style="color:#aaa;">Cost</label><input type="number" step="0.01" name="buying_price" value="<?php echo $animal['buying_price']; ?>"></div>
                </div>

                <label style="color:#aaa;">Mother</label>
                <select name="mother_id" style="width:100%; padding:10px; background:rgba(0,0,0,0.4); color:white; border:1px solid #555; border-radius:5px;">
                    <option value="">-- None --</option>
                    <?php foreach ($mothers as $m): ?>
                        <option value="<?php echo $m['id']; ?>" <?php echo ($animal['mother_id'] == $m['id']) ? 'selected' : ''; ?>><?php echo $m['name']; ?></option>
                    <?php endforeach; ?>
                </select>

                <label style="color:#aaa;">Date of Birth</label>
                <input type="date" name="dob" value="<?php echo $animal['dob']; ?>" style="background:rgba(0,0,0,0.4); color:white; border:1px solid #555;">

                <button type="submit" name="edit_animal" style="background:#2196F3; margin-top:10px;">Update Details</button>
            </form>
        </div>
    </div>

<?php elseif ($action == 'manage' && isset($_GET['id'])):
    $a = $pdo->prepare("SELECT * FROM animals WHERE id = ?");
    $a->execute([$_GET['id']]);
    $cow = $a->fetch();
?>
    <div class="form-container">
        <div class="form-header"><a href="?page=animals" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h2>Manage: <?php echo $cow['name']; ?></h2>
        </div>
        <div class="card" style="text-align:left; border-top:3px solid #2196F3;">
            <h3 style="margin-bottom:5px;">Status: <span style="color:#4CAF50;"><?php echo $cow['status']; ?></span></h3>
            <p style="color:#aaa; margin-bottom:15px;">Current Quantity: <strong><?php echo $cow['quantity']; ?></strong></p>

            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $cow['id']; ?>">
                <input type="hidden" name="tag" value="<?php echo $cow['tag_id']; ?>">

                <div style="margin-bottom:20px; background:#2c3e50; padding:10px; border-radius:8px; display:flex; gap:10px;">
                    <label style="flex:1; cursor:pointer;">
                        <input type="radio" name="action_type" value="status_change" checked onchange="toggleManageMode()" style="margin-right:5px;">
                        🩺 Health/Status
                    </label>
                    <label style="flex:1; cursor:pointer;">
                        <input type="radio" name="action_type" value="record_sale" onchange="toggleManageMode()" style="margin-right:5px;">
                        💰 Sell Animal(s)
                    </label>
                </div>

                <div id="status_section">
                    <label style="display:block; margin-bottom:5px; color:#aaa;">Update Condition:</label>
                    <select name="status" style="width:100%; padding:10px; background:rgba(0,0,0,0.4); color:white; border:1px solid #555; border-radius:5px; margin-bottom:15px;">
                        <option value="Active">Active / Healthy</option>
                        <option value="Sick">Sick / Isolated</option>
                        <option value="Deceased">Deceased</option>
                    </select>
                </div>

                <div id="sale_section" style="display:none;">
                    <label style="display:block; margin-bottom:5px; color:#aaa;">Quantity to Sell:</label>
                    <input type="number" name="qty_sold" value="1" min="1" max="<?php echo $cow['quantity']; ?>" placeholder="Qty" style="width:100%; margin-bottom:10px;">

                    <label style="display:block; margin-bottom:5px; color:#aaa;">Total Sale Price (KES):</label>
                    <input type="number" step="0.01" name="sale_price" placeholder="Total Amount Recieved" style="width:100%; margin-bottom:10px;">

                    <label style="display:block; margin-bottom:5px; color:#aaa;">Customer (Optional):</label>
                    <input type="text" name="customer_name" placeholder="Buyer Name" style="width:100%; margin-bottom:15px;">
                </div>

                <button type="submit" name="manage_animal" style="background:#2196F3; width:100%;">Confirm Update</button>
            </form>
        </div>

        <script>
            function toggleManageMode() {
                const isSale = document.querySelector('input[name="action_type"][value="record_sale"]').checked;
                document.getElementById('status_section').style.display = isSale ? 'none' : 'block';
                document.getElementById('sale_section').style.display = isSale ? 'block' : 'none';

                // Toggle 'Required' for price to avoid form errors
                document.querySelector('input[name="sale_price"]').required = isSale;
            }
        </script>
    </div>

<?php elseif ($action == 'history' && isset($_GET['id'])):
    $t = $pdo->prepare("SELECT tag_id, name FROM animals WHERE id = ?");
    $t->execute([$_GET['id']]);
    $info = $t->fetch();
    $logs = $pdo->prepare("SELECT * FROM animal_logs WHERE animal_tag = ? ORDER BY id DESC");
    $logs->execute([$info['tag_id']]);
    $history = $logs->fetchAll();
?>
    <div class="form-container" style="max-width:800px;">
        <div class="form-header"><a href="?page=animals" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h2>History: <?php echo $info['name']; ?></h2>
        </div>
        <div class="card" style="text-align:left;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Details</th>
                            <th>User</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td data-label="Action"><span class="badge badge-blue"><?php echo $h['action_type']; ?></span></td>
                                <td data-label="Details"><?php echo $h['details'] ? $h['details'] : '-'; ?></td>
                                <td data-label="User"><?php echo $h['performed_by']; ?></td>
                                <td data-label="Date" style="color:#888;"><?php echo date('d M Y, H:i', strtotime($h['log_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($history) == 0) echo "<tr><td colspan='4' style='text-align:center; color:#666;'>No history yet.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php else:
    $animals = $pdo->query("SELECT t1.*, t2.name AS mother_name FROM animals t1 LEFT JOIN animals t2 ON t1.mother_id = t2.id ORDER BY t1.id DESC")->fetchAll();
?>
    <div class="list-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>🐄 Livestock</h2>
        <a href="?page=animals&action=add" style="background:#4CAF50; color:white; padding:8px 15px; text-decoration:none; border-radius:5px;">+ Add Animal</a>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tag</th>
                        <th>Name</th>
                        <th>Qty</th>
                        <th>Breed</th>
                        <th>DOB</th>
                        <th>Mother</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($animals as $a): ?>
                        <tr>
                            <td data-label="Tag"><?php echo htmlspecialchars($a['tag_id']); ?></td>
                            <td data-label="Name"><?php echo htmlspecialchars($a['name']); ?></td>

                            <td data-label="Qty">
                                <span style="font-weight:bold; color:<?php echo ($a['quantity'] > 1) ? '#2196F3' : '#aaa'; ?>">
                                    <?php echo $a['quantity']; ?>
                                </span>
                            </td>

                            <td data-label="Breed" style="color:#ccc;"><?php echo !empty($a['breed']) ? htmlspecialchars($a['breed']) : '-'; ?></td>
                            <td data-label="DOB" style="color:#aaa;"><?php echo ($a['dob']) ? date('d M Y', strtotime($a['dob'])) : '-'; ?></td>
                            <td data-label="Mother" style="color:#aaa;"><?php echo $a['mother_name'] ? htmlspecialchars($a['mother_name']) : '-'; ?></td>
                            <td data-label="Status"><span class="<?php echo ($a['status'] == 'Active') ? 'badge badge-green' : 'badge badge-red'; ?>"><?php echo $a['status']; ?></span></td>

                            <td data-label="Actions" style="text-align:right;">
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <a href="?page=animals&action=edit&id=<?php echo $a['id']; ?>" class="badge badge-blue" style="text-decoration:none; margin-right:5px;" title="Edit Details"><i class="fas fa-pencil-alt"></i></a>
                                <?php endif; ?>
                                <a href="?page=animals&action=manage&id=<?php echo $a['id']; ?>" style="color:#2196F3; margin-right:10px;" title="Update Status/Sell"><i class="fas fa-edit"></i></a>
                                <a href="?page=animals&action=history&id=<?php echo $a['id']; ?>" style="color:#FF9800; margin-right:10px;" title="History"><i class="fas fa-history"></i></a>
                                <a href="?page=animals&delete=<?php echo $a['id']; ?>" onclick="return confirm('Delete?')" style="color:#ff4b4b;"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
