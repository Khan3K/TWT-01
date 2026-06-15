<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<nav id="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-hand-holding-medical"></i> <?php echo APP_NAME; ?></h3>
    </div>
    <ul class="list-unstyled components p-3">
        <li class="sidebar-label" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.35); padding: 8px 16px 4px; font-weight: 600;">MAIN</li>
        <li class="mb-1 <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
            <a href="dashboard.php" class="<?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>

        <li class="sidebar-label" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.35); padding: 16px 16px 4px; font-weight: 600;">INVENTORY</li>
        <li class="mb-1 <?php echo ($currentPage == 'medicines.php') ? 'active' : ''; ?>">
            <a href="medicines.php" class="<?php echo ($currentPage == 'medicines.php') ? 'active' : ''; ?>">
                <i class="fas fa-pills"></i> Medicines
            </a>
        </li>
        <li class="mb-1 <?php echo ($currentPage == 'categories.php') ? 'active' : ''; ?>">
            <a href="categories.php" class="<?php echo ($currentPage == 'categories.php') ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i> Categories
            </a>
        </li>
        <li class="mb-1 <?php echo ($currentPage == 'stock_transactions.php') ? 'active' : ''; ?>">
            <a href="stock_transactions.php" class="<?php echo ($currentPage == 'stock_transactions.php') ? 'active' : ''; ?>">
                <i class="fas fa-exchange-alt"></i> Stock Movements
            </a>
        </li>

        <li class="sidebar-label" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.35); padding: 16px 16px 4px; font-weight: 600;">BUSINESS</li>
        <li class="mb-1 <?php echo ($currentPage == 'sales.php') ? 'active' : ''; ?>">
            <a href="sales.php" class="<?php echo ($currentPage == 'sales.php') ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i> Sales / Billing
            </a>
        </li>
        <li class="mb-1 <?php echo ($currentPage == 'customers.php') ? 'active' : ''; ?>">
            <a href="customers.php" class="<?php echo ($currentPage == 'customers.php') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Customers
            </a>
        </li>
        <li class="mb-1 <?php echo ($currentPage == 'suppliers.php') ? 'active' : ''; ?>">
            <a href="suppliers.php" class="<?php echo ($currentPage == 'suppliers.php') ? 'active' : ''; ?>">
                <i class="fas fa-truck"></i> Suppliers
            </a>
        </li>

        <li class="sidebar-label" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.35); padding: 16px 16px 4px; font-weight: 600;">ANALYTICS</li>
        <li class="mb-1 <?php echo ($currentPage == 'reports.php') ? 'active' : ''; ?>">
            <a href="reports.php" class="<?php echo ($currentPage == 'reports.php') ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Reports
            </a>
        </li>
        <li class="mb-1 <?php echo ($currentPage == 'logs.php') ? 'active' : ''; ?>">
            <a href="logs.php" class="<?php echo ($currentPage == 'logs.php') ? 'active' : ''; ?>">
                <i class="fas fa-history"></i> Activity Logs
            </a>
        </li>

        <?php if($_SESSION['role'] == 'Admin'): ?>
        <li class="sidebar-label" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.35); padding: 16px 16px 4px; font-weight: 600;">SYSTEM</li>
        <li class="mb-1 <?php echo ($currentPage == 'users.php') ? 'active' : ''; ?>">
            <a href="users.php" class="<?php echo ($currentPage == 'users.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i> User Management
            </a>
        </li>
        <?php endif; ?>

        <hr style="border-color: rgba(255,255,255,0.08);">
        <li>
            <a href="logout.php" class="text-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</nav>

<div id="content">
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
        <div class="container-fluid">
            <button type="button" id="sidebarCollapse" class="btn btn-dark">
                <i class="fas fa-align-left"></i>
            </button>
            <div class="ms-auto d-flex align-items-center">
                <div class="me-3 d-flex align-items-center" style="gap: 8px;">
                    <div style="width: 34px; height: 34px; border-radius: 10px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 600; font-size: 0.8rem;">
                        <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                    </div>
                    <div style="line-height: 1.3;">
                        <div style="font-size: 0.85rem; font-weight: 600; color: var(--dark);"><?php echo $_SESSION['full_name']; ?></div>
                        <div style="font-size: 0.7rem; color: #94a3b8;"><?php echo $_SESSION['role']; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <div class="container-fluid p-4">
        <?php if(isset($_SESSION['msg'])): ?>
            <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show animate-fade-in" role="alert">
                <i class="fas fa-<?php echo ($_SESSION['msg_type'] == 'success') ? 'check-circle' : 'exclamation-circle'; ?> me-1"></i>
                <?php echo $_SESSION['msg']; unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
