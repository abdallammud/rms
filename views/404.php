<?php require VIEW_PATH . '/partials/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="error-template">
                <h1 class="display-1">404</h1>
                <h2>Page Not Found</h2>
                <p class="text-muted">Sorry, the page you are looking for does not exist.</p>
                <div class="error-actions mt-4">
                    <a href="?page=dashboard" class="btn btn-primary btn-lg">
                        <i class="bi bi-house"></i> Take Me Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require VIEW_PATH . '/partials/footer.php'; ?>
