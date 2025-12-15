<?php require VIEW_PATH . '/partials/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-2"><i class="bi bi-clock-history me-2"></i> Activity Log</h2>
            <p class="text-muted">Audit trail of system activities and user actions.</p>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form id="logFilterForm" class="row g-3 align-items-end">
                <input type="hidden" name="action" value="get_logs">
                
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Module</label>
                    <select class="form-select" name="module" id="moduleFilter">
                        <option value="">All Modules</option>
                        <!-- Populated by JS -->
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">User</label>
                    <select class="form-select" name="user_id" id="userFilter">
                        <option value="">All Users</option>
                        <!-- Populated by JS -->
                    </select>
                </div>
                
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Log Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="activityLogTable" class="table table-hover table-striped w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/activity_log.js"></script>

<?php require VIEW_PATH . '/partials/footer.php'; ?>
