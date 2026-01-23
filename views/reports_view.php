<?php

/**
 * REPORTS VIEW
 * - Update: "Net Profit" card now smooth-scrolls to the Income Chart.
 */

// ... (Data fetching logic remains exactly the same) ...
// Get Date Range (Default: Last 30 Days)
$start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-30 days'));
$end = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');
$action = isset($_GET['action']) ? $_GET['action'] : 'overview';

// A. Calculate Total Income (Sales)
$sql_inc = "SELECT SUM(total_revenue) FROM sales_logs WHERE action_type='Sold' AND DATE(log_date) BETWEEN ? AND ?";
$stmt = $pdo->prepare($sql_inc);
$stmt->execute([$start, $end]);
$total_income = $stmt->fetchColumn() ?: 0;

// B. Calculate Total Expenses (Stock Purchases)
$sql_exp = "SELECT SUM(total_cost) FROM stock_logs WHERE action_type='Restocked' AND DATE(log_date) BETWEEN ? AND ?";
$stmt = $pdo->prepare($sql_exp);
$stmt->execute([$start, $end]);
$total_expense = $stmt->fetchColumn() ?: 0;

// C. Calculate Net Profit
$net_profit = $total_income - $total_expense;

// D. Calculate Total Production Volume (Harvests)
$sql_prod = "SELECT SUM(quantity) FROM sales_logs WHERE action_type='Harvest' AND DATE(log_date) BETWEEN ? AND ?";
$stmt = $pdo->prepare($sql_prod);
$stmt->execute([$start, $end]);
$total_production = $stmt->fetchColumn() ?: 0;

// E. Prepare Chart Data
$chart_sql = "SELECT DATE(log_date) as d, SUM(total_revenue) as inc FROM sales_logs WHERE action_type='Sold' AND DATE(log_date) BETWEEN ? AND ? GROUP BY d ORDER BY d ASC";
$stmt = $pdo->prepare($chart_sql);
$stmt->execute([$start, $end]);
$chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dates = [];
$incomes = [];
foreach ($chart_data as $Row) {
    $dates[] = date('M d', strtotime($Row['d']));
    $incomes[] = $Row['inc'];
}
?>

<?php if ($action == 'production_report'):
    // ... (Production Report Logic same as before) ...
    $p_sql = "SELECT * FROM sales_logs WHERE action_type='Harvest' AND DATE(log_date) BETWEEN ? AND ? ORDER BY log_date DESC";
    $stmt = $pdo->prepare($p_sql);
    $stmt->execute([$start, $end]);
    $harvests = $stmt->fetchAll();
