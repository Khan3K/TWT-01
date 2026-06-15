<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
check_login();

// Handle Create Sale
if (isset($_POST['create_sale'])) {
    $customer_id = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $discount = (float)($_POST['discount'] ?? 0);
    $tax = (float)($_POST['tax'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $invoice_no = generate_invoice_no();
    $medicines = $_POST['medicines'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $prices = $_POST['prices'] ?? [];
    $total_amount = 0;

    if (empty($medicines)) {
        redirect('sales.php', "Please add at least one item!", 'danger');
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO sales (customer_id, invoice_no, subtotal, discount, tax, total_amount, payment_method, user_id) VALUES (?, ?, 0, ?, ?, 0, ?, ?)");
        $stmt->bind_param("idddsi", $customer_id, $invoice_no, $discount, $tax, $payment_method, $_SESSION['user_id']);
        $stmt->execute();
        $sale_id = $conn->insert_id;

        foreach ($medicines as $i => $med_id) {
            $med_id = (int)$med_id;
            $qty = (int)$quantities[$i];
            $price = (float)$prices[$i];
            $subtotal = $qty * $price;
            $total_amount += $subtotal;

            // Check stock
            $stock_check = $conn->query("SELECT quantity, medicine_name FROM medicines WHERE medicine_id = $med_id")->fetch_assoc();
            if ($stock_check && $stock_check['quantity'] < $qty) {
                throw new Exception("Insufficient stock for " . $stock_check['medicine_name'] . " (Available: " . $stock_check['quantity'] . ")");
            }

            $stmt = $conn->prepare("INSERT INTO sale_items (sale_id, medicine_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiidd", $sale_id, $med_id, $qty, $price, $subtotal);
            $stmt->execute();
        }

        $final_total = $total_amount - $discount + $tax;
        $stmt = $conn->prepare("UPDATE sales SET subtotal = ?, total_amount = ? WHERE sale_id = ?");
        $stmt->bind_param("ddi", $total_amount, $final_total, $sale_id);
        $stmt->execute();

        $conn->commit();

        // Log the sale
        log_activity('SALE', 'sales', $sale_id, "Invoice $invoice_no created - Total: " . format_currency($final_total));

        redirect('sales.php', "Invoice <strong>$invoice_no</strong> created successfully! Total: " . format_currency($final_total));
    } catch (Exception $e) {
        $conn->rollback();
        redirect('sales.php', "Error: " . $e->getMessage(), 'danger');
    }
}

// Handle Quick Customer Add
if (isset($_POST['add_customer'])) {
    $name = trim($_POST['new_cust_name']);
    $phone = trim($_POST['new_cust_phone']);
    $email = trim($_POST['new_cust_email']);

    if (empty($name)) {
        redirect('sales.php', "Customer name is required!", 'danger');
    }

    $stmt = $conn->prepare("INSERT INTO customers (name, phone, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $phone, $email);
    try {
        $stmt->execute();
        $new_id = $conn->insert_id;
        log_activity('CREATE', 'customers', $new_id, "Quick-added customer: $name");
        redirect('sales.php', "Customer <strong>$name</strong> added successfully!");
    } catch (Exception $e) {
        redirect('sales.php', "Error: " . $e->getMessage(), 'danger');
    }
}

// Fetch data
$customers = $conn->query("SELECT * FROM customers ORDER BY name ASC");
$medicines_list = $conn->query("SELECT * FROM medicines WHERE quantity > 0 ORDER BY medicine_name ASC");
$sales_history = $conn->query("SELECT s.*, c.name as customer_name, u.full_name as cashier FROM sales s LEFT JOIN customers c ON s.customer_id = c.customer_id LEFT JOIN users u ON s.user_id = u.user_id ORDER BY s.sale_date DESC LIMIT 50");

// Stats for today
$today = date('Y-m-d');
$today_sales = $conn->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as total FROM sales WHERE DATE(sale_date) = '$today'")->fetch_assoc();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="page-header">
    <h2><i class="fas fa-shopping-cart me-2 text-primary"></i>Sales & Billing</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#saleModal">
        <i class="fas fa-plus"></i> New Sale
    </button>
</div>

<!-- Today's Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card" style="border-left: 4px solid #6366f1;">
            <div class="card-body d-flex align-items-center" style="gap: 12px; padding: 16px 20px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(99,102,241,0.1); display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-receipt" style="color: #6366f1;"></i>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 500;">Today's Invoices</div>
                    <div style="font-size: 1.2rem; font-weight: 700;"><?php echo $today_sales['cnt']; ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card" style="border-left: 4px solid #10b981;">
            <div class="card-body d-flex align-items-center" style="gap: 12px; padding: 16px 20px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(16,185,129,0.1); display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-dollar-sign" style="color: #10b981;"></i>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 500;">Today's Revenue</div>
                    <div style="font-size: 1.2rem; font-weight: 700; color: #059669;"><?php echo format_currency($today_sales['total']); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card" style="border-left: 4px solid #0ea5e9;">
            <div class="card-body d-flex align-items-center" style="gap: 12px; padding: 16px 20px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(14,165,233,0.1); display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-boxes" style="color: #0ea5e9;"></i>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 500;">Items in Stock</div>
                    <div style="font-size: 1.2rem; font-weight: 700;"><?php echo $conn->query("SELECT COALESCE(SUM(quantity),0) FROM medicines")->fetch_row()[0]; ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sales Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-bold"><i class="fas fa-list me-2 text-primary"></i>Recent Invoices</h6>
        <a href="reports.php?type=sales" class="btn btn-sm btn-outline-primary">View Full Report</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Cashier</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($sales_history->num_rows > 0): ?>
                        <?php while($sale = $sales_history->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <a href="invoice.php?id=<?php echo $sale['sale_id']; ?>" class="text-decoration-none fw-bold" style="color: #6366f1;">
                                    #<?php echo $sale['invoice_no']; ?>
                                </a>
                            </td>
                            <td>
                                <div class="d-flex align-items-center" style="gap: 8px;">
                                    <div style="width: 28px; height: 28px; border-radius: 7px; background: linear-gradient(135deg, #e0e7ff, #c7d2fe); display: flex; align-items: center; justify-content: center; color: #4f46e5; font-weight: 600; font-size: 0.65rem;">
                                        <?php echo strtoupper(substr($sale['customer_name'] ?? 'W', 0, 1)); ?>
                                    </div>
                                    <?php echo $sale['customer_name'] ?: 'Walk-in'; ?>
                                </div>
                            </td>
                            <td class="text-muted" style="font-size: 0.85rem;"><?php echo format_date($sale['sale_date']); ?></td>
                            <td class="text-muted" style="font-size: 0.85rem;"><?php echo $sale['cashier']; ?></td>
                            <td class="text-end fw-bold" style="color: #059669;"><?php echo format_currency($sale['total_amount']); ?></td>
                            <td>
                                <?php if($sale['payment_status'] == 'paid'): ?>
                                    <span class="badge" style="background: rgba(16,185,129,0.1); color: #059669;">Paid</span>
                                <?php else: ?>
                                    <span class="badge" style="background: rgba(245,158,11,0.1); color: #f59e0b;"><?php echo ucfirst($sale['payment_status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="invoice.php?id=<?php echo $sale['sale_id']; ?>" class="btn btn-sm btn-info" title="View Invoice">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="invoice.php?id=<?php echo $sale['sale_id']; ?>&print=1" class="btn btn-sm btn-success" title="Print" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                                <a href="invoice.php?id=<?php echo $sale['sale_id']; ?>" class="btn btn-sm btn-danger" title="Download PDF" target="_blank" onclick="setTimeout(function(){ document.querySelector('.btn-danger[title=Download PDF]')?.click(); }, 500);">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-shopping-cart"></i>
                                    <p>No sales yet. Create your first sale!</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New Sale Modal -->
<div class="modal fade" id="saleModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form method="POST" id="saleForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-shopping-cart me-2 text-primary"></i>Create New Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Customer Selection -->
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Customer</label>
                            <div class="d-flex gap-2">
                                <select name="customer_id" id="customerSelect" class="form-select flex-grow-1">
                                    <option value="">Walk-in Customer</option>
                                    <?php while($cus = $customers->fetch_assoc()): ?>
                                        <option value="<?php echo $cus['customer_id']; ?>" data-phone="<?php echo $cus['phone']; ?>" data-email="<?php echo $cus['email']; ?>">
                                            <?php echo $cus['name']; ?> <?php echo $cus['phone'] ? "($cus[phone])" : ''; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#quickCustomerModal" title="Add New Customer">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                    </div>

                    <hr style="border-color: rgba(0,0,0,0.06);">

                    <!-- Items -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0" style="font-weight: 600;"><i class="fas fa-list me-1"></i> Items</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-item">
                            <i class="fas fa-plus me-1"></i>Add Item
                        </button>
                    </div>

                    <div id="sale-items">
                        <div class="row mb-2 item-row align-items-start">
                            <div class="col-md-5">
                                <select name="medicines[]" class="form-select med-select" required>
                                    <option value="">Select Medicine</option>
                                    <?php while($med = $medicines_list->fetch_assoc()): ?>
                                        <option value="<?php echo $med['medicine_id']; ?>" data-price="<?php echo $med['selling_price']; ?>" data-stock="<?php echo $med['quantity']; ?>" data-name="<?php echo $med['medicine_name']; ?>">
                                            <?php echo $med['medicine_name']; ?> - <?php echo format_currency($med['selling_price']); ?> (Stock: <?php echo $med['quantity']; ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="quantities[]" class="form-control qty-input" placeholder="Qty" min="1" value="1" required>
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="prices[]" class="form-control price-input" readonly placeholder="Price">
                            </div>
                            <div class="col-md-2">
                                <input type="text" class="form-control subtotal-input" readonly placeholder="Subtotal" style="background: #f1f5f9; font-weight: 600;">
                            </div>
                            <div class="col-md-1 text-center">
                                <button type="button" class="btn btn-sm btn-danger remove-item" title="Remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <hr style="border-color: rgba(0,0,0,0.06);">

                    <!-- Totals -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal:</span>
                                <span id="subtotalDisplay" class="fw-bold">0.00 $</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Discount:</span>
                                <input type="number" step="0.01" name="discount" id="discountInput" class="form-control form-control-sm" style="width: 120px; text-align: right;" value="0">
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Tax:</span>
                                <input type="number" step="0.01" name="tax" id="taxInput" class="form-control form-control-sm" style="width: 120px; text-align: right;" value="0">
                            </div>
                            <hr style="border-color: rgba(0,0,0,0.1);">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold" style="font-size: 1.1rem;">Total:</span>
                                <span id="totalDisplay" class="fw-bold" style="font-size: 1.3rem; color: #059669;">0.00 $</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" name="create_sale" class="btn btn-primary btn-lg">
                        <i class="fas fa-file-invoice me-1"></i>Generate Invoice
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Quick Customer Modal -->
<div class="modal fade" id="quickCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="fas fa-user-plus me-2 text-primary"></i>Quick Add Customer</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="new_cust_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="new_cust_phone" class="form-control">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Email</label>
                        <input type="email" name="new_cust_email" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_customer" class="btn btn-success btn-sm">
                        <i class="fas fa-plus me-1"></i>Add Customer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function calculateTotals() {
    let subtotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const qty = parseInt(row.querySelector('.qty-input').value) || 0;
        const sub = price * qty;
        row.querySelector('.subtotal-input').value = sub.toFixed(2) + ' $';
        subtotal += sub;
    });

    const discount = parseFloat(document.getElementById('discountInput').value) || 0;
    const tax = parseFloat(document.getElementById('taxInput').value) || 0;
    const total = subtotal - discount + tax;

    document.getElementById('subtotalDisplay').textContent = subtotal.toFixed(2) + ' $';
    document.getElementById('totalDisplay').textContent = total.toFixed(2) + ' $';
}

document.getElementById('add-item').addEventListener('click', () => {
    const row = document.querySelector('.item-row').cloneNode(true);
    row.querySelector('.qty-input').value = '1';
    row.querySelector('.price-input').value = '';
    row.querySelector('.subtotal-input').value = '';
    row.querySelector('.med-select').value = '';
    document.getElementById('sale-items').appendChild(row);
});

document.addEventListener('change', (e) => {
    if (e.target.classList.contains('med-select')) {
        const option = e.target.options[e.target.selectedIndex];
        const row = e.target.closest('.item-row');
        row.querySelector('.price-input').value = option.dataset.price || '';
        row.querySelector('.qty-input').max = option.dataset.stock || '';
        calculateTotals();
    }
    if (e.target.classList.contains('qty-input')) {
        calculateTotals();
    }
});

document.addEventListener('input', (e) => {
    if (e.target.id === 'discountInput' || e.target.id === 'taxInput') {
        calculateTotals();
    }
});

document.addEventListener('click', (e) => {
    if (e.target.closest('.remove-item')) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length > 1) {
            e.target.closest('.item-row').remove();
            calculateTotals();
        }
    }
});

// Update subtotal when modal opens
document.getElementById('saleModal').addEventListener('shown.bs.modal', calculateTotals);
</script>

<?php include 'includes/footer.php'; ?>
