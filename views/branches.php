<?php require VIEW_PATH . '/partials/header.php'; ?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Branch Management</h2>
            <p class="text-muted">Manage all branches and their information</p>
        </div>
        <?php if (has_permission('create_branch')): ?>
        <button class="btn btn-primary" onclick="showAddBranchModal()">
            <i class="bi bi-plus-circle"></i> Add New Branch
        </button>
        <?php endif; ?>
    </div>

    <!-- Branches Table Card -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="branchesTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Branch Code</th>
                            <th>Branch Name</th>
                            <th>Location</th>
                            <th>Manager</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Branch Modal -->
<div class="modal fade" id="branchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="branchModalLabel">Add New Branch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="branchForm">
                <div class="modal-body">
                    <input type="hidden" id="branch_id" name="branch_id">
                    
                    <div class="mb-3">
                        <label for="branch_name" class="form-label">Branch Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="branch_name" name="branch_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="branch_code" class="form-label">Branch Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="branch_code" name="branch_code" required>
                        <small class="text-muted">Unique identifier for the branch (e.g., BR001)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <textarea class="form-control" id="location" name="location" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="manager_name" class="form-label">Manager Name</label>
                        <input type="text" class="form-control" id="manager_name" name="manager_name">
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Branch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Branch JavaScript -->
<script src="assets/js/branches.js"></script>

<?php require VIEW_PATH . '/partials/footer.php'; ?>
