<?php
require_once "config/db.php";
require_once "includes/functions.php";
check_login();
check_role(["admin"]);

// Handle Add/Edit
if (isset($_POST["save_supplier"])) {
    $id = $_POST["id"];
    $name = trim($_POST["name"]);
    $contact = trim($_POST["contact_person"]);
    $phone = trim($_POST["phone"]);
    $email = trim($_POST["email"]);
    $address = trim($_POST["address"]);

    if (empty($name)) {
        redirect("suppliers.php", "Supplier name is required!", "danger");
    }

    if ($id) {
        $stmt = $conn->prepare(
            "UPDATE suppliers SET supplier_name=?, contact_person=?, phone=?, email=?, address=? WHERE supplier_id=?",
        );
        $stmt->bind_param(
            "sssssi",
            $name,
            $contact,
            $phone,
            $email,
            $address,
            $id,
        );
        $msg = "Supplier updated successfully!";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO suppliers (supplier_name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?)",
        );
        $stmt->bind_param("sssss", $name, $contact, $phone, $email, $address);
        $msg = "Supplier added successfully!";
    }

    try {
        $stmt->execute();
        $log_id = $id ?: $conn->insert_id;
        log_activity(
            $id ? "UPDATE" : "CREATE",
            "suppliers",
            $log_id,
            ($id ? "Updated" : "Added") . " supplier: $name",
        );
        redirect("suppliers.php", $msg);
    } catch (Exception $e) {
        redirect("suppliers.php", "Error: " . $e->getMessage(), "danger");
    }
}

// Handle Delete
if (isset($_GET["delete"])) {
    $id = (int) $_GET["delete"];
    $stmt = $conn->prepare("DELETE FROM suppliers WHERE supplier_id=?");
    $stmt->bind_param("i", $id);
    try {
        $stmt->execute();
        log_activity("DELETE", "suppliers", $id, "Deleted supplier ID: $id");
        redirect("suppliers.php", "Supplier deleted successfully!");
    } catch (Exception $e) {
        redirect(
            "suppliers.php",
            "Cannot delete supplier: " . $e->getMessage(),
            "danger",
        );
    }
}

$suppliers = $conn->query("SELECT * FROM suppliers ORDER BY supplier_id DESC");

include "includes/header.php";
include "includes/sidebar.php";
?>

<div class="page-header">
    <h2><i class="fas fa-truck me-2 text-primary"></i>Supplier Management</h2>
    <button class="btn btn-primary" id="addSupplierBtn" data-bs-toggle="modal" data-bs-target="#supplierModal">
        <i class="fas fa-plus"></i> Add New Supplier
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($suppliers->num_rows > 0): ?>
                        <?php while ($sup = $suppliers->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center" style="gap: 10px;">
                                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(14, 165, 233, 0.1); display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-truck" style="color: var(--secondary); font-size: 0.85rem;"></i>
                                    </div>
                                    <span class="fw-bold"><?php echo $sup[
                                        "supplier_name"
                                    ]; ?></span>
                                </div>
                            </td>
                            <td class="text-muted"><?php echo $sup[
                                "contact_person"
                            ]; ?></td>
                            <td><i class="fas fa-phone me-1 text-muted" style="font-size: 0.75rem;"></i><?php echo $sup[
                                "phone"
                            ]; ?></td>
                            <td><i class="fas fa-envelope me-1 text-muted" style="font-size: 0.75rem;"></i><?php echo $sup[
                                "email"
                            ]; ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-info edit-sup-btn" data-json='<?php echo htmlspecialchars(
                                    json_encode($sup),
                                    ENT_QUOTES,
                                    "UTF-8",
                                ); ?>' data-bs-toggle="modal" data-bs-target="#supplierModal" title="Edit supplier" aria-label="Edit supplier">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <a href="suppliers.php?delete=<?php echo $sup[
                                    "supplier_id"
                                ]; ?>" class="btn btn-sm btn-danger" data-confirm="Delete this supplier permanently?" title="Delete supplier" aria-label="Delete supplier">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-truck"></i>
                                    <p>No suppliers found</p>
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
<div class="modal fade" id="supplierModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-truck me-2 text-primary"></i>Supplier Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="sup_id">
                    <div class="mb-3">
                        <label class="form-label">Supplier Name</label>
                        <input type="text" name="name" id="sup_name" class="form-control" placeholder="Enter supplier name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" id="sup_contact" class="form-control" placeholder="Enter contact person">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="sup_phone" class="form-control" placeholder="Enter phone number">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="sup_email" class="form-control" placeholder="Enter email address">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="sup_address" class="form-control" placeholder="Enter address"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" name="save_supplier" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const supplierModal = document.getElementById('supplierModal');
const supplierForm = supplierModal.querySelector('form');

supplierModal.addEventListener('show.bs.modal', (event) => {
    const trigger = event.relatedTarget;
    if (!trigger || !trigger.classList.contains('edit-sup-btn')) {
        supplierForm.reset();
        document.getElementById('sup_id').value = '';
    }
});

document.querySelectorAll('.edit-sup-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const sup = JSON.parse(btn.dataset.json);
        document.getElementById('sup_id').value = sup.supplier_id;
        document.getElementById('sup_name').value = sup.supplier_name || '';
        document.getElementById('sup_contact').value = sup.contact_person || '';
        document.getElementById('sup_phone').value = sup.phone || '';
        document.getElementById('sup_email').value = sup.email || '';
        document.getElementById('sup_address').value = sup.address || '';
    });
});
</script>

<?php include "includes/footer.php"; ?>
