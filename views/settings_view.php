<?php
/**
 * SETTINGS VIEW
 * - Features: Manage Categories and Species ONLY.
 * - Removed: Unit management (Units are now free-text in the Add/Edit views).
 */

// --- BACKEND LOGIC ---

// 1. ADD OPTION
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_option'])) {
    $type = $_POST['option_type'];
    $val = trim($_POST['option_value']);
    $sec = $_POST['section_redirect'];
    
    // Check duplicates
    $chk = $pdo->prepare("SELECT id FROM system_options WHERE option_type = ? AND option_value = ?");
    $chk->execute([$type, $val]);
    
    if ($chk->rowCount() == 0 && !empty($val)) {
        $pdo->prepare("INSERT INTO system_options (option_type, option_value) VALUES (?, ?)")->execute([$type, $val]);
    }
    echo "<script>window.location.href='?page=settings&section=$sec';</script>";
}

// 2. DELETE OPTION
if (isset($_GET['delete_opt'])) {
    $id = $_GET['delete_opt'];
    $sec = isset($_GET['section']) ? $_GET['section'] : 'main';
    $pdo->prepare("DELETE FROM system_options WHERE id = ?")->execute([$id]);
    echo "<script>window.location.href='?page=settings&section=$sec';</script>";
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
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <h3 style="color:#FF9800; margin:0;">📦 Inventory Categories</h3>
                <i class="fas fa-arrow-right" style="color:#555;"></i>
            </div>
            <p style="color:#aaa; font-size:0.9rem;">Manage groups like Feeds, Meds, etc.</p>
        </a>

        <a href="?page=settings&section=livestock" class="card setting-box" style="border-top: 3px solid #2196F3;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <h3 style="color:#2196F3; margin:0;">🐄 Livestock Species</h3>
                <i class="fas fa-arrow-right" style="color:#555;"></i>
            </div>
            <p style="color:#aaa; font-size:0.9rem;">Manage list of Animals (Cows, Goats)</p>
        </a>

        <a href="?page=settings&section=sales" class="card setting-box" style="border-top: 3px solid #4CAF50;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <h3 style="color:#4CAF50; margin:0;">💰 Produce Categories</h3>
                <i class="fas fa-arrow-right" style="color:#555;"></i>
            </div>
            <p style="color:#aaa; font-size:0.9rem;">Manage groups like Dairy, Crops, etc.</p>
        </a>
    </div>

<?php elseif ($section == 'inventory'): ?>
    <div class="form-container" style="max-width: 800px;">
        <div class="form-header"><a href="?page=settings" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a><h2>📦 Inventory Categories</h2></div>
        <div class="card" style="text-align:left; border-top: 3px solid #FF9800;">
            <div style="max-height: 400px; overflow-y:auto; margin-bottom:15px;">
                <?php foreach(get_opts($opts, 'stock_category') as $o): ?>
                    <div class="set-row"><span><?php echo htmlspecialchars($o['option_value']); ?></span><a href="?page=settings&section=inventory&delete_opt=<?php echo $o['id']; ?>" onclick="return confirm('Delete?')" style="color:#ff4b4b;"><i class="fas fa-trash"></i> Delete</a></div>
                <?php endforeach; ?>
            </div>
            <form method="POST" style="display:flex; gap:10px; border-top:1px solid #333; padding-top:15px;">
                <input type="hidden" name="section_redirect" value="inventory">
                <input type="hidden" name="option_type" value="stock_category">
                <input type="text" name="option_value" placeholder="New Category (e.g. Tools)" required style="flex:1;">
                <button type="submit" name="add_option" style="background:#4CAF50; width:auto;">+ Add</button>
            </form>
        </div>
    </div>

<?php elseif ($section == 'livestock'): ?>
    <div class="form-container" style="max-width: 800px;">
        <div class="form-header"><a href="?page=settings" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a><h2>🐄 Livestock Species</h2></div>
        <div class="card" style="text-align:left; border-top: 3px solid #2196F3;">
            <div style="max-height: 400px; overflow-y:auto; margin-bottom:15px;">
                <?php foreach(get_opts($opts, 'animal_species') as $o): ?>
                    <div class="set-row"><span><?php echo htmlspecialchars($o['option_value']); ?></span><a href="?page=settings&section=livestock&delete_opt=<?php echo $o['id']; ?>" onclick="return confirm('Delete?')" style="color:#ff4b4b;"><i class="fas fa-trash"></i> Delete</a></div>
                <?php endforeach; ?>
            </div>
            <form method="POST" style="display:flex; gap:10px; border-top:1px solid #333; padding-top:15px;">
                <input type="hidden" name="section_redirect" value="livestock">
                <input type="hidden" name="option_type" value="animal_species">
                <input type="text" name="option_value" placeholder="New Species (e.g. Duck)" required style="flex:1;">
                <button type="submit" name="add_option" style="background:#4CAF50; width:auto;">+ Add</button>
            </form>
        </div>
    </div>

<?php elseif ($section == 'sales'): ?>
    <div class="form-container" style="max-width: 800px;">
        <div class="form-header"><a href="?page=settings" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a><h2>💰 Produce Categories</h2></div>
        <div class="card" style="text-align:left; border-top: 3px solid #4CAF50;">
            <div style="max-height: 400px; overflow-y:auto; margin-bottom:15px;">
                <?php foreach(get_opts($opts, 'produce_category') as $o): ?>
                    <div class="set-row"><span><?php echo htmlspecialchars($o['option_value']); ?></span><a href="?page=settings&section=sales&delete_opt=<?php echo $o['id']; ?>" onclick="return confirm('Delete?')" style="color:#ff4b4b;"><i class="fas fa-trash"></i> Delete</a></div>
                <?php endforeach; ?>
            </div>
            <form method="POST" style="display:flex; gap:10px; border-top:1px solid #333; padding-top:15px;">
                <input type="hidden" name="section_redirect" value="sales">
                <input type="hidden" name="option_type" value="produce_category">
                <input type="text" name="option_value" placeholder="New Category (e.g. Fruits)" required style="flex:1;">
                <button type="submit" name="add_option" style="background:#4CAF50; width:auto;">+ Add</button>
            </form>
        </div>
    </div>

<?php endif; ?>