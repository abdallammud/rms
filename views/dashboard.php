<?php require VIEW_PATH . '/partials/header.php'; ?>

<div class="container-fluid py-4">
    <!-- Dashboard Header and Filters -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="mb-0">Dashboard</h2>
            <p class="text-muted mb-0">Overview & Statistics</p>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Period Filter -->
            <select class="form-select" id="periodFilter" style="width: 150px;">
                <option value="daily">Today</option>
                <option value="weekly">Last 7 Days</option>
                <option value="monthly" selected>Last 30 Days</option>
                <option value="custom">Custom Range</option>
            </select>
            
            <!-- Custom Date Range (Hidden by default) -->
            <div id="customDateRange" class="d-none d-flex gap-2">
                <input type="date" class="form-control" id="startDate" placeholder="Start Date">
                <input type="date" class="form-control" id="endDate" placeholder="End Date">
                <button class="btn btn-primary" id="applyCustomDate">Go</button>
            </div>
            
            <button class="btn btn-outline-secondary" onclick="refreshDashboard()">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    <!-- 8 Key Metrics Cards -->
    <div class="row g-3 mb-4">
        <!-- 1. Remittance Volume -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title mb-0">Total Volume</h6>
                        <i class="bi bi-currency-dollar fs-4 opacity-75"></i>
                    </div>
                    <h3 class="mb-0" id="stats_remittance_volume">$0.00</h3>
                    <small class="opacity-75">In selected period</small>
                </div>
            </div>
        </div>
        
        <!-- 2. Transaction Count -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title mb-0">Transactions</h6>
                        <i class="bi bi-receipt fs-4 opacity-75"></i>
                    </div>
                    <h3 class="mb-0" id="stats_remittance_count">0</h3>
                    <small class="opacity-75">Total processed</small>
                </div>
            </div>
        </div>
        
        <!-- 3. Commission (Or Balance for Agent) -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title mb-0" id="label_card_3">Commission</h6>
                        <i class="bi bi-wallet2 fs-4 opacity-75"></i>
                    </div>
                    <h3 class="mb-0" id="stats_card_3">$0.00</h3>
                    <small class="opacity-75">Earned income</small>
                </div>
            </div>
        </div>
        
        <!-- 4. Today's Volume -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-purple text-white h-100 shadow-sm" style="background-color: #6f42c1;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title mb-0">Today's Volume</h6>
                        <i class="bi bi-graph-up-arrow fs-4 opacity-75"></i>
                    </div>
                    <h3 class="mb-0" id="stats_today_volume">$0.00</h3>
                    <small class="opacity-75">Daily performance</small>
                </div>
            </div>
        </div>
        
        <!-- 5. Pending Settlements -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-dark h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title mb-0">Pending Settlements</h6>
                        <i class="bi bi-hourglass-split fs-4 opacity-50"></i>
                    </div>
                    <h3 class="mb-0"><span id="stats_pending_count">0</span> <small class="fs-6">($<span id="stats_pending_amount">0</span>)</small></h3>
                    <small class="opacity-75">Awaiting approval</small>
                </div>
            </div>
        </div>
        
        <!-- 6. Active Agents / My Balance -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-dark text-white h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title mb-0" id="label_card_6">Active Agents</h6>
                        <i class="bi bi-people fs-4 opacity-75"></i>
                    </div>
                    <h3 class="mb-0" id="stats_card_6">0</h3>
                    <small class="opacity-75">System status</small>
                </div>
            </div>
        </div>
        
        <!-- 7. Success Rate -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-teal text-white h-100 shadow-sm" style="background-color: #20c997;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title mb-0">Success Rate</h6>
                        <i class="bi bi-check-circle fs-4 opacity-75"></i>
                    </div>
                    <h3 class="mb-0" id="stats_success_rate">0%</h3>
                    <small class="opacity-75">Completion ratio</small>
                </div>
            </div>
        </div>
        
        <!-- 8. Rejected/Failed -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title mb-0">Rejected</h6>
                        <i class="bi bi-x-circle fs-4 opacity-75"></i>
                    </div>
                    <h3 class="mb-0" id="stats_rejected_count">0</h3>
                    <small class="opacity-75">Needs attention</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Volume Trend -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">Remittance Volume Trend</h5>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="volumeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status Distribution -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">Transaction Status</h5>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 250px; width: 100%;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tables Row -->
    <div class="row g-4">
        <!-- Recent Remittances -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Remittances</h5>
                    <a href="?page=remittances" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Ref</th>
                                    <th>Sender</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="recentRemittancesTable">
                                <!-- Loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pending Settlements / Requests -->
        <div class="col-lg-6" id="settlementsCard">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Pending Settlements</h5>
                    <a href="?page=settlements" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Agent</th>
                                    <th>Amount</th>
                                    <th>Requested</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="pendingSettlementsTable">
                                <!-- Loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal (Reused) -->
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
                        <label class="form-label">Reference Number</label>
                        <input type="text" class="form-control" name="reference_number">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script src="assets/js/dashboard.js"></script>

<?php require VIEW_PATH . '/partials/footer.php'; ?>
