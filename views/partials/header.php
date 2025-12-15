<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RMS - Remittance Management System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <?php if (is_logged_in()): ?>
        <?php include VIEW_PATH . '/partials/navbar.php'; ?>
        <?php include VIEW_PATH . '/partials/sidebar.php'; ?>
    <?php endif; ?>
    
    <!-- Main Content -->
    <div class="<?php echo is_logged_in() ? 'main-content' : ''; ?>">