?>
    <div class="form-container" style="max-width:800px;">
        <div class="form-header">
            <a href="?page=reports&start=<?php echo $start; ?>&end=<?php echo $end; ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <h2>Production Report</h2>
        </div>

        <div class="card" style="text-align:left; border-top: 3px solid #2196F3;">
            <div style="margin-bottom:20px; text-align:center;">
                <p style="color:#aaa; font-size:0.9rem;">Period: <?php echo date('M d', strtotime($start)) . ' - ' . date('M d', strtotime($end)); ?></p>
                <h1 style="color:#2196F3; font-size:2.5rem; margin:0;"><?php echo number_format($total_production); ?> <span style="font-size:1rem; color:#aaa;">units</span></h1>
                <p style="color:#aaa;">Total Harvested</p>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Recorded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($harvests as $h): ?>
                            <tr>
                                <td data-label="Date" style="color:#888;">
                                    <?php echo date('d M Y, H:i', strtotime($h['log_date'])); ?>
                                </td>
                                <td data-label="Product">
                                    <?php echo $h['product_name']; ?>
                                </td>
                                <td data-label="Quantity" style="font-weight:bold; color:#2196F3;">
                                    <?php echo $h['quantity']; ?>
                                </td>
                                <td data-label="Recorded By">
                                    <?php echo $h['user']; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (count($harvests) == 0) echo "<tr><td colspan='4' style='text-align:center; padding:20px; color:#666;'>No production recorded in this period.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php else: ?>

    <div class="card" style="margin-bottom:20px;">
        <form method="GET" style="display:flex; gap:15px; align-items:end; flex-wrap:wrap;">
            <input type="hidden" name="page" value="reports">
            <div style="flex:1; min-width:150px;">
                <label style="color:#aaa; font-size:0.9rem;">Start Date</label>
                <input type="date" name="start" value="<?php echo $start; ?>" required>
            </div>
            <div style="flex:1; min-width:150px;">
                <label style="color:#aaa; font-size:0.9rem;">End Date</label>
                <input type="date" name="end" value="<?php echo $end; ?>" required>
            </div>
            <button type="submit" style="background:#2196F3; height:45px; min-width:100px;">Filter Report</button>
        </form>
    </div>

    <div class="metrics-grid">

        <a href="#" onclick="event.preventDefault(); document.getElementById('history-table').scrollIntoView({behavior: 'smooth'});" style="text-decoration:none; color:inherit; display:block;">
            <div class="metric-card" style="border-left:4px solid #4CAF50; transition:transform 0.2s; cursor:pointer;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                <h4>Total Income <i class="fas fa-arrow-down" style="font-size:0.8rem; margin-left:5px; opacity:0.5;"></i></h4>
                <div class="value" style="color:#4CAF50;">Ksh <?php echo number_format($total_income); ?></div>
                <div class="sub-text">From Sales</div>
            </div>
        </a>

        <a href="#" onclick="event.preventDefault(); document.getElementById('history-table').scrollIntoView({behavior: 'smooth'});" style="text-decoration:none; color:inherit; display:block;">
            <div class="metric-card" style="border-left:4px solid #ff4b4b; transition:transform 0.2s; cursor:pointer;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                <h4>Total Expenses <i class="fas fa-arrow-down" style="font-size:0.8rem; margin-left:5px; opacity:0.5;"></i></h4>
                <div class="value" style="color:#ff4b4b;">Ksh <?php echo number_format($total_expense); ?></div>
                <div class="sub-text">From Restocking</div>
            </div>
        </a>

        <a href="#" onclick="event.preventDefault(); document.getElementById('income-chart').scrollIntoView({behavior: 'smooth'});" style="text-decoration:none; color:inherit; display:block;">
            <div class="metric-card" style="border-left:4px solid <?php echo ($net_profit >= 0) ? '#FF9800' : '#ff4b4b'; ?>; transition:transform 0.2s; cursor:pointer;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                <h4>Net Profit <i class="fas fa-chart-line" style="font-size:0.8rem; margin-left:5px; opacity:0.5;"></i></h4>
                <div class="value" style="color:<?php echo ($net_profit >= 0) ? '#FF9800' : '#ff4b4b'; ?>;">
                    Ksh <?php echo number_format($net_profit); ?>
                </div>
                <div class="sub-text"><?php echo ($net_profit >= 0) ? 'Profit' : 'Loss'; ?></div>
            </div>
        </a>

        <a href="?page=reports&action=production_report&start=<?php echo $start; ?>&end=<?php echo $end; ?>" style="text-decoration:none;">
            <div class="metric-card" style="border-left:4px solid #2196F3; transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                <h4>Production <i class="fas fa-external-link-alt" style="font-size:0.8rem; margin-left:5px;"></i></h4>
                <div class="value" style="color:#2196F3;"><?php echo number_format($total_production); ?></div>
                <div class="sub-text">Units Harvested</div>
            </div>
        </a>
    </div>

    <div class="card" id="income-chart">
        <h3>📈 Income Trend (Selected Period)</h3>
        <div style="height:300px;">
            <canvas id="incomeChart"></canvas>
        </div>
    </div>

    <div class="card" id="history-table">
        <h3>📄 Transaction History</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Item</th>
                        <th>Customer</th>
                        <th>Detail</th>
                        <th>Amount (Ksh)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "
                        (SELECT log_date, 'Income' as type, product_name as item, customer_name, CONCAT('Qty: ', quantity) as detail, total_revenue as amount FROM sales_logs WHERE action_type='Sold' AND DATE(log_date) BETWEEN ? AND ?)
                        UNION
                        (SELECT log_date, 'Expense' as type, item_name as item, NULL as customer_name, CONCAT('Restock Qty: ', quantity) as detail, total_cost as amount FROM stock_logs WHERE action_type='Restocked' AND DATE(log_date) BETWEEN ? AND ?)
                        ORDER BY log_date DESC LIMIT 50";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$start, $end, $start, $end]);
                    $logs = $stmt->fetchAll();

                    foreach ($logs as $log):
                    ?>
                        <tr>
                            <td data-label="Date" style="color:#888;">
                                <?php echo date('d M Y', strtotime($log['log_date'])); ?>
                            </td>
                            <td data-label="Type">
                                <span class="badge <?php echo ($log['type'] == 'Income') ? 'badge-green' : 'badge-red'; ?>">
                                    <?php echo $log['type']; ?>
                                </span>
                            </td>
                            <td data-label="Item"><?php echo $log['item']; ?></td>
                            <td data-label="Customer" style="color:#ccc;"><?php echo !empty($log['customer_name']) ? htmlspecialchars($log['customer_name']) : '-'; ?></td>
                            <td data-label="Detail" style="color:#ccc;"><?php echo $log['detail']; ?></td>
                            <td data-label="Amount" style="font-weight:bold; color:<?php echo ($log['type'] == 'Income') ? '#4CAF50' : '#ff4b4b'; ?>;">
                                <?php echo number_format($log['amount']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (count($logs) == 0) echo "<tr><td colspan='6' style='text-align:center; padding:20px; color:#666;'>No financial activity in this period.</td></tr>"; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('incomeChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Income',
                    data: <?php echo json_encode($incomes); ?>,
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.2)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#333'
                        }
                    },
                    x: {
                        grid: {
                            color: '#333'
                        }
                    }
                }
            }
        });
    </script>
<?php endif; ?>