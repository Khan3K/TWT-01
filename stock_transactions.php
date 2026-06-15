<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
check_login();

$transactions = $conn->query("SELECT t.*, m.medicine_name as med_name, u.username FROM stock_transactions t LEFT JOIN medicines m ON t.medicine_id = m.medicine_id LEFT JOIN users u ON t.user_id = u.user_id ORDER BY t.transaction_date DESC");

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="page-header">
    <h2><i class="fas fa-exchange-alt me-2 text-primary"></i>Stock Movements</h2>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Medicine</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Reference #</th>
                        <th>Notes</th>
                        <th>By User</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($transactions->num_rows > 0): ?>
                        <?php while($t = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td class="text-muted"><?php echo date('M d, Y H:i', strtotime($t['transaction_date'])); ?></td>
                            <td class="fw-bold"><?php echo $t['med_name']; ?></td>
                            <td>
                                <?php if($t['type'] == 'IN'): ?>
                                    <span class="badge" style="background: rgba(16,185,129,0.1); color: #059669;">
                                        <i class="fas fa-arrow-down me-1" style="font-size: 0.7rem;"></i>IN
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background: rgba(239,68,68,0.1); color: #ef4444;">
                                        <i class="fas fa-arrow-up me-1" style="font-size: 0.7rem;"></i>OUT
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fw-bold" style="color: <?php echo ($t['type'] == 'IN') ? '#059669' : '#ef4444'; ?>;">
                                    <?php echo ($t['type'] == 'IN') ? '+' : '-'; ?><?php echo $t['quantity']; ?>
                                </span>
                            </td>
                            <td><span class="badge bg-light text-dark"><?php echo $t['reference_no']; ?></span></td>
                            <td class="text-muted"><?php echo $t['notes']; ?></td>
                            <td>
                                <div class="d-flex align-items-center" style="gap: 6px;">
                                    <div style="width: 24px; height: 24px; border-radius: 6px; background: rgba(99,102,241,0.1); display: flex; align-items: center; justify-content: center; color: #6366f1; font-weight: 600; font-size: 0.65rem;">
                                        <?php echo strtoupper(substr($t['username'], 0, 1)); ?>
                                    </div>
                                    <?php echo $t['username']; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-exchange-alt"></i>
                                    <p>No stock movements recorded</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
