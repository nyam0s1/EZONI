<?php

/**
 * ANIMALS VIEW - UPDATED
 * - Feature: 'Breed' field included.
 * - Feature: 'Buying Price' (Cost) added to Registration, Edit, and Table.
 */

// --- BACKEND LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_animal'])) {
    try {
        // NEW: Capture the cost (default to 0 if empty)
        $cost = !empty($_POST['buying_price']) ? floatval($_POST['buying_price']) : 0.00;

        // FIXED: Added 'buying_price' to INSERT query
        $stmt = $pdo->prepare("INSERT INTO animals (tag_id, name, species, gender, breed, buying_price, mother_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['tag_id'],
            $_POST['name'],
            $_POST['species'],
            $_POST['gender'],
            $_POST['breed'],
            $cost, // <--- New Value
            $_POST['mother_id'] ?: NULL
        ]);

        // Log includes the cost now
        $pdo->prepare("INSERT INTO animal_logs (animal_tag, action_type, details, user) VALUES (?, 'Registered', ?, ?)")
            ->execute([$_POST['tag_id'], "Initial Value: KES $cost", $_SESSION['username']]);

        echo "<script>window.location.href='?page=animals';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Error: Tag ID already exists');</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_animal'])) {
    $mother_id = $_POST['mother_id'] ?: NULL;
    $cost = floatval($_POST['buying_price']);

    // FIXED: Added 'buying_price' to UPDATE query
    $pdo->prepare("UPDATE animals SET tag_id = ?, name = ?, species = ?, breed = ?, buying_price = ?, mother_id = ? WHERE id = ?")
        ->execute([
            $_POST['tag_id'],
            $_POST['name'],
            $_POST['species'],
            $_POST['breed'],
            $cost, // <--- New Value
            $mother_id,
            $_POST['id']
        ]);
    echo "<script>window.location.href='?page=animals';</script>";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['manage_animal'])) {
    $status = $_POST['status'];
    $pdo->prepare("UPDATE animals SET status = ? WHERE id = ?")->execute([$status, $_POST['id']]);
    $pdo->prepare("INSERT INTO animal_logs (animal_tag, action_type, details, user) VALUES (?, 'Status Change', ?, ?)")->execute([$_POST['tag'], $status, $_SESSION['username']]);
    echo "<script>window.location.href='?page=animals';</script>";
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM animals WHERE id = ?")->execute([$_GET['delete']]);
    echo "<script>window.location.href='?page=animals';</script>";
}

