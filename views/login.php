<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border-radius: 1rem;
            box-shadow: 0 1rem 3rem rgba(0,0,0,0.3);
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 1rem 1rem 0 0;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card login-card">
                    <div class="login-header">
                        <h2><i class="bi bi-cash-stack"></i> RMS</h2>
                        <p class="mb-0">Remittance Management System</p>
                    </div>
                    <div class="login-body">
                        <!-- Login Form -->
                        <form id="loginForm">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right"></i> Login
                                </button>
                            </div>
                        </form>
                        
                        <!-- 2FA OTP Form (Hidden by default) -->
                        <form id="otpForm" style="display: none;">
                            <div class="text-center mb-3">
                                <i class="bi bi-shield-check text-primary" style="font-size: 3rem;"></i>
                                <h5 class="mt-2">Two-Factor Authentication</h5>
                                <p class="text-muted small">Enter the OTP sent to <span id="phoneMasked"></span></p>
                            </div>
                            <div class="mb-3">
                                <label for="otp" class="form-label">OTP Code</label>
                                <input type="text" class="form-control form-control-lg text-center" 
                                       id="otp" name="otp" maxlength="6" required 
                                       style="letter-spacing: 5px; font-size: 1.5rem;">
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle"></i> Verify OTP
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="resendOtp">
                                    <i class="bi bi-arrow-clockwise"></i> Resend OTP
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" id="backToLogin">
                                    <i class="bi bi-arrow-left"></i> Back to Login
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="#" class="text-muted small">Forgot Password?</a>
                        </div>
                    </div>
                </div>
                <p class="text-center text-white mt-3">
                    <small>&copy; <?php echo date('Y'); ?> RMS. All rights reserved.</small>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Login Form Handler
        $('#loginForm').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: '?page=login',
                method: 'POST',
                data: {
                    action: 'login',
                    username: $('#username').val(),
                    password: $('#password').val()
                },
                dataType: 'json',
                beforeSend: function() {
                    $('button[type="submit"]').prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Logging in...');
                },
                success: function(response) {
                    if (response.success) {
                        if (response.requires_2fa) {
                            // Show OTP form
                            $('#loginForm').hide();
                            $('#otpForm').show();
                            $('#phoneMasked').text(response.phone_masked);
                            Swal.fire({
                                icon: 'info',
                                title: '2FA Required',
                                text: response.message,
                                timer: 3000
                            });
                        } else {
                            // Redirect to dashboard
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = response.redirect;
                            });
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Login Failed',
                            text: response.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred. Please try again.'
                    });
                },
                complete: function() {
                    $('button[type="submit"]').prop('disabled', false).html('<i class="bi bi-box-arrow-in-right"></i> Login');
                }
            });
        });
        
        // OTP Form Handler
        $('#otpForm').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: '?page=login',
                method: 'POST',
                data: {
                    action: 'verify_otp',
                    otp: $('#otp').val()
                },
                dataType: 'json',
                beforeSend: function() {
                    $('button[type="submit"]').prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Verifying...');
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = response.redirect;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Verification Failed',
                            text: response.message
                        });
                        $('#otp').val('').focus();
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred. Please try again.'
                    });
                },
                complete: function() {
                    $('button[type="submit"]').prop('disabled', false).html('<i class="bi bi-check-circle"></i> Verify OTP');
                }
            });
        });
        
        // Resend OTP
        $('#resendOtp').on('click', function() {
            $.ajax({
                url: '?page=login',
                method: 'POST',
                data: { action: 'resend_otp' },
                dataType: 'json',
                beforeSend: function() {
                    $('#resendOtp').prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Sending...');
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'OTP Resent',
                            text: response.message,
                            timer: 2000
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message
                        });
                    }
                },
                complete: function() {
                    $('#resendOtp').prop('disabled', false).html('<i class="bi bi-arrow-clockwise"></i> Resend OTP');
                }
            });
        });
        
        // Back to Login
        $('#backToLogin').on('click', function() {
            $('#otpForm').hide();
            $('#loginForm').show();
            $('#otp').val('');
            $('#password').val('');
        });
    </script>

</body>
</html>
