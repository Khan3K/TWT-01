<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
check_login();

// Handle Add/Edit
if (isset($_POST['save_medicine'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
    $batch_no = trim($_POST['batch_no']);
    $expiry_date = $_POST['expiry_date'];
    $buy_price = (float)$_POST['buy_price'];
    $sell_price = (float)$_POST['sell_price'];
    $quantity = (int)$_POST['quantity'];
    $reorder_level = (int)$_POST['reorder_level'];

    if ($id) {
        $stmt = $conn->prepare("UPDATE medicines SET medicine_name=?, category_id=?, supplier_id=?, batch_no=?, expiry_date=?, purchase_price=?, selling_price=?, quantity=?, reorder_level=? WHERE medicine_id=?");
        $stmt->bind_param("siisssddii", $name, $category_id, $supplier_id, $batch_no, $expiry_date, $buy_price, $sell_price, $quantity, $reorder_level, $id);
        $msg = "Medicine updated successfully!";
    } else {
        $stmt = $conn->prepare("INSERT INTO medicines (medicine_name, category_id, supplier_id, batch_no, expiry_date, purchase_price, selling_price, quantity, reorder_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siisssddi", $name, $category_id, $supplier_id, $batch_no, $expiry_date, $buy_price, $sell_price, $quantity, $reorder_level);
        $msg = "Medicine added successfully!";
    }
    
    try {
        $stmt->execute();
        $log_id = $id ?: $conn->insert_id;
        log_activity($id ? 'UPDATE' : 'CREATE', 'medicines', $log_id, ($id ? 'Updated' : 'Added') . " medicine: $name");
        redirect('medicines.php', $msg);
    } catch (Exception $e) {
        redirect('medicines.php', "Error: " . $e->getMessage(), 'danger');
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM medicines WHERE medicine_id=?");
    $stmt->bind_param("i", $id);
    try {
        $stmt->execute();
        log_activity('DELETE', 'medicines', $id, "Deleted medicine ID: $id");
        redirect('medicines.php', "Medicine deleted successfully!");
    } catch (Exception $e) {
        redirect('medicines.php', "Cannot delete medicine: " . $e->getMessage(), 'danger');
    }
}

// Fetch Options
$categories = $conn->query("SELECT * FROM categories");
$suppliers = $conn->query("SELECT * FROM suppliers");

// Filter/Search
$where = "WHERE 1=1";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $s = $conn->real_escape_string($_GET['search']);
    $where .= " AND (m.medicine_name LIKE '%$s%' OR m.batch_no LIKE '%$s%')";
}
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $c = (int)$_GET['category'];
    $where .= " AND m.category_id = $c";
}

$medicines = $conn->query("SELECT m.*, c.category_name as cat_name, s.supplier_name as sup_name FROM medicines m LEFT JOIN categories c ON m.category_id = c.category_id LEFT JOIN suppliers s ON m.supplier_id = s.supplier_id $where ORDER BY m.medicine_id DESC");

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="page-header">
    <h2><i class="fas fa-pills me-2 text-primary"></i>Medicine Inventory</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#medicineModal">
        <i class="fas fa-plus"></i> Add New Medicine
    </button>
</div>

<!-- Search & Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form class="row g-3" method="GET">
            <div class="col-md-5">
                <div style="position: relative;">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or batch..." 
                           value="<?php echo $_GET['search'] ?? ''; ?>"
                           style="padding-left: 40px;">
                    <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                </div>
            </div>
            <div class="col-md-4">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php while($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $cat['category_id']) ? 'selected' : ''; ?>><?php echo $cat['category_name']; ?></option>
                    <?php endwhile; $categories->data_seek(0); ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
                <a href="medicines.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Medicines Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>Category</th>
                        <th>Supplier</th>
                        <th>Expiry</th>
                        <th>Stock</th>
                        <th class="text-end">Price</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($medicines->num_rows > 0): ?>
                        <?php while($med = $medicines->fetch_assoc()): ?>
                        <?php 
                        $isLow = ($med['quantity'] <= $med['reorder_level']);
                        $isExpired = (strtotime($med['expiry_date']) < time());
                        $rowClass = $isExpired ? 'table-danger' : ($isLow ? 'table-warning' : '');
                        ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td>
                                <div class="d-flex align-items-center" style="gap: 12px;">
                                    <div style="width: 40px; height: 40px; border-radius: 10px; background: <?php echo $isExpired ? 'rgba(239,68,68,0.1)' : ($isLow ? 'rgba(245,158,11,0.1)' : 'rgba(99,102,241,0.1)'); ?>; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-pills" style="color: <?php echo $isExpired ? '#ef4444' : ($isLow ? '#f59e0b' : '#6366f1'); ?>; font-size: 0.9rem;"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="font-size: 0.9rem;"><?php echo $med['medicine_name']; ?></div>
                                        <small class="text-muted">Batch: <?php echo $med['batch_no']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark"><?php echo $med['cat_name']; ?></span></td>
                            <td class="text-muted"><?php echo $med['sup_name']; ?></td>
                            <td>
                                <?php echo date('M d, Y', strtotime($med['expiry_date'])); ?>
                                <?php if($isExpired): ?>
                                    <br><small class="text-danger fw-bold">Expired</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fw-bold <?php echo $isLow ? 'text-warning' : 'text-success'; ?>">
                                    <?php echo $med['quantity']; ?>
                                </span>
                                <?php if($isLow): ?>
                                    <br><small class="text-danger">Low Stock</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-bold"><?php echo format_currency($med['selling_price']); ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-info edit-med-btn" data-json='<?php echo json_encode($med); ?>' data-bs-toggle="modal" data-bs-target="#medicineModal" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <a href="medicines.php?delete=<?php echo $med['medicine_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-pills"></i>
                                    <p>No medicines found</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="medicineModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-pills me-2 text-primary"></i>Medicine Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="med_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Medicine Name</label>
                            <input type="text" name="name" id="med_name" class="form-control" placeholder="Enter medicine name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" id="med_cat" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php while($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" id="med_sup" class="form-select">
                                <option value="">Select Supplier</option>
                                <?php while($sup = $suppliers->fetch_assoc()): ?>
                                    <option value="<?php echo $sup['supplier_id']; ?>"><?php echo $sup['supplier_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Batch No</label>
                            <input type="text" name="batch_no" id="med_batch" class="form-control" placeholder="Enter batch number">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" id="med_expiry" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reorder Level</label>
                            <input type="number" name="reorder_level" id="med_reorder" class="form-control" value="10">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Purchase Price</label>
                            <input type="number" step="0.01" name="buy_price" id="med_buy" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Selling Price</label>
                            <input type="number" step="0.01" name="sell_price" id="med_sell" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" id="med_qty" class="form-control" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" name="save_medicine" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.edit-med-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const med = JSON.parse(btn.dataset.json);
        document.getElementById('med_id').value = med.medicine_id;
        document.getElementById('med_name').value = med.medicine_name;
        document.getElementById('med_cat').value = med.category_id;
        document.getElementById('med_sup').value = med.supplier_id;
        document.getElementById('med_batch').value = med.batch_no;
        document.getElementById('med_expiry').value = med.expiry_date;
        document.getElementById('med_reorder').value = med.reorder_level;
        document.getElementById('med_buy').value = med.purchase_price;
        document.getElementById('med_sell').value = med.selling_price;
        document.getElementById('med_qty').value = med.quantity;
    });
});
</script>

<?php include 'includes/footer.php'; ?>