// --- FETCH SETTINGS ---
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
                <input type="text" name="tag_id" placeholder="Tag ID (e.g. COW-01)" required>
                <input type="text" name="name" placeholder="Name" required>

                <div style="display:flex; gap:10px;">
                    <select name="species" id="species" style="flex:1; padding:10px; background:rgba(0,0,0,0.4); color:white; border:1px solid #555; border-radius:5px;">
                        <?php foreach ($species_list as $s): ?>
                            <?php $selected = (strcasecmp(trim($s), $pre_species) == 0) ? 'selected' : ''; ?>
                            <option value='<?php echo $s; ?>' <?php echo $selected; ?>><?php echo $s; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="gender" style="flex:1; padding:10px; background:rgba(0,0,0,0.4); color:white; border:1px solid #555; border-radius:5px;">
                        <option value="Female">Female</option>
                        <option value="Male">Male</option>
                    </select>
                </div>

                <div style="display:flex; gap:10px;">
                    <input type="text" name="breed" placeholder="Breed (e.g. Friesian)" style="flex:1; background:rgba(0,0,0,0.4); color:white; border:1px solid #555;">
                    <input type="number" step="0.01" name="buying_price" placeholder="Cost / Value (KES)" style="flex:1; background:rgba(0,0,0,0.4); color:white; border:1px solid #555;">
                </div>

                <select name="mother_id" id="mother_id" style="padding:10px; background:rgba(0,0,0,0.4); color:white; border:1px solid #555; border-radius:5px;">
                    <option value="">-- Mother (Optional) --</option>
                    <?php foreach ($mothers as $m) echo "<option value='{$m['id']}' data-species='{$m['species']}'>{$m['name']} ({$m['species']})</option>"; ?>
                </select>
                <button type="submit" name="add_animal" style="background:#4CAF50;">Register</button>
            </form>
        </div>
        <script>
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
                    <div style="flex:1;"><label style="color:#aaa;">Gender (Fixed)</label><input type="text" value="<?php echo $animal['gender']; ?>" disabled style="background:#333; color:#888; border:1px solid #444;"></div>
                </div>

                <div style="display:flex; gap:10px;">
                    <div style="flex:1;">
                        <label style="color:#aaa;">Breed</label>
                        <input type="text" name="breed" value="<?php echo $animal['breed']; ?>">
                    </div>
                    <div style="flex:1;">
                        <label style="color:#aaa;">Acquisition Cost (KES)</label>
                        <input type="number" step="0.01" name="buying_price" value="<?php echo $animal['buying_price']; ?>">
                    </div>
                </div>

                <label style="color:#aaa;">Mother</label>
                <select name="mother_id" style="width:100%; padding:10px; background:rgba(0,0,0,0.4); color:white; border:1px solid #555; border-radius:5px;">
                    <option value="">-- None --</option>
                    <?php foreach ($mothers as $m): ?>
                        <option value="<?php echo $m['id']; ?>" <?php echo ($animal['mother_id'] == $m['id']) ? 'selected' : ''; ?>><?php echo $m['name']; ?></option>
                    <?php endforeach; ?>
                </select>

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
            <h3 style="margin-bottom:10px;">Status: <span style="color:#4CAF50;"><?php echo $cow['status']; ?></span></h3>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $cow['id']; ?>">
                <input type="hidden" name="tag" value="<?php echo $cow['tag_id']; ?>">
                <label style="display:block; margin-bottom:5px; color:#aaa;">Update Status:</label>
                <select name="status" style="width:100%; padding:10px; background:rgba(0,0,0,0.4); color:white; border:1px solid #555; border-radius:5px; margin-bottom:15px;">
                    <option value="Active">Active</option>
                    <option value="Sold">Sold</option>
                    <option value="Deceased">Deceased</option>
                    <option value="Sick">Sick / Isolated</option>
                </select>
                <button type="submit" name="manage_animal" style="background:#2196F3; width:100%;">Update Status</button>
            </form>
        </div>
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
                                <td data-label="User"><?php echo $h['user']; ?></td>
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
                        <th>Species</th>
                        <th>Breed</th>
                        <th>Cost</th>
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
                            <td data-label="Species"><?php echo htmlspecialchars($a['species']); ?></td>
                            <td data-label="Breed" style="color:#ccc;"><?php echo !empty($a['breed']) ? htmlspecialchars($a['breed']) : '-'; ?></td>

                            <td data-label="Cost" style="color:#4CAF50; font-weight:bold;">
                                <?php echo ($a['buying_price'] > 0) ? number_format($a['buying_price']) : '-'; ?>
                            </td>

                            <td data-label="Mother" style="color:#aaa;"><?php echo $a['mother_name'] ? htmlspecialchars($a['mother_name']) : '-'; ?></td>
                            <td data-label="Status"><span class="<?php echo ($a['status'] == 'Active') ? 'badge badge-green' : 'badge badge-red'; ?>"><?php echo $a['status']; ?></span></td>

                            <td data-label="Actions" style="text-align:right;">
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <a href="?page=animals&action=edit&id=<?php echo $a['id']; ?>" class="badge badge-blue" style="text-decoration:none; margin-right:5px;" title="Edit Details"><i class="fas fa-pencil-alt"></i></a>
                                <?php endif; ?>
                                <a href="?page=animals&action=manage&id=<?php echo $a['id']; ?>" style="color:#2196F3; margin-right:10px;" title="Update Status"><i class="fas fa-edit"></i></a>
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