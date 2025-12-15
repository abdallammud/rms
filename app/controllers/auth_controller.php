<?php
/**
 * Authentication Controller
 * Handles login, logout, 2FA, and session management
 */

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'login':
            handle_login();
            break;
        case 'verify_otp':
            handle_otp_verification();
            break;
        case 'resend_otp':
            handle_resend_otp();
            break;
        default:
            send_json(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

// Handle logout - check for page=logout
if (isset($_GET['page']) && $_GET['page'] === 'logout') {
    handle_logout();
    exit;
}

/**
 * Handle user login
 */
function handle_login() {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($username) || empty($password)) {
        send_json(['success' => false, 'message' => 'Username and password are required']);
    }
    
    // Check user credentials
    $sql = "SELECT u.*, r.role_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            WHERE u.username = '" . db_escape($username) . "' 
            LIMIT 1";
    
    $user = db_query_row($sql);
    
    if (!$user) {
        // Log failed attempt
        log_failed_login($username, 'User not found');
        send_json(['success' => false, 'message' => 'Invalid username or password']);
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        // Log failed attempt
        log_failed_login($username, 'Invalid password');
        send_json(['success' => false, 'message' => 'Invalid username or password']);
    }
    
    // Check if user is active
    if ($user['is_active'] != 1) {
        send_json(['success' => false, 'message' => 'Your account is inactive. Contact administrator.']);
    }
    
    // Check if user is suspended
    if ($user['is_suspended'] == 1) {
        send_json(['success' => false, 'message' => 'Your account has been suspended. Contact administrator.']);
    }
    
    // Check if 2FA is enabled
    if ($user['two_fa_enabled'] == 1) {
        // Generate and send OTP
        $otp = generate_otp(6);
        $expires_at = date('Y-m-d H:i:s', strtotime('+5 days'));
        
        // Store OTP in database
        $sql = "INSERT INTO otp_codes (user_id, otp_code, purpose, expires_at) 
                VALUES ('{$user['id']}', '$otp', 'login', '$expires_at')";
        db_query($sql);
        
        // Send OTP via SMS using SMSManager
        $message = "Your OTP code is: $otp. Valid for 5 minutes.";
        $smsResult = null;
        if (!empty($user['phone'])) {
            global $SMSManager;
            if (isset($SMSManager) && method_exists($SMSManager, 'sendSMS')) {
                $smsResult = $SMSManager->sendSMS($user['phone'], $message);
            } else {
                $smsResult = 'sms_not_configured';
            }
        } else {
            $smsResult = 'no_phone_number';
        }

        if ($smsResult !== 'sent') {
            // Failed to send SMS, return an error so user can try again
            send_json(['success' => false, 'message' => 'Failed to send OTP: ' . $smsResult]);
        }
        
        // Store user ID in session for OTP verification
        $_SESSION['pending_user_id'] = $user['id'];
        $_SESSION['pending_2fa'] = true;
        
        send_json([
            'success' => true,
            'requires_2fa' => true,
            'message' => 'OTP sent to your registered phone number',
            'phone_masked' => mask_phone($user['phone'])
        ]);
    }
    
    // No 2FA - log user in directly
    complete_login($user);
}

/**
 * Handle OTP verification
 */
function handle_otp_verification() {
    $otp = sanitize($_POST['otp'] ?? '');
    
    if (!isset($_SESSION['pending_user_id'])) {
        send_json(['success' => false, 'message' => 'Invalid session']);
    }
    
    $user_id = $_SESSION['pending_user_id'];
    
    // Verify OTP
    $sql = "SELECT * FROM otp_codes 
            WHERE user_id = '$user_id' 
            AND otp_code = '" . db_escape($otp) . "' 
            AND purpose = 'login' 
            AND is_used = 0 
            AND expires_at > NOW() 
            ORDER BY id DESC 
            LIMIT 1";
    
    $otp_record = db_query_row($sql);
    
    if (!$otp_record) {
        send_json(['success' => false, 'message' => 'Invalid or expired OTP']);
    }
    
    // Mark OTP as used
    db_query("UPDATE otp_codes SET is_used = 1 WHERE id = '{$otp_record['id']}'");
    
    // Get user data
    $sql = "SELECT u.*, r.role_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            WHERE u.id = '$user_id' 
            LIMIT 1";
    
    $user = db_query_row($sql);
    
    // Clear pending session
    unset($_SESSION['pending_user_id']);
    unset($_SESSION['pending_2fa']);
    
    // Complete login
    complete_login($user);
}

