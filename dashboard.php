<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
check_login();

// Helper for safe queries
function safe_query($conn, $sql, $default = 0) {
    try {
        $result = $conn->query($sql);
        if (!$result) return $default;
        return $result;
    } catch (Exception $e) {
        return $default;
    }
}

function safe_fetch($conn, $sql, $default = null) {
    $result = safe_query($conn, $sql, false);
    if (!$result || $result->num_rows == 0) return $default;
    return $result->fetch_assoc();
}

function safe_row($conn, $sql, $default = 0) {
    $row = safe_fetch($conn, $sql, null);
    return $row ? ($row[0] ?? $default) : $default;
}

// Fetch statistics
$total_medicines = safe_row($conn, "SELECT COUNT(*) FROM medicines");
$total_suppliers = safe_row($conn, "SELECT COUNT(*) FROM suppliers");
$total_customers = safe_row($conn, "SELECT COUNT(*) FROM customers");
$total_sales = safe_row($conn, "SELECT COALESCE(SUM(total_amount),0) FROM sales");
$low_stock = safe_row($conn, "SELECT COUNT(*) FROM v_low_stock_medicines");
$expired = safe_row($conn, "SELECT COUNT(*) FROM v_expired_medicines");
$total_invoices = safe_row($conn, "SELECT COUNT(*) FROM sales");

// Today's stats
$today = date('Y-m-d');
$today_sales = safe_fetch($conn, "SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as total FROM sales WHERE DATE(sale_date) = '$today'", ['cnt' => 0, 'total' => 0]);
$today_stock_in = safe_row($conn, "SELECT COALESCE(SUM(quantity),0) FROM stock_transactions WHERE type='IN' AND DATE(transaction_date) = '$today'");

// Recent Sales
$recent_sales = safe_query($conn, "SELECT s.*, c.name as customer_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.customer_id ORDER BY s.sale_date DESC LIMIT 5", null);

