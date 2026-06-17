<?php
require_once "config/db.php";
require_once "includes/functions.php";
check_login();
check_role(["admin"]);

// Pagination
$page = max(1, (int) ($_GET["page"] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filters
$where = "WHERE 1=1";
if (!empty($_GET["action"])) {
    $action = $conn->real_escape_string($_GET["action"]);
    $where .= " AND al.action = '$action'";
}
if (!empty($_GET["user_id"])) {
    $uid = (int) $_GET["user_id"];
    $where .= " AND al.user_id = $uid";
}
if (!empty($_GET["date_from"])) {
    $df = $conn->real_escape_string($_GET["date_from"]);
    $where .= " AND al.created_at >= '$df 00:00:00'";
}
if (!empty($_GET["date_to"])) {
    $dt = $conn->real_escape_string($_GET["date_to"]);
    $where .= " AND al.created_at <= '$dt 23:59:59'";
}

try {
    $total = $conn
        ->query("SELECT COUNT(*) FROM activity_logs al $where")
        ->fetch_row()[0];
} catch (Exception $e) {
    $total = 0;
}

try {
    $logs = $conn->query(
        "SELECT al.*, u.full_name FROM activity_logs al LEFT JOIN users u ON al.user_id = u.user_id $where ORDER BY al.created_at DESC LIMIT $per_page OFFSET $offset",
    );
} catch (Exception $e) {
    $logs = false;
}

$total_pages = ceil($total / $per_page);

// For filter dropdowns
$all_users = $conn->query(
    "SELECT user_id, full_name FROM users ORDER BY full_name",
);

include "includes/header.php";
include "includes/sidebar.php";
?>

<div class="page-header">
    <h2><i class="fas fa-history me-2 text-primary"></i>Activity Logs</h2>
    <div class="d-flex gap-2">
        <a href="logs.php" class="btn btn-secondary"><i class="fas fa-sync me-1"></i>Refresh</a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form class="row g-3" method="GET">
            <div class="col-md-2">
                <select name="action" class="form-select">
                    <option value="">All Actions</option>
                    <option value="LOGIN" <?php echo ($_GET["action"] ?? "") ==
                    "LOGIN"
                        ? "selected"
                        : ""; ?>>Login</option>
                    <option value="LOGOUT" <?php echo ($_GET["action"] ?? "") ==
                    "LOGOUT"
                        ? "selected"
                        : ""; ?>>Logout</option>
                    <option value="CREATE" <?php echo ($_GET["action"] ?? "") ==
                    "CREATE"
                        ? "selected"
                        : ""; ?>>Create</option>
                    <option value="UPDATE" <?php echo ($_GET["action"] ?? "") ==
                    "UPDATE"
                        ? "selected"
                        : ""; ?>>Update</option>
                    <option value="DELETE" <?php echo ($_GET["action"] ?? "") ==
                    "DELETE"
                        ? "selected"
                        : ""; ?>>Delete</option>
                    <option value="SALE" <?php echo ($_GET["action"] ?? "") ==
                    "SALE"
                        ? "selected"
                        : ""; ?>>Sale</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="user_id" class="form-select">
                    <option value="">All Users</option>
                    <?php while ($u = $all_users->fetch_assoc()): ?>
                        <option value="<?php echo $u[
                            "user_id"
                        ]; ?>" <?php echo ($_GET["user_id"] ?? "") ==
$u["user_id"]
    ? "selected"
    : ""; ?>><?php echo $u["full_name"]; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="<?php echo $_GET[
                    "date_from"
                ] ?? ""; ?>" placeholder="From">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="<?php echo $_GET[
                    "date_to"
                ] ?? ""; ?>" placeholder="To">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i>Filter</button>
            </div>
            <div class="col-md-2">
                <a href="logs.php" class="btn btn-outline-secondary w-100"><i class="fas fa-times me-1"></i>Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs && $logs->num_rows > 0): ?>
                        <?php while ($log = $logs->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div style="font-size: 0.85rem;"><?php echo format_datetime(
                                    $log["created_at"],
                                ); ?></div>
                                <small class="text-muted"><?php echo time_ago(
                                    $log["created_at"],
                                ); ?></small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center" style="gap: 8px;">
                                    <div style="width: 28px; height: 28px; border-radius: 7px; background: linear-gradient(135deg, #e0e7ff, #c7d2fe); display: flex; align-items: center; justify-content: center; color: #4f46e5; font-weight: 600; font-size: 0.65rem;">
                                        <?php echo strtoupper(
                                            substr(
                                                $log["full_name"] ?? "S",
                                                0,
                                                1,
                                            ),
                                        ); ?>
                                    </div>
                                    <?php echo $log["full_name"] ?? "System"; ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                $colors = [
                                    "LOGIN" => [
                                        "bg" => "rgba(16,185,129,0.1)",
                                        "color" => "#059669",
                                        "icon" => "fa-sign-in-alt",
                                    ],
                                    "LOGOUT" => [
                                        "bg" => "rgba(148,163,184,0.1)",
                                        "color" => "#64748b",
                                        "icon" => "fa-sign-out-alt",
                                    ],
                                    "CREATE" => [
                                        "bg" => "rgba(99,102,241,0.1)",
                                        "color" => "#6366f1",
                                        "icon" => "fa-plus",
                                    ],
                                    "UPDATE" => [
                                        "bg" => "rgba(14,165,233,0.1)",
                                        "color" => "#0ea5e9",
                                        "icon" => "fa-pen",
                                    ],
                                    "DELETE" => [
                                        "bg" => "rgba(239,68,68,0.1)",
                                        "color" => "#ef4444",
                                        "icon" => "fa-trash",
                                    ],
                                    "SALE" => [
                                        "bg" => "rgba(16,185,129,0.1)",
                                        "color" => "#059669",
                                        "icon" => "fa-shopping-cart",
                                    ],
                                ];
                                $c = $colors[$log["action"]] ?? [
                                    "bg" => "rgba(148,163,184,0.1)",
                                    "color" => "#64748b",
                                    "icon" => "fa-circle",
                                ];
                                ?>
                                <span class="badge" style="background: <?php echo $c[
                                    "bg"
                                ]; ?>; color: <?php echo $c[
    "color"
]; ?>; padding: 5px 10px;">
                                    <i class="fas <?php echo $c[
                                        "icon"
                                    ]; ?> me-1" style="font-size: 0.7rem;"></i><?php echo $log[
     "action"
 ]; ?>
                                </span>
                            </td>
                            <td class="text-muted" style="max-width: 300px;">
                                <?php echo $log["table_name"]
                                    ? '<span class="badge bg-light text-dark me-1">' .
                                        $log["table_name"] .
                                        "</span>"
                                    : ""; ?>
                                <?php echo $log["description"]; ?>
                                <?php if ($log["record_id"]): ?>
                                    <small class="text-muted">(ID: <?php echo $log[
                                        "record_id"
                                    ]; ?>)</small>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?php echo $log[
                                "ip_address"
                            ]; ?></small></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-history"></i>
                                    <p>No activity logs found</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_pages > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Showing <?php echo $offset +
            1; ?>-<?php echo min(
    $offset + $per_page,
    $total,
); ?> of <?php echo $total; ?> logs</small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for (
                    $i = max(1, $page - 2);
                    $i <= min($total_pages, $page + 2);
                    $i++
                ): ?>
                    <li class="page-item <?php echo $i == $page
                        ? "active"
                        : ""; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(
                            array_merge($_GET, ["page" => $i]),
                        ); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php include "includes/footer.php"; ?>
