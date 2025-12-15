<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container-fluid">
        <button class="navbar-toggler me-2" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <a class="navbar-brand" href="?page=dashboard">
            <i class="bi bi-cash-stack"></i> <span class="brand-text">RMS</span>
        </a>
        
        <div class="d-flex align-items-center ms-auto">
            <!-- Notifications -->
            <div class="dropdown me-3">
                <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        3
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end notification-dropdown">
                    <li class="dropdown-header">Notifications</li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#">
                        <i class="bi bi-flag text-warning"></i> New large transfer flagged
                        <small class="text-muted d-block">2 mins ago</small>
                    </a></li>
                    <li><a class="dropdown-item" href="#">
                        <i class="bi bi-check-circle text-success"></i> Settlement approved
                        <small class="text-muted d-block">1 hour ago</small>
                    </a></li>
                    <li><a class="dropdown-item" href="#">
                        <i class="bi bi-person-plus text-info"></i> New user registered
                        <small class="text-muted d-block">3 hours ago</small>
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-center small" href="#">View all notifications</a></li>
                </ul>
            </div>
            
            <!-- User Menu -->
            <div class="dropdown">
                <a class="nav-link d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar">
                        <?php 
                        $user = get_current_user();
                        $initials = '';
                        if ($user && isset($user['full_name'])) {
                            $names = explode(' ', $user['full_name']);
                            $initials = strtoupper(substr($names[0], 0, 1));
                            if (isset($names[1])) {
                                $initials .= strtoupper(substr($names[1], 0, 1));
                            }
                        } else {
                            $initials = 'U';
                        }
                        echo $initials;
                        ?>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end user-dropdown">
                    <li class="dropdown-header">
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-3">
                                <?php echo $initials; ?>
                            </div>
                            <div>
                                <div class="fw-bold"><?php echo $user['full_name'] ?? 'User'; ?></div>
                                <small class="text-muted">@<?php echo $user['username'] ?? 'username'; ?></small>
                                <div><span class="badge bg-primary mt-1"><?php echo get_user_role(); ?></span></div>
                            </div>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="?page=profile"><i class="bi bi-person"></i> My Profile</a></li>
                    <li><a class="dropdown-item" href="?page=settings"><i class="bi bi-gear"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="?page=logout"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
