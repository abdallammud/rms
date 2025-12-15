<?php
/**
 * Autoload core application files
 */

// Load database connection
require_once APP_PATH . '/config/database.php';

// Load helper functions
require_once APP_PATH . '/helpers/functions.php';

// Load authentication helper
require_once APP_PATH . '/helpers/auth.php';

// Load session helper
require_once APP_PATH . '/helpers/session.php';

/**
 * Load page based on routing
 */
function load_page($page, $action = 'index') {
    // Check if user is authenticated (except for login page)
    if ($page !== 'login' && !is_logged_in()) {
        header('Location: ?page=login');
        exit;
    }
    
    // Map pages to controllers
    $controller_map = [
        'dashboard' => 'dashboard',
        'login' => 'auth',
        'logout' => 'auth',
        'users' => 'user',
        'roles' => 'role',
        'branches' => 'branch',
        'remittances' => 'remittance',
        'settlements' => 'settlement',
        'settlement' => 'settlement',
        'commission' => 'commission',
        'banks' => 'bank',
        'bank' => 'bank',
        'bank_account' => 'bank_account',
        'reports' => 'report',
        'activity_log' => 'activity_log',
        'profile' => 'profile'
    ];
    
    // Load controller if exists
    if (isset($controller_map[$page])) {
        $controller_file = APP_PATH . '/controllers/' . $controller_map[$page] . '_controller.php';
        if (file_exists($controller_file)) {
            require_once $controller_file;
        }
    }
    
    // Load view
    $view_file = VIEW_PATH . '/' . $page . '.php';
    if (file_exists($view_file)) {
        require_once $view_file;
    } else {
        // 404 page
        require_once VIEW_PATH . '/404.php';
    }
}
