<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
check_login();

// Handle Add/Edit
if (isset($_POST['save_category'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $id = $_POST['id'];

    if (empty($name)) {
        redirect('categories.php', "Category name is required!", 'danger');
    }

    if ($id) {
        $stmt = $conn->prepare("UPDATE categories SET category_name=?, description=? WHERE category_id=?");
        $stmt->bind_param("ssi", $name, $description, $id);
        $msg = "Category updated successfully!";
    } else {
        $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        $msg = "Category added successfully!";
    }
    
    try {
        $stmt->execute();
        $log_id = $id ?: $conn->insert_id;
        log_activity($id ? 'UPDATE' : 'CREATE', 'categories', $log_id, ($id ? 'Updated' : 'Added') . " category: $name");
        redirect('categories.php', $msg);
    } catch (Exception $e) {
        redirect('categories.php', "Error: " . $e->getMessage(), 'danger');
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id=?");
    $stmt->bind_param("i", $id);
    try {
        $stmt->execute();
        log_activity('DELETE', 'categories', $id, "Deleted category ID: $id");
        redirect('categories.php', "Category deleted successfully!");
    } catch (Exception $e) {
        redirect('categories.php', "Cannot delete category: " . $e->getMessage(), 'danger');
    }
}

$categories = $conn->query("SELECT * FROM categories ORDER BY category_id DESC");

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="page-header">
    <h2><i class="fas fa-tags me-2 text-primary"></i>Category Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
        <i class="fas fa-plus"></i> Add New Category
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($categories->num_rows > 0): ?>
                        <?php while($cat = $categories->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge bg-light text-dark">#<?php echo $cat['category_id']; ?></span></td>
                            <td class="fw-bold"><?php echo $cat['category_name']; ?></td>
                            <td class="text-muted"><?php echo $cat['description']; ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-info edit-btn" 
                                        data-id="<?php echo $cat['category_id']; ?>" 
                                        data-name="<?php echo $cat['category_name']; ?>" 
                                        data-description="<?php echo $cat['description']; ?>"
                                        data-bs-toggle="modal" data-bs-target="#categoryModal" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <a href="categories.php?delete=<?php echo $cat['category_id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure?')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-tags"></i>
                                    <p>No categories found</p>
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
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-tags me-2 text-primary"></i>Category Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="cat_id">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="name" id="cat_name" class="form-control" placeholder="Enter category name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="cat_desc" class="form-control" placeholder="Enter description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" name="save_category" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('cat_id').value = btn.dataset.id;
        document.getElementById('cat_name').value = btn.dataset.name;
        document.getElementById('cat_desc').value = btn.dataset.description;
    });
});
</script>

<?php include 'includes/footer.php'; ?>
