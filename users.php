<?php
require_once "config/db.php";
require_once "includes/functions.php";
check_login();
check_role(["admin"]);

// Handle Add/Edit
if (isset($_POST["save_user"])) {
    $id = $_POST["id"];
    $username = trim($_POST["username"]);
    $full_name = trim($_POST["full_name"]);
    $role = $_POST["role"];
    $password = $_POST["password"];

    if (empty($username) || empty($full_name)) {
        redirect("users.php", "Username and full name are required!", "danger");
    }

    if ($id) {
        if (!empty($password)) {
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                "UPDATE users SET username=?, full_name=?, role=?, password=? WHERE user_id=?",
            );
            $stmt->bind_param(
                "ssssi",
                $username,
                $full_name,
                $role,
                $hashed_pass,
                $id,
            );
        } else {
            $stmt = $conn->prepare(
                "UPDATE users SET username=?, full_name=?, role=? WHERE user_id=?",
            );
            $stmt->bind_param("sssi", $username, $full_name, $role, $id);
        }
        $msg = "User updated successfully!";
    } else {
        if (empty($password)) {
            redirect(
                "users.php",
                "Password is required for new users!",
                "danger",
            );
        }
        $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            "INSERT INTO users (username, full_name, role, password) VALUES (?, ?, ?, ?)",
        );
        $stmt->bind_param("ssss", $username, $full_name, $role, $hashed_pass);
        $msg = "User added successfully!";
    }

    try {
        $stmt->execute();
        $log_id = $id ?: $conn->insert_id;
        log_activity(
            $id ? "UPDATE" : "CREATE",
            "users",
            $log_id,
            ($id ? "Updated" : "Added") . " user: $full_name ($username)",
        );
        redirect("users.php", $msg);
    } catch (Exception $e) {
        $errMsg = $e->getMessage();
        if (
            strpos($errMsg, "Duplicate") !== false ||
            strpos($errMsg, "1062") !== false
        ) {
            redirect("users.php", "Username already exists!", "danger");
        } else {
            redirect("users.php", "Error: " . $errMsg, "danger");
        }
    }
}

// Handle Delete
if (isset($_GET["delete"])) {
    $id = (int) $_GET["delete"];
    if ($id != $_SESSION["user_id"]) {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
        $stmt->bind_param("i", $id);
        try {
            $stmt->execute();
            log_activity("DELETE", "users", $id, "Deleted user ID: $id");
            redirect("users.php", "User deleted successfully!");
        } catch (Exception $e) {
            redirect(
                "users.php",
                "Cannot delete user: " . $e->getMessage(),
                "danger",
            );
        }
    } else {
        redirect("users.php", "You cannot delete yourself!", "danger");
    }
}

$users = $conn->query("SELECT * FROM users ORDER BY user_id DESC");

include "includes/header.php";
include "includes/sidebar.php";
?>

<div class="page-header">
    <h2><i class="fas fa-user-shield me-2 text-primary"></i>User Management</h2>
    <button class="btn btn-primary" id="addUserBtn" data-bs-toggle="modal" data-bs-target="#userModal">
        <i class="fas fa-plus"></i> Add New User
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users->num_rows > 0): ?>
                        <?php while ($u = $users->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center" style="gap: 10px;">
                                    <div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, <?php echo $u[
                                        "role"
                                    ] == "Admin"
                                        ? "#818cf8, #6366f1"
                                        : ($u["role"] == "Manager"
                                            ? "#34d399, #10b981"
                                            : "#38bdf8, #0ea5e9"); ?>); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 600; font-size: 0.75rem;">
                                        <?php echo strtoupper(
                                            substr($u["full_name"], 0, 1),
                                        ); ?>
                                    </div>
                                    <span class="fw-bold"><?php echo $u[
                                        "full_name"
                                    ]; ?></span>
                                </div>
                            </td>
                            <td class="text-muted"><?php echo $u[
                                "username"
                            ]; ?></td>
                            <td>
                                <?php if ($u["role"] == "Admin"): ?>
                                    <span class="badge" style="background: rgba(99,102,241,0.1); color: #6366f1;">
                                        <i class="fas fa-shield-alt me-1" style="font-size: 0.7rem;"></i><?php echo $u[
                                            "role"
                                        ]; ?>
                                    </span>
                                <?php elseif ($u["role"] == "Manager"): ?>
                                    <span class="badge" style="background: rgba(16,185,129,0.1); color: #059669;">
                                        <i class="fas fa-user-tie me-1" style="font-size: 0.7rem;"></i><?php echo $u[
                                            "role"
                                        ]; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background: rgba(14,165,233,0.1); color: #0ea5e9;">
                                        <i class="fas fa-user me-1" style="font-size: 0.7rem;"></i><?php echo $u[
                                            "role"
                                        ]; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-info edit-user-btn" data-json='<?php echo htmlspecialchars(
                                    json_encode($u),
                                    ENT_QUOTES,
                                    "UTF-8",
                                ); ?>' data-bs-toggle="modal" data-bs-target="#userModal" title="Edit user" aria-label="Edit user">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <?php if (
                                    $u["user_id"] != $_SESSION["user_id"]
                                ): ?>
                                <a href="users.php?delete=<?php echo $u[
                                    "user_id"
                                ]; ?>" class="btn btn-sm btn-danger" data-confirm="Delete this user permanently?" title="Delete user" aria-label="Delete user">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-user-shield"></i>
                                    <p>No users found</p>
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
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-shield me-2 text-primary"></i>User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="user_id">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" id="user_name" class="form-control" placeholder="Enter username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="user_full" class="form-control" placeholder="Enter full name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" id="user_role" class="form-select">
                            <option value="Admin">Admin</option>
                            <option value="Pharmacist">Pharmacist</option>
                            <option value="Manager">Manager</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <small class="text-muted">(Leave blank to keep current)</small></label>
                        <input type="password" name="password" class="form-control" placeholder="Enter new password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" name="save_user" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const userModal = document.getElementById('userModal');
const userForm = userModal.querySelector('form');

userModal.addEventListener('show.bs.modal', (event) => {
    const trigger = event.relatedTarget;
    if (!trigger || !trigger.classList.contains('edit-user-btn')) {
        userForm.reset();
        document.getElementById('user_id').value = '';
        document.getElementById('user_role').value = 'Pharmacist';
    }
});

document.querySelectorAll('.edit-user-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const u = JSON.parse(btn.dataset.json);
        document.getElementById('user_id').value = u.user_id;
        document.getElementById('user_name').value = u.username || '';
        document.getElementById('user_full').value = u.full_name || '';
        document.getElementById('user_role').value = u.role || 'Pharmacist';
    });
});
</script>

<?php include "includes/footer.php"; ?>
