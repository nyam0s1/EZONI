<?php
/**
 * SETTINGS VIEW - HEADER BUTTON VERSION
 * - Feature: "Add" buttons are now top-right (Professional Style).
 * - Feature: Dedicated Add Pages for clean mobile input.
 * - Logic: Cascading updates for Species renaming.
 */

// --- BACKEND LOGIC ---

// 1. ADD NEW SPECIES (From Dedicated Page)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_species'])) {
    $type = 'animal_species';
    $val = trim($_POST['option_value']);
    $limit = intval($_POST['limit']);
    
    $chk = $pdo->prepare("SELECT id FROM system_options WHERE option_type = ? AND option_value = ?");
    $chk->execute([$type, $val]);
    
    if ($chk->rowCount() == 0 && !empty($val)) {
        $pdo->prepare("INSERT INTO system_options (option_type, option_value) VALUES (?, ?)")->execute([$type, $val]);
        $pdo->prepare("INSERT INTO species_limits (species, min_threshold) VALUES (?, ?)")->execute([$val, $limit]);
    }
    echo "<script>window.location.href='?page=settings&section=livestock';</script>";
}

// 2. DELETE SPECIES
if (isset($_GET['delete_species'])) {
    $species = $_GET['delete_species'];
    $pdo->prepare("DELETE FROM system_options WHERE option_type='animal_species' AND option_value = ?")->execute([$species]);
    $pdo->prepare("DELETE FROM species_limits WHERE species = ?")->execute([$species]);
    echo "<script>window.location.href='?page=settings&section=livestock';</script>";
}

// 3. EDIT SPECIES
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_species_data'])) {
    $old_name = $_POST['original_name'];
    $new_name = trim($_POST['species_name']);
    $new_limit = intval($_POST['min_threshold']);

    $pdo->prepare("UPDATE species_limits SET species = ?, min_threshold = ? WHERE species = ?")->execute([$new_name, $new_limit, $old_name]);
    $pdo->prepare("UPDATE system_options SET option_value = ? WHERE option_type='animal_species' AND option_value = ?")->execute([$new_name, $old_name]);

    if ($old_name !== $new_name) {
        $pdo->prepare("UPDATE animals SET species = ? WHERE species = ?")->execute([$new_name, $old_name]);
    }
    echo "<script>window.location.href='?page=settings&section=livestock';</script>";
}

// 4. GENERIC ADD (From Dedicated Pages)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_generic_option'])) {
    $pdo->prepare("INSERT INTO system_options (option_type, option_value) VALUES (?, ?)")->execute([$_POST['option_type'], $_POST['option_value']]);
    echo "<script>window.location.href='?page=settings&section=".$_POST['section_redirect']."';</script>";
}
if (isset($_GET['delete_opt'])) {
    $pdo->prepare("DELETE FROM system_options WHERE id = ?")->execute([$_GET['delete_opt']]);
    echo "<script>window.location.href='?page=settings&section=".$_GET['section']."';</script>";
}

// FETCH OPTIONS
$opts = $pdo->query("SELECT option_type, id, option_value FROM system_options ORDER BY option_value ASC")->fetchAll(PDO::FETCH_GROUP);
function get_opts($data, $key) { return isset($data[$key]) ? $data[$key] : []; }

$section = isset($_GET['section']) ? $_GET['section'] : 'main';
?>