// Recent Activity
$recent_logs = safe_query($conn, "SELECT al.*, u.full_name FROM activity_logs al LEFT JOIN users u ON al.user_id = u.user_id ORDER BY al.created_at DESC LIMIT 8", null);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Stat Cards - Clickable -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <a href="medicines.php" class="text-decoration-none">
            <div class="stat-card bg-primary-gradient animate-fade-in">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Total Medicines</div>
                        <div class="stat-value"><?php echo number_format($total_medicines); ?></div>
                        <div class="stat-change"><i class="fas fa-arrow-right me-1"></i>View Inventory</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-pills"></i></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="sales.php" class="text-decoration-none">
            <div class="stat-card bg-success-gradient animate-fade-in">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Today's Sales</div>
                        <div class="stat-value"><?php echo format_currency($today_sales['total']); ?></div>
                        <div class="stat-change"><i class="fas fa-receipt me-1"></i><?php echo $today_sales['cnt']; ?> invoices today</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="reports.php?type=low_stock" class="text-decoration-none">
            <div class="stat-card bg-warning-gradient animate-fade-in">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Low Stock Alert</div>
                        <div class="stat-value"><?php echo $low_stock; ?></div>
                        <div class="stat-change"><i class="fas fa-exclamation-triangle me-1"></i>Needs restocking</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="reports.php?type=expired" class="text-decoration-none">
            <div class="stat-card bg-danger-gradient animate-fade-in">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Expired Items</div>
                        <div class="stat-value"><?php echo $expired; ?></div>
                        <div class="stat-change"><i class="fas fa-calendar-times me-1"></i>Remove from stock</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-calendar-times"></i></div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="row g-4">
    <!-- Recent Sales -->
    <div class="col-xl-5">
        <div class="card" style="height: 100%;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold"><i class="fas fa-receipt me-2 text-primary"></i>Recent Sales</h6>
                <a href="sales.php" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>New Sale</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($recent_sales->num_rows > 0): ?>
                                <?php while($sale = $recent_sales->fetch_assoc()): ?>
                                <tr style="cursor: pointer;" onclick="window.location='invoice.php?id=<?php echo $sale['sale_id']; ?>'">
                                    <td>
                                        <span class="badge" style="background: rgba(99,102,241,0.1); color: #6366f1; padding: 4px 8px;">
                                            #<?php echo $sale['invoice_no']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $sale['customer_name'] ?: 'Walk-in'; ?></td>
                                    <td class="text-end fw-bold" style="color: #059669;"><?php echo format_currency($sale['total_amount']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                        <small>No sales yet</small>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Chart -->
    <div class="col-xl-3">
        <div class="card" style="height: 100%;">
            <div class="card-header">
                <h6 class="m-0 fw-bold"><i class="fas fa-chart-pie me-2 text-primary"></i>Inventory</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 200px;">
                    <canvas id="inventoryChart"></canvas>
                </div>
                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2" style="font-size: 0.85rem;">
                        <span><span style="display: inline-block; width: 8px; height: 8px; border-radius: 2px; background: #10b981;"></span> In Stock</span>
                        <span class="fw-bold"><?php echo $total_medicines - $low_stock - $expired; ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2" style="font-size: 0.85rem;">
                        <span><span style="display: inline-block; width: 8px; height: 8px; border-radius: 2px; background: #f59e0b;"></span> Low Stock</span>
                        <a href="reports.php?type=low_stock" class="fw-bold text-decoration-none" style="color: #f59e0b;"><?php echo $low_stock; ?></a>
                    </div>
                    <div class="d-flex justify-content-between align-items-center" style="font-size: 0.85rem;">
                        <span><span style="display: inline-block; width: 8px; height: 8px; border-radius: 2px; background: #ef4444;"></span> Expired</span>
                        <a href="reports.php?type=expired" class="fw-bold text-decoration-none" style="color: #ef4444;"><?php echo $expired; ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-xl-4">
        <div class="card" style="height: 100%;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold"><i class="fas fa-history me-2 text-primary"></i>Recent Activity</h6>
                <a href="logs.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0" style="max-height: 380px; overflow-y: auto;">
                <?php if($recent_logs->num_rows > 0): ?>
                    <?php while($log = $recent_logs->fetch_assoc()): ?>
                    <div class="d-flex align-items-start px-4 py-3" style="gap: 10px; border-bottom: 1px solid rgba(0,0,0,0.04);">
                        <?php
                        $log_icons = [
                            'LOGIN' => ['icon' => 'fa-sign-in-alt', 'color' => '#10b981'],
                            'LOGOUT' => ['icon' => 'fa-sign-out-alt', 'color' => '#94a3b8'],
                            'CREATE' => ['icon' => 'fa-plus', 'color' => '#6366f1'],
                            'UPDATE' => ['icon' => 'fa-pen', 'color' => '#0ea5e9'],
                            'DELETE' => ['icon' => 'fa-trash', 'color' => '#ef4444'],
                            'SALE' => ['icon' => 'fa-shopping-cart', 'color' => '#10b981'],
                        ];
                        $li = $log_icons[$log['action']] ?? ['icon' => 'fa-circle', 'color' => '#94a3b8'];
                        ?>
                        <div style="width: 28px; height: 28px; border-radius: 7px; background: <?php echo $li['color']; ?>15; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas <?php echo $li['icon']; ?>" style="color: <?php echo $li['color']; ?>; font-size: 0.7rem;"></i>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-size: 0.8rem; font-weight: 500; color: var(--dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo $log['description'] ?: $log['action']; ?>
                            </div>
                            <div style="font-size: 0.7rem; color: #94a3b8;">
                                <?php echo $log['full_name'] ?? 'System'; ?> &middot; <?php echo time_ago($log['created_at']); ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-history fa-2x mb-2 d-block opacity-25"></i>
                        <small>No activity yet</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats Row -->
<div class="row g-4 mt-2">
    <div class="col-xl-3 col-md-6">
        <a href="suppliers.php" class="text-decoration-none">
            <div class="card" style="transition: var(--transition);">
                <div class="card-body d-flex align-items-center" style="gap: 16px;">
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(99, 102, 241, 0.1); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-truck" style="color: var(--primary); font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 500;">Suppliers</div>
                        <div style="font-size: 1.3rem; font-weight: 700; color: var(--dark);"><?php echo $total_suppliers; ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="customers.php" class="text-decoration-none">
            <div class="card" style="transition: var(--transition);">
                <div class="card-body d-flex align-items-center" style="gap: 16px;">
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(14, 165, 233, 0.1); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-users" style="color: var(--secondary); font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 500;">Customers</div>
                        <div style="font-size: 1.3rem; font-weight: 700; color: var(--dark);"><?php echo $total_customers; ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="categories.php" class="text-decoration-none">
            <div class="card" style="transition: var(--transition);">
                <div class="card-body d-flex align-items-center" style="gap: 16px;">
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-boxes" style="color: var(--success); font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 500;">Categories</div>
                        <div style="font-size: 1.3rem; font-weight: 700; color: var(--dark);"><?php echo $conn->query("SELECT COUNT(*) FROM categories")->fetch_row()[0]; ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="stock_transactions.php" class="text-decoration-none">
            <div class="card" style="transition: var(--transition);">
                <div class="card-body d-flex align-items-center" style="gap: 16px;">
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-exchange-alt" style="color: var(--warning); font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 500;">Stock IN Today</div>
                        <div style="font-size: 1.3rem; font-weight: 700; color: var(--dark);"><?php echo $today_stock_in; ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('inventoryChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['In Stock', 'Low Stock', 'Expired'],
            datasets: [{
                data: [<?php echo max(0, $total_medicines - $low_stock - $expired); ?>, <?php echo $low_stock; ?>, <?php echo $expired; ?>],
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                borderWidth: 0,
                hoverOffset: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { size: 13, weight: '600' },
                    bodyFont: { size: 12 },
                    padding: 12,
                    cornerRadius: 8,
                }
            },
            animation: { animateRotate: true, duration: 1200 }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
