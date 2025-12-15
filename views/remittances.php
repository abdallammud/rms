<?php require VIEW_PATH . '/partials/header.php'; ?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Remittance Management</h2>
            <p class="text-muted">Manage money transfer transactions</p>
        </div>
        <?php if (has_permission('create_remittance')): ?>
        <button class="btn btn-primary" onclick="showAddRemittanceModal()">
            <i class="bi bi-plus-circle"></i> Add Remittance
        </button>
        <?php endif; ?>
    </div>

    <!-- Remittances Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Remittances</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="remittancesTable" class="table table-hover" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Sender</th>
                            <th>Receiver</th>
                            <th>Amount Sent</th>
                            <th>Commission</th>
                            <th>Bank Account</th>
                            <th>Status</th>
                            <th>Date</th>
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

<!-- Add/Edit Remittance Modal -->
<div class="modal fade" id="remittanceModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="remittanceModalLabel">Add Remittance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="remittanceForm">
                <div class="modal-body">
                    <input type="hidden" id="remittance_id" name="remittance_id">
                    
                    <!-- Sender Information Section -->
                    <h6 class="border-bottom pb-2 mb-3">Sender Information</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="sender_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="sender_name" name="sender_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="sender_phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="sender_phone" name="sender_phone">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="sender_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="sender_email" name="sender_email">
                        </div>
                        <div class="col-md-6">
                            <label for="sender_address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="sender_address" name="sender_address">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="sender_id_type" class="form-label">ID Type</label>
                            <select class="form-select" id="sender_id_type" name="sender_id_type">
                                <option value="">Select ID Type</option>
                                <option value="National ID">National ID</option>
                                <option value="Passport">Passport</option>
                                <option value="Driver License">Driver License</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="sender_id_number" class="form-label">ID Number</label>
                            <input type="text" class="form-control" id="sender_id_number" name="sender_id_number">
                        </div>
                        <div class="col-md-4">
                            <label for="sender_relation_to_receiver" class="form-label">Relation to Receiver</label>
                            <select class="form-select" id="sender_relation_to_receiver" name="sender_relation_to_receiver">
                                <option value="">Select Relation</option>
                                <option value="Family">Family</option>
                                <option value="Friend">Friend</option>
                                <option value="Business Partner">Business Partner</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Receiver Information Section -->
                    <h6 class="border-bottom pb-2 mb-3 mt-4">Receiver Information</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="receiver_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="receiver_name" name="receiver_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="receiver_phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="receiver_phone" name="receiver_phone">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="receiver_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="receiver_email" name="receiver_email">
                        </div>
                        <div class="col-md-6">
                            <label for="receiver_address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="receiver_address" name="receiver_address">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="receiver_id_type" class="form-label">ID Type</label>
                            <select class="form-select" id="receiver_id_type" name="receiver_id_type">
                                <option value="">Select ID Type</option>
                                <option value="National ID">National ID</option>
                                <option value="Passport">Passport</option>
                                <option value="Driver License">Driver License</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="receiver_id_number" class="form-label">ID Number</label>
                            <input type="text" class="form-control" id="receiver_id_number" name="receiver_id_number">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Receive Through</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="receive_method" id="receive_bank" value="bank" checked onchange="toggleReceiveMethod()">
                                    <label class="form-check-label" for="receive_bank">Bank Account</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="receive_method" id="receive_mobile" value="mobile" onchange="toggleReceiveMethod()">
                                    <label class="form-check-label" for="receive_mobile">Mobile Money</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="bank_details_section">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="receiver_bank_name" class="form-label">Bank Name</label>
                                <input type="text" class="form-control" id="receiver_bank_name" name="receiver_bank_name">
                            </div>
                            <div class="col-md-4">
                                <label for="receiver_account_number" class="form-label">Account Number</label>
                                <input type="text" class="form-control" id="receiver_account_number" name="receiver_account_number">
                            </div>
                            <div class="col-md-4">
                                <label for="receiver_account_holder" class="form-label">Account Holder Name</label>
                                <input type="text" class="form-control" id="receiver_account_holder" name="receiver_account_holder">
                            </div>
                        </div>
                    </div>
                    
                    <div id="mobile_details_section" style="display: none;">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="receiver_mobile_number" class="form-label">Mobile Money Number</label>
                                <input type="text" class="form-control" id="receiver_mobile_number" name="receiver_mobile_number">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Transaction Details Section -->
                    <h6 class="border-bottom pb-2 mb-3 mt-4">Transaction Details</h6>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="amount_sent" class="form-label">Amount to Send <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="amount_sent" name="amount_sent" step="0.01" required onchange="calculateCommission()">
                        </div>
                        <div class="col-md-4">
                            <label for="currency_sent" class="form-label">Currency</label>
                            <select class="form-select" id="currency_sent" name="currency_sent" onchange="calculateCommission()">
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="exchange_rate" class="form-label">Exchange Rate</label>
                            <input type="number" class="form-control" id="exchange_rate" name="exchange_rate" step="0.000001" value="1.0">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="bank_account_id" class="form-label">Pay From Bank Account <span class="text-danger">*</span></label>
                            <select class="form-select" id="bank_account_id" name="bank_account_id" required>
                                <option value="">Select Bank Account</option>
                                <!-- Loaded via AJAX -->
                            </select>
                            <small class="text-muted">Select which bank account to deduct the amount from</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Commission Details</label>
                            <div class="card bg-light">
                                <div class="card-body p-2">
                                    <small>
                                        <strong>Customer Commission:</strong> <span id="customer_commission_display">$0.00</span><br>
                                        <strong>Total Deduction:</strong> <span id="total_deduction_display">$0.00</span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Remittance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Remittance Details Modal -->
<div class="modal fade" id="viewRemittanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Remittance Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="remittanceDetailsContent">
                <!-- Loaded via JavaScript -->
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
<script src="assets/js/remittances.js"></script>

<?php require VIEW_PATH . '/partials/footer.php'; ?>
