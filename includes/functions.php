<?php
// Sanitize input
function sanitize($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

// Check if user is logged in
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

// Check role access
function check_role($allowed_roles) {
    if (!in_array(strtolower($_SESSION['role']), array_map('strtolower', $allowed_roles))) {
        $_SESSION['msg'] = "Access Denied! You do not have permission to view this page.";
        $_SESSION['msg_type'] = 'danger';
        header("Location: dashboard.php");
        exit();
    }
}

// Format currency
function format_currency($amount) {
    return number_format((float)$amount, 2) . " $";
}

// Redirect with message
function redirect($url, $msg, $type = 'success') {
    $_SESSION['msg'] = $msg;
    $_SESSION['msg_type'] = $type;
    header("Location: $url");
    exit();
}

// Log activity to database
function log_activity($action, $table_name = null, $record_id = null, $description = null) {
    global $conn;
    $user_id = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    try {
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ississ", $user_id, $action, $table_name, $record_id, $description, $ip);
        $stmt->execute();
    } catch (Exception $e) {
        // Silently fail - table may not exist yet
    }
}

// Get user's display name
function get_user_name($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['full_name'] : 'System';
}

// Format date for display
function format_date($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

// Format datetime for display
function format_datetime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}

// Get time ago
function time_ago($datetime) {
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

// Generate invoice number
function generate_invoice_no() {
    return 'INV-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}
?>
