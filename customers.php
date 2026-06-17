<?php
require_once "config/db.php";
require_once "includes/functions.php";
check_login();
check_role(["admin", "pharmacist"]);

// Handle Add/Edit
if (isset($_POST["save_customer"])) {
    $id = $_POST["id"];
    $name = trim($_POST["name"]);
    $phone = trim($_POST["phone"]);
    $email = trim($_POST["email"]);
    $address = trim($_POST["address"]);

    if (empty($name)) {
        redirect("customers.php", "Customer name is required!", "danger");
    }

    if ($id) {
        $stmt = $conn->prepare(
            "UPDATE customers SET name=?, phone=?, email=?, address=? WHERE customer_id=?",
        );
        $stmt->bind_param("ssssi", $name, $phone, $email, $address, $id);
        $msg = "Customer updated successfully!";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO customers (name, phone, email, address) VALUES (?, ?, ?, ?)",
        );
        $stmt->bind_param("ssss", $name, $phone, $email, $address);
        $msg = "Customer added successfully!";
    }

    try {
        $stmt->execute();
        $log_id = $id ?: $conn->insert_id;
        log_activity(
            $id ? "UPDATE" : "CREATE",
            "customers",
            $log_id,
            ($id ? "Updated" : "Added") . " customer: $name",
        );
        redirect("customers.php", $msg);
    } catch (Exception $e) {
        redirect("customers.php", "Error: " . $e->getMessage(), "danger");
    }
}

// Handle Delete
if (isset($_GET["delete"])) {
    $id = (int) $_GET["delete"];
    $stmt = $conn->prepare("DELETE FROM customers WHERE customer_id=?");
    $stmt->bind_param("i", $id);
    try {
        $stmt->execute();
        log_activity("DELETE", "customers", $id, "Deleted customer ID: $id");
        redirect("customers.php", "Customer deleted successfully!");
    } catch (Exception $e) {
        redirect(
            "customers.php",
            "Cannot delete customer: " . $e->getMessage(),
            "danger",
        );
    }
}

$customers = $conn->query("SELECT * FROM customers ORDER BY customer_id DESC");

include "includes/header.php";
include "includes/sidebar.php";
?>

<div class="page-header">
    <h2><i class="fas fa-users me-2 text-primary"></i>Customer Management</h2>
    <button class="btn btn-primary" id="addCustomerBtn" data-bs-toggle="modal" data-bs-target="#customerModal">
        <i class="fas fa-plus"></i> Add New Customer
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($customers->num_rows > 0): ?>
                        <?php while ($cus = $customers->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center" style="gap: 10px;">
                                    <div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #e0e7ff, #c7d2fe); display: flex; align-items: center; justify-content: center; color: #4f46e5; font-weight: 600; font-size: 0.75rem;">
                                        <?php echo strtoupper(
                                            substr($cus["name"], 0, 1),
                                        ); ?>
                                    </div>
                                    <span class="fw-bold"><?php echo $cus[
                                        "name"
                                    ]; ?></span>
                                </div>
                            </td>
                            <td><i class="fas fa-phone me-1 text-muted" style="font-size: 0.75rem;"></i><?php echo $cus[
                                "phone"
                            ]; ?></td>
                            <td><i class="fas fa-envelope me-1 text-muted" style="font-size: 0.75rem;"></i><?php echo $cus[
                                "email"
                            ]; ?></td>
                            <td class="text-muted"><?php echo $cus[
                                "address"
                            ]; ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-info edit-cus-btn" data-json='<?php echo htmlspecialchars(
                                    json_encode($cus),
                                    ENT_QUOTES,
                                    "UTF-8",
                                ); ?>' data-bs-toggle="modal" data-bs-target="#customerModal" title="Edit customer" aria-label="Edit customer">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <a href="customers.php?delete=<?php echo $cus[
                                    "customer_id"
                                ]; ?>" class="btn btn-sm btn-danger" data-confirm="Delete this customer permanently?" title="Delete customer" aria-label="Delete customer">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <p>No customers found</p>
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
<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-users me-2 text-primary"></i>Customer Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="cus_id">
                    <div class="mb-3">
                        <label class="form-label">Customer Name</label>
                        <input type="text" name="name" id="cus_name" class="form-control" placeholder="Enter customer name" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="cus_phone" class="form-control" placeholder="Enter phone number">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="cus_email" class="form-control" placeholder="Enter email address">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="cus_address" class="form-control" placeholder="Enter address"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" name="save_customer" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const customerModal = document.getElementById('customerModal');
const customerForm = customerModal.querySelector('form');

customerModal.addEventListener('show.bs.modal', (event) => {
    const trigger = event.relatedTarget;
    if (!trigger || !trigger.classList.contains('edit-cus-btn')) {
        customerForm.reset();
        document.getElementById('cus_id').value = '';
    }
});

document.querySelectorAll('.edit-cus-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const cus = JSON.parse(btn.dataset.json);
        document.getElementById('cus_id').value = cus.customer_id;
        document.getElementById('cus_name').value = cus.name || '';
        document.getElementById('cus_phone').value = cus.phone || '';
        document.getElementById('cus_email').value = cus.email || '';
        document.getElementById('cus_address').value = cus.address || '';
    });
});
</script>

<?php include "includes/footer.php"; ?>
