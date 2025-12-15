<?php require VIEW_PATH . '/partials/header.php'; ?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Commission Tier Management</h2>
            <p class="text-muted">Configure commission rates for transactions</p>
        </div>
        <?php if (has_permission('manage_commission_tiers')): ?>
        <button class="btn btn-primary" onclick="showAddTierModal()">
            <i class="bi bi-plus-circle"></i> Add Commission Tier
        </button>
        <?php endif; ?>
    </div>

    <!-- Commission Tiers Table Card -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tiersTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tier Name</th>
                            <th>Type</th>
                            <th>Amount Range</th>
                            <th>Commission</th>
                            <th>Currency</th>
                            <th>Status</th>
                            <th>Created By</th>
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

<!-- Add/Edit Tier Modal -->
<div class="modal fade" id="tierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tierModalLabel">Add Commission Tier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="tierForm">
                <div class="modal-body">
                    <input type="hidden" id="tier_id" name="tier_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tier_name" class="form-label">Tier Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tier_name" name="tier_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="tier_type" class="form-label">Tier Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="tier_type" name="tier_type" required>
                                <option value="">Select Type</option>
                                <option value="customer">Customer Commission</option>
                                <option value="agent">Agent Commission</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="min_amount" class="form-label">Minimum Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="min_amount" name="min_amount" step="0.01" min="0" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="max_amount" class="form-label">Maximum Amount</label>
                            <input type="number" class="form-control" id="max_amount" name="max_amount" step="0.01" min="0">
                            <small class="text-muted">Leave empty for unlimited</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="commission_type" class="form-label">Commission Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="commission_type" name="commission_type" required>
                                <option value="">Select Type</option>
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="commission_value" class="form-label">Commission Value <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="commission_value" name="commission_value" step="0.01" min="0" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="currency_code" class="form-label">Currency</label>
                            <select class="form-select" id="currency_code" name="currency_code">
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>Note:</strong> Commission tiers are applied based on the transaction amount. 
                        The system will automatically select the appropriate tier when processing remittances.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Tier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Log Modal -->
<div class="modal fade" id="changeLogModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Old Value</th>
                                <th>New Value</th>
                                <th>Changed By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="changeLogBody">
                            <!-- Loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/commission.js"></script>

<?php require VIEW_PATH . '/partials/footer.php'; ?>
