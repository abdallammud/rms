<?php require VIEW_PATH . '/partials/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar / Report Selector -->
        <div class="col-md-3 mb-4">
            <div class="list-group shadow-sm sticky-top" style="top: 20px;">
                <div class="list-group-item bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-bar-graph"></i> Select Report</h5>
                </div>
                <a href="#" class="list-group-item list-group-item-action active" data-report="daily_remittance" onclick="selectReport(this)">
                    <i class="bi bi-receipt me-2"></i> Daily Remittances
                </a>
                <?php if (has_permission('view_reports')): // Admin only ?>
                <a href="#" class="list-group-item list-group-item-action" data-report="agent_performance" onclick="selectReport(this)">
                    <i class="bi bi-people me-2"></i> Agent Performance
                </a>
                <?php endif; ?>
                <a href="#" class="list-group-item list-group-item-action" data-report="earnings" onclick="selectReport(this)">
                    <i class="bi bi-currency-dollar me-2"></i> Earnings & Commission
                </a>
                <a href="#" class="list-group-item list-group-item-action" data-report="settlements" onclick="selectReport(this)">
                    <i class="bi bi-cash-stack me-2"></i> Settlement History
                </a>
                <a href="#" class="list-group-item list-group-item-action" data-report="bank_activity" onclick="selectReport(this)">
                    <i class="bi bi-bank me-2"></i> Bank Account Activity
                </a>
            </div>
        </div>
        
        <!-- Report Content -->
        <div class="col-md-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" id="reportTitle">Daily Remittances</h5>
                        <div>
                            <button class="btn btn-sm btn-outline-success" onclick="printReport()">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form id="reportFilterForm" class="row g-3 mb-4 align-items-end">
                        <input type="hidden" name="action" id="reportAction" value="daily_remittance">
                        
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" id="start_date" 
                                   value="<?php echo date('Y-m-01'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" id="end_date" 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <!-- Dynamic Filters based on report type -->
                        <div class="col-md-3 d-none dynamic-filter" id="filter_status">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="completed">Completed</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        
                         <div class="col-md-3 d-none dynamic-filter" id="filter_agent">
                            <label class="form-label">Agent (Optional)</label>
                             <!-- In real implementation this would load via AJAX or include -->
                            <input type="text" class="form-control" name="agent_id" placeholder="Agent ID"> 
                        </div>

                        <div class="col-md-3 d-none dynamic-filter" id="filter_bank_account">
                            <label class="form-label">Bank Account</label>
                             <!-- Simple text input for now, ideally a select loaded via JS -->
                            <input type="text" class="form-control" name="bank_account_id" placeholder="Account ID">
                        </div>

                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Generate
                            </button>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <!-- Results Table -->
                    <div class="table-responsive">
                        <table id="reportTable" class="table table-bordered table-striped table-hover w-100">
                            <thead class="table-light" id="reportHeader">
                                <!-- Headers injected via JS -->
                            </thead>
                            <tbody id="reportBody">
                                <!-- Data injected via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/reports.js"></script>

<?php require VIEW_PATH . '/partials/footer.php'; ?>
