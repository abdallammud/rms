<?php
session_start();

// Base path configuration
define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');
define('VIEW_PATH', BASE_PATH . '/views');

// Auto-load core files
require APP_PATH . '/autoload.php';
require APP_PATH . '/sms_helper.php';
// Handle routing
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

// Load the appropriate view/controller
load_page($page, $action);