/**
 * Resend OTP
 */
function handle_resend_otp() {
    if (!isset($_SESSION['pending_user_id'])) {
        send_json(['success' => false, 'message' => 'Invalid session']);
    }
    
    $user_id = $_SESSION['pending_user_id'];
    
    // Get user phone
    $user = db_query_row("SELECT phone FROM users WHERE id = '$user_id'");
    
    // Generate new OTP
    $otp = generate_otp(6);
    $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    // Store OTP
    $sql = "INSERT INTO otp_codes (user_id, otp_code, purpose, expires_at) 
            VALUES ('$user_id', '$otp', 'login', '$expires_at')";
    db_query($sql);
    
    // Send OTP via SMS
    $message = "Your OTP code is: $otp. Valid for 5 minutes.";
    $smsResult = null;
    if (!empty($user['phone'])) {
        global $SMSManager;
        if (isset($SMSManager) && method_exists($SMSManager, 'sendSMS')) {
            $smsResult = $SMSManager->sendSMS($user['phone'], $message);
        } else {
            $smsResult = 'sms_not_configured';
        }
    } else {
        $smsResult = 'no_phone_number';
    }

    if ($smsResult !== 'sent') {
        send_json(['success' => false, 'message' => 'Failed to resend OTP: ' . $smsResult]);
    }
    
    send_json([
        'success' => true,
        'message' => 'OTP has been resent to your phone',
        'phone_masked' => mask_phone($user['phone'])
    ]);
}

/**
 * Complete login and set session
 */
function complete_login($user) {
    // Load user permissions
    $sql = "SELECT p.permission_code 
            FROM role_permissions rp 
            LEFT JOIN permissions p ON rp.permission_id = p.id 
            WHERE rp.role_id = '{$user['role_id']}'";
    
    $permissions_result = db_query($sql);
    $permissions = [];
    while ($row = mysqli_fetch_assoc($permissions_result)) {
        $permissions[] = $row['permission_code'];
    }
    
    // Set session data
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_name'];
    $_SESSION['permissions'] = $permissions;
    $_SESSION['user_data'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'role_name' => $user['role_name'],
        'branch_id' => $user['branch_id']
    ];
    $_SESSION['last_activity'] = time();
    
    // Update last login
    db_query("UPDATE users SET last_login = NOW() WHERE id = '{$user['id']}'");
    
    // Log successful login
    log_activity($user['id'], 'login', 'User logged in successfully', 'auth');
    
    send_json([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => '?page=dashboard'
    ]);
}

/**
 * Handle logout
 */
function handle_logout() {
    if (isset($_SESSION['user_id'])) {
        log_activity($_SESSION['user_id'], 'logout', 'User logged out', 'auth');
    }
    
    session_unset();
    session_destroy();
    redirect('login');
}

/**
 * Log failed login attempt
 */
function log_failed_login($username, $reason) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $sql = "INSERT INTO activity_log (action, description, ip_address, user_agent, created_at) 
            VALUES ('failed_login', 
                    'Failed login attempt for username: " . db_escape($username) . " - Reason: " . db_escape($reason) . "', 
                    '" . db_escape($ip) . "', 
                    '" . db_escape($user_agent) . "', 
                    NOW())";
    
    db_query($sql);
}

/**
 * Mask phone number
 */
function mask_phone($phone) {
    if (empty($phone)) return 'N/A';
    $length = strlen($phone);
    if ($length > 4) {
        return substr($phone, 0, 3) . str_repeat('*', $length - 6) . substr($phone, -3);
    }
    return $phone;
}
