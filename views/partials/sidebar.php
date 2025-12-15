<div class="sidebar collapse d-lg-block" id="sidebarMenu">
    <div class="sidebar-header">
        <h5><i class="bi bi-list-ul"></i> Navigation</h5>
    </div>
    <ul class="sidebar-menu">
        <li class="<?php echo ($_GET['page'] ?? 'dashboard') == 'dashboard' ? 'active' : ''; ?>">
            <a href="?page=dashboard">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <?php if (has_permission('view_remittances')): ?>
        <li class="<?php echo ($_GET['page'] ?? '') == 'remittances' ? 'active' : ''; ?>">
            <a href="?page=remittances">
                <i class="bi bi-currency-exchange"></i>
                <span>Remittances</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (has_permission('view_settlements')): ?>
        <!-- <li class="<?php echo ($_GET['page'] ?? '') == 'settlements' ? 'active' : ''; ?>">
            <a href="?page=settlements">
                <i class="bi bi-cash-coin"></i>
                <span>Settlements</span>
            </a>
        </li> -->
        <?php endif; ?>
        
        <?php if (has_permission('view_commission_tiers')): ?>
        <li class="<?php echo ($_GET['page'] ?? '') == 'commission' ? 'active' : ''; ?>">
            <a href="?page=commission">
                <i class="bi bi-percent"></i>
                <span>Commission Tiers</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (has_permission('view_bank_accounts')): ?>
        <li class="<?php echo ($_GET['page'] ?? '') == 'banks' ? 'active' : ''; ?>">
            <a href="?page=banks">
                <i class="bi bi-bank"></i>
                <span>Bank Accounts</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (has_permission('view_branches')): ?>
        <li class="<?php echo ($_GET['page'] ?? '') == 'branches' ? 'active' : ''; ?>">
            <a href="?page=branches">
                <i class="bi bi-building"></i>
                <span>Branches</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (has_permission('view_users')): ?>
        <li class="<?php echo ($_GET['page'] ?? '') == 'users' ? 'active' : ''; ?>">
            <a href="?page=users">
                <i class="bi bi-people"></i>
                <span>Users</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (has_permission('view_roles')): ?>
        <li class="<?php echo ($_GET['page'] ?? '') == 'roles' ? 'active' : ''; ?>">
            <a href="?page=roles">
                <i class="bi bi-shield-check"></i>
                <span>Roles & Permissions</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (has_permission('view_reports')): ?>
        <li class="<?php echo ($_GET['page'] ?? '') == 'reports' ? 'active' : ''; ?>">
            <a href="?page=reports">
                <i class="bi bi-graph-up"></i>
                <span>Reports</span>
            </a>
        </li>
        <?php endif; ?>
        
        <li class="<?php echo ($_GET['page'] ?? '') == 'activity-log' ? 'active' : ''; ?>">
            <a href="?page=activity-log">
                <i class="bi bi-clock-history"></i>
                <span>Activity Log</span>
            </a>
        </li>
    </ul>
</div>