<style>
    .setting-box { transition: transform 0.2s; cursor: pointer; text-decoration: none; display: block; color: inherit; }
    .setting-box:hover { transform: translateY(-5px); background: rgba(255,255,255,0.02); }
    .set-row { display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .set-row:last-child { border-bottom: none; }
</style>

<?php if ($section == 'main'): ?>
    
    <div class="form-header"><h2>⚙️ Settings</h2></div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <a href="?page=settings&section=inventory" class="card setting-box" style="border-top: 3px solid #FF9800;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;"><h3 style="color:#FF9800; margin:0;">📦 Inventory Categories</h3><i class="fas fa-arrow-right" style="color:#555;"></i></div><p style="color:#aaa; font-size:0.9rem;">Manage groups like Feeds, Meds.</p></a>
        <a href="?page=settings&section=livestock" class="card setting-box" style="border-top: 3px solid #2196F3;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;"><h3 style="color:#2196F3; margin:0;">🐄 Livestock Species</h3><i class="fas fa-arrow-right" style="color:#555;"></i></div><p style="color:#aaa; font-size:0.9rem;">Manage Species & Alert Limits.</p></a>
        <a href="?page=settings&section=sales" class="card setting-box" style="border-top: 3px solid #4CAF50;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;"><h3 style="color:#4CAF50; margin:0;">💰 Produce Categories</h3><i class="fas fa-arrow-right" style="color:#555;"></i></div><p style="color:#aaa; font-size:0.9rem;">Manage groups like Dairy, Crops.</p></a>
    </div>

<?php elseif ($section == 'livestock'): ?>
    <?php $limits = $pdo->query("SELECT * FROM species_limits ORDER BY species ASC")->fetchAll(); ?>
    <div class="form-container" style="max-width: 900px;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div style="display:flex; align-items:center;">
                <a href="?page=settings" style="text-decoration:none; color:#ccc; margin-right:15px; font-size:1rem;"><i class="fas fa-arrow-left"></i> Back</a>
                <h2 style="margin:0;">🐄 Livestock</h2>
            </div>
            <a href="?page=settings&section=add_species" style="background:#4CAF50; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:bold;">+ Add</a>
        </div>

        <div class="card" style="text-align:left; border-top: 3px solid #2196F3;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Species Name</th><th>Low Alert Limit</th><th style="text-align:right;">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach($limits as $l): ?>
                        <tr><td style="font-weight:bold;"><?php echo htmlspecialchars($l['species']); ?></td><td><span class="badge badge-orange"><?php echo $l['min_threshold']; ?> Head(s)</span></td><td style="text-align:right;"><a href="?page=settings&section=edit_species&species=<?php echo urlencode($l['species']); ?>" class="badge badge-blue" style="text-decoration:none; margin-right:10px;">Edit ✏️</a><a href="?page=settings&section=livestock&delete_species=<?php echo urlencode($l['species']); ?>" onclick="return confirm('Delete <?php echo $l['species']; ?>?')" style="color:#ff4b4b;"><i class="fas fa-trash"></i></a></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($section == 'add_species'): ?>
    <div class="form-container">
        <div class="form-header"><a href="?page=settings&section=livestock" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a><h2>Add New Species</h2></div>
        <div class="card" style="text-align:left; border-top: 3px solid #4CAF50;">
            <form method="POST">
                <label style="color:#aaa;">Species Name</label>
                <input type="text" name="option_value" placeholder="e.g. Duck" required>
                <label style="color:#aaa;">Low Stock Alert Limit</label>
                <input type="number" name="limit" placeholder="e.g. 5" value="5" required>
                <button type="submit" name="add_species" style="background:#4CAF50; width:100%; margin-top:20px;">+ Create Species</button>
            </form>
        </div>
    </div>

<?php elseif ($section == 'edit_species'): ?>
    <?php $s = urldecode($_GET['species']); $curr = $pdo->prepare("SELECT * FROM species_limits WHERE species=?"); $curr->execute([$s]); $c=$curr->fetch(); ?>
    <div class="form-container">
        <div class="form-header"><a href="?page=settings&section=livestock" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a><h2>Edit Species</h2></div>
        <div class="card" style="text-align:left; border-top: 3px solid #2196F3;">
            <form method="POST">
                <input type="hidden" name="original_name" value="<?php echo htmlspecialchars($c['species']); ?>">
                <input type="hidden" name="update_species_data" value="1">
                <label style="color:#aaa;">Species Name</label><input type="text" name="species_name" value="<?php echo htmlspecialchars($c['species']); ?>" required><p style="font-size:0.8rem; color:#666; margin-bottom:15px;">⚠️ Renaming will update all existing animal records.</p>
                <label style="color:#aaa;">Low Stock Alert Limit</label><input type="number" name="min_threshold" value="<?php echo $c['min_threshold']; ?>" required>
                <button type="submit" style="background:#2196F3; width:100%; margin-top:20px;">Save Changes</button>
            </form>
        </div>
    </div>

<?php elseif ($section == 'inventory'): ?>
    <div class="form-container" style="max-width: 800px;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div style="display:flex; align-items:center;">
                <a href="?page=settings" style="text-decoration:none; color:#ccc; margin-right:15px; font-size:1rem;"><i class="fas fa-arrow-left"></i> Back</a>
                <h2 style="margin:0;">📦 Inventory Categories</h2>
            </div>
            <a href="?page=settings&section=add_inv_cat" style="background:#4CAF50; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:bold;">+ Add</a>
        </div>

        <div class="card" style="text-align:left; border-top: 3px solid #FF9800;">
            <div style="max-height: 400px; overflow-y:auto;">
                <?php foreach(get_opts($opts, 'stock_category') as $o): ?><div class="set-row"><span><?php echo htmlspecialchars($o['option_value']); ?></span><a href="?page=settings&section=inventory&delete_opt=<?php echo $o['id']; ?>&section=inventory" onclick="return confirm('Delete?')" style="color:#ff4b4b;"><i class="fas fa-trash"></i> Delete</a></div><?php endforeach; ?>
            </div>
        </div>
    </div>

<?php elseif ($section == 'add_inv_cat'): ?>
    <div class="form-container">
        <div class="form-header"><a href="?page=settings&section=inventory" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a><h2>Add Inventory Category</h2></div>
        <div class="card" style="text-align:left; border-top: 3px solid #FF9800;">
            <form method="POST">
                <input type="hidden" name="section_redirect" value="inventory"><input type="hidden" name="option_type" value="stock_category">
                <label style="color:#aaa;">Category Name</label><input type="text" name="option_value" placeholder="e.g. Tools" required>
                <button type="submit" name="add_generic_option" style="background:#FF9800; width:100%; margin-top:20px;">+ Create Category</button>
            </form>
        </div>
    </div>

<?php elseif ($section == 'sales'): ?>
    <div class="form-container" style="max-width: 800px;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div style="display:flex; align-items:center;">
                <a href="?page=settings" style="text-decoration:none; color:#ccc; margin-right:15px; font-size:1rem;"><i class="fas fa-arrow-left"></i> Back</a>
                <h2 style="margin:0;">💰 Produce Categories</h2>
            </div>
            <a href="?page=settings&section=add_prod_cat" style="background:#4CAF50; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:bold;">+ Add</a>
        </div>

        <div class="card" style="text-align:left; border-top: 3px solid #4CAF50;">
            <div style="max-height: 400px; overflow-y:auto;">
                <?php foreach(get_opts($opts, 'produce_category') as $o): ?><div class="set-row"><span><?php echo htmlspecialchars($o['option_value']); ?></span><a href="?page=settings&section=sales&delete_opt=<?php echo $o['id']; ?>&section=sales" onclick="return confirm('Delete?')" style="color:#ff4b4b;"><i class="fas fa-trash"></i> Delete</a></div><?php endforeach; ?>
            </div>
        </div>
    </div>

<?php elseif ($section == 'add_prod_cat'): ?>
    <div class="form-container">
        <div class="form-header"><a href="?page=settings&section=sales" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a><h2>Add Produce Category</h2></div>
        <div class="card" style="text-align:left; border-top: 3px solid #4CAF50;">
            <form method="POST">
                <input type="hidden" name="section_redirect" value="sales"><input type="hidden" name="option_type" value="produce_category">
                <label style="color:#aaa;">Category Name</label><input type="text" name="option_value" placeholder="e.g. Fruits" required>
                <button type="submit" name="add_generic_option" style="background:#4CAF50; width:100%; margin-top:20px;">+ Create Category</button>
            </form>
        </div>
    </div>

<?php endif; ?>