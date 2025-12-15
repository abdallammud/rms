<?php require VIEW_PATH . '/partials/header.php'; ?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Banking Management</h2>
            <p class="text-muted">Manage banks, bank accounts, and settlements</p>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="banks-tab" data-bs-toggle="tab" data-bs-target="#banks" 
                    type="button" role="tab" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; margin-right: 5px;">
                <i class="bi bi-building"></i> Banks
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="accounts-tab" data-bs-toggle="tab" data-bs-target="#accounts" 
                    type="button" role="tab" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: none; margin-right: 5px;">
                <i class="bi bi-credit-card"></i> Bank Accounts
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="settlements-tab" data-bs-toggle="tab" data-bs-target="#settlements" 
                    type="button" role="tab" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border: none;">
                <i class="bi bi-cash-stack"></i> Settlements
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Banks Tab -->
        <div class="tab-pane fade show active" id="banks" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Banks</h5>
                    <?php if (has_permission('manage_banks')): ?>
                    <button class="btn btn-primary btn-sm" onclick="showAddBankModal()">
                        <i class="bi bi-plus-circle"></i> Add Bank
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="banksTable" class="table table-hover" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Bank Name</th>
                                    <th>Accounts</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
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

        <!-- Bank Accounts Tab -->
        <div class="tab-pane fade" id="accounts" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Bank Accounts</h5>
                    <?php if (has_permission('manage_bank_accounts')): ?>
                    <button class="btn btn-primary btn-sm" onclick="showAddAccountModal()">
                        <i class="bi bi-plus-circle"></i> Add Account
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="accountsTable" class="table table-hover" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Account Holder</th>
                                    <th>Bank</th>
                                    <th>Account Number</th>
                                    <th>Balance</th>
                                    <th>Currency</th>
                                    <th>Default</th>
                                    <th>Status</th>
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

        <!-- Settlements Tab -->
        <div class="tab-pane fade" id="settlements" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Settlement Requests</h5>
                    <?php if (has_permission('request_settlement')): ?>
                    <button class="btn btn-success btn-sm" onclick="showRequestSettlementModal()">
                        <i class="bi bi-plus-circle"></i> Request Settlement
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="settlementsTable" class="table table-hover" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Agent</th>
                                    <th>Bank Account</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                    <th>Requested Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Bank Modal -->
<div class="modal fade" id="bankModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bankModalLabel">Add Bank</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bankForm">
                <div class="modal-body">
                    <input type="hidden" id="bank_id" name="bank_id">
                    
                    <div class="mb-3">
                        <label for="bank_name" class="form-label">Bank Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="bank_name" name="bank_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Bank
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add/Edit Account Modal -->
<div class="modal fade" id="accountModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accountModalLabel">Add Bank Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="accountForm">
                <div class="modal-body">
                    <input type="hidden" id="account_id" name="account_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="account_holder_id" class="form-label">Account Holder <span class="text-danger">*</span></label>
                            <select class="form-select" id="account_holder_id" name="account_holder_id" required>
                                <option value="">Select User</option>
                                <?php
                                $users = db_query_all("SELECT id, full_name, username FROM users WHERE is_active = 1 ORDER BY full_name");
                                foreach ($users as $user) {
                                    echo "<option value='{$user['id']}'>{$user['full_name']} ({$user['username']})</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="bank_id" class="form-label">Bank <span class="text-danger">*</span></label>
                            <select class="form-select" id="bank_id" name="bank_id" required>
                                <option value="">Select Bank</option>
                                 <?php
                                $banks = db_query_all("SELECT id, bank_name FROM banks WHERE is_active = 1 ORDER BY bank_name");
                                foreach ($banks as $bank) {
                                    echo "<option value='{$bank['id']}'>{$bank['bank_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="account_number" class="form-label">Account Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="account_number" name="account_number" required>
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
                    
                    <div class="mb-3" id="initial_balance_group">
                        <label for="initial_balance" class="form-label">Initial Balance</label>
                        <input type="number" class="form-control" id="initial_balance" name="initial_balance" 
                               step="0.01" value="0.00">
                        <small class="text-muted">Only for new accounts. Cannot be changed after creation.</small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_default" name="is_default">
                            <label class="form-check-label" for="is_default">
                                Set as Default Account
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="account_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="account_notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Request Settlement Modal -->
<div class="modal fade" id="settlementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Settlement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="settlementForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="settlement_bank_account_id" class="form-label">Bank Account <span class="text-danger">*</span></label>
                        <select class="form-select" id="settlement_bank_account_id" name="bank_account_id" required>
                            <option value="">Select Bank Account</option>
                            <!-- Loaded via AJAX -->
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="requested_amount" class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="requested_amount" name="requested_amount" 
                               step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method">
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                            <option value="mobile_money">Mobile Money</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="settlement_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="settlement_notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-send"></i> Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Approve Settlement Modal -->
<div class="modal fade" id="approveSettlementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Settlement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveSettlementForm">
                <div class="modal-body">
                    <input type="hidden" id="approve_settlement_id" name="settlement_id">
                    
                    <div class="mb-3">
                        <label for="reference_number" class="form-label">Reference Number</label>
                        <input type="text" class="form-control" id="reference_number" name="reference_number">
                        <small class="text-muted">Bank transfer reference or receipt number</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Approve & Complete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Activity Modal -->
<div class="modal fade" id="activityModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Account Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="activityTable" class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Direction</th>
                                <th>Amount</th>
                                <th>Balance Before</th>
                                <th>Balance After</th>
                                <th>Description</th>
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
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Set current user ID for JavaScript
    window.currentUserId = <?php echo get_user_id(); ?>;
</script>
<script src="assets/js/banks.js"></script>

<?php require VIEW_PATH . '/partials/footer.php'; ?>
