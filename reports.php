<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
check_login();

$type = $_GET['type'] ?? 'low_stock';
$data = [];

if ($type == 'low_stock') {
    $data = $conn->query("SELECT * FROM v_low_stock_medicines");
    $title = "Low Stock Medicines";
    $icon = "fas fa-exclamation-triangle";
    $color = "#f59e0b";
} elseif ($type == 'expired') {
    $data = $conn->query("SELECT * FROM v_expired_medicines");
    $title = "Expired Medicines";
    $icon = "fas fa-calendar-times";
    $color = "#ef4444";
} elseif ($type == 'expiring_soon') {
    $data = $conn->query("SELECT * FROM v_expiring_medicines");
    $title = "Expiring Soon (30 Days)";
    $icon = "fas fa-clock";
    $color = "#f97316";
} elseif ($type == 'sales') {
    $data = $conn->query("SELECT s.*, c.name as customer_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.customer_id ORDER BY s.sale_date DESC");
    $title = "Sales Report";
    $icon = "fas fa-receipt";
    $color = "#10b981";
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="page-header">
    <h2><i class="fas fa-chart-line me-2 text-primary"></i>Reports</h2>
</div>

<!-- Report Type Tabs -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <a href="reports.php?type=low_stock" class="btn <?php echo $type == 'low_stock' ? 'btn-warning' : 'btn-outline-secondary'; ?>">
                <i class="fas fa-exclamation-triangle me-1"></i>Low Stock
            </a>
            <a href="reports.php?type=expired" class="btn <?php echo $type == 'expired' ? 'btn-danger' : 'btn-outline-secondary'; ?>">
                <i class="fas fa-calendar-times me-1"></i>Expired
            </a>
            <a href="reports.php?type=expiring_soon" class="btn <?php echo $type == 'expiring_soon' ? 'btn-info' : 'btn-outline-secondary'; ?>">
                <i class="fas fa-clock me-1"></i>Expiring Soon
            </a>
            <a href="reports.php?type=sales" class="btn <?php echo $type == 'sales' ? 'btn-success' : 'btn-outline-secondary'; ?>">
                <i class="fas fa-receipt me-1"></i>Sales
            </a>
        </div>
    </div>
</div>

<!-- Report Content -->
<div class="card">
    <div class="card-header d-flex align-items-center" style="gap: 10px;">
        <div style="width: 36px; height: 36px; border-radius: 10px; background: <?php echo $color; ?>15; display: flex; align-items: center; justify-content: center;">
            <i class="<?php echo $icon; ?>" style="color: <?php echo $color; ?>; font-size: 0.9rem;"></i>
        </div>
        <h6 class="m-0 fw-bold"><?php echo $title; ?></h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <?php if ($type == 'sales'): ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($data->num_rows > 0): ?>
                            <?php while($row = $data->fetch_assoc()): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark">#<?php echo $row['invoice_no']; ?></span></td>
                                <td><?php echo $row['customer_name'] ?: 'Walk-in'; ?></td>
                                <td class="text-muted"><?php echo date('M d, Y', strtotime($row['sale_date'])); ?></td>
                                <td class="text-end fw-bold" style="color: #059669;"><?php echo format_currency($row['total_amount']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="fas fa-receipt"></i>
                                        <p>No sales data</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th>Batch #</th>
                            <th>Expiry Date</th>
                            <th>Stock</th>
                            <?php if($type == 'low_stock'): ?>
                                <th>Reorder Level</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($data->num_rows > 0): ?>
                            <?php while($row = $data->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold"><?php echo $row['medicine_name']; ?></td>
                                <td><span class="badge bg-light text-dark"><?php echo $row['batch_no'] ?? '-'; ?></span></td>
                                <td class="text-muted"><?php echo $row['expiry_date'] ?? '-'; ?></td>
                                <td>
                                    <span class="fw-bold" style="color: <?php echo ($row['quantity'] <= 5) ? '#ef4444' : '#059669'; ?>;">
                                        <?php echo $row['quantity']; ?>
                                    </span>
                                </td>
                                <?php if($type == 'low_stock'): ?>
                                    <td><?php echo $row['reorder_level']; ?></td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="<?php echo $icon; ?>"></i>
                                        <p>No data available</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
