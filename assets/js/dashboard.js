/**
 * Dashboard JavaScript
 * Handles dynamic data loading for the dashboard
 */

let volumeChartInstance = null;
let statusChartInstance = null;

$(document).ready(function () {
    // Initial Load
    refreshDashboard();

    // Period Filter Change
    $('#periodFilter').on('change', function () {
        if ($(this).val() === 'custom') {
            $('#customDateRange').removeClass('d-none');
        } else {
            $('#customDateRange').addClass('d-none');
            refreshDashboard();
        }
    });

    // Custom Date Apply
    $('#applyCustomDate').on('click', function () {
        refreshDashboard();
    });

    // Settlement Approval Form (From Dashboard)
    $('#approveSettlementForm').on('submit', function (e) {
        e.preventDefault();
        approveSettlement();
    });
});

/**
 * Refresh all dashboard data
 */
function refreshDashboard() {
    const period = $('#periodFilter').val();
    const startDate = $('#startDate').val();
    const endDate = $('#endDate').val();

    const params = {
        period: period,
        start_date: startDate,
        end_date: endDate
    };

    loadStats(params);
    loadCharts(params);
    loadTables(params);

}

/**
 * Load Stats Cards
 */
function loadStats(params) {
    $.ajax({
        url: '?page=dashboard',
        method: 'POST',
        data: { ...params, action: 'get_stats' },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                const data = response.data;

                // Update 8 Cards
                $('#stats_remittance_volume').text(formatCurrency(data.remittance_volume));
                $('#stats_remittance_count').text(data.remittance_count);

                // Card 3: Commission or Balance
                if (data.my_balance !== undefined) {
                    $('#label_card_3').text('My Balance');
                    $('#stats_card_3').text(formatCurrency(data.my_balance));
                } else {
                    $('#label_card_3').text('Commission');
                    $('#stats_card_3').text(formatCurrency(data.total_commission));
                }

                $('#stats_today_volume').text(formatCurrency(data.today_volume));

                // Pending Settlements
                $('#stats_pending_count').text(data.pending_settlements_count);
                $('#stats_pending_amount').text(data.pending_settlements_amount.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 }));

                // Card 6: Active Agents or N/A
                if (data.active_agents !== undefined) {
                    $('#label_card_6').text('Active Agents');
                    $('#stats_card_6').text(data.active_agents);
                } else {
                    $('#label_card_6').text('System Status');
                    $('#stats_card_6').html('<span class="text-success">Active</span>');
                }

                $('#stats_success_rate').text(data.success_rate + '%');
                $('#stats_rejected_count').text(data.rejected_count);
            }
        }
    });
}

/**
 * Load Charts
 */
/**
 * Load Charts
 */
function loadCharts(params) {
    console.log('Loading charts with params:', params);
    $.ajax({
        url: '?page=dashboard',
        method: 'POST',
        data: { ...params, action: 'get_charts' },
        dataType: 'json',
        success: function (response) {
            console.log('Charts API Response:', response);
            if (response.success) {
                renderVolumeChart(response.volume_trend);
                renderStatusChart(response.status_dist);
            } else {
                console.error('Charts API returned success:false', response.message);
            }
        },
        error: function (xhr, status, error) {
            console.error('Charts API Error:', status, error);
            console.log('Response Text:', xhr.responseText);
            // Show error in chart containers
            const volumeContainer = document.getElementById('volumeChart').parentElement;
            volumeContainer.innerHTML = `<div class="alert alert-danger">Error loading chart data: ${error}</div>`;
            const statusContainer = document.getElementById('statusChart').parentElement;
            statusContainer.innerHTML = `<div class="alert alert-danger">Error loading chart data: ${error}</div>`;
        }
    });
}

/**
 * Render Volume Line Chart
 */
function renderVolumeChart(data) {
    console.log('Rendering Volume Chart', data);
    const canvas = document.getElementById('volumeChart');
    if (!canvas) return;

    // Parent container for messages
    const container = canvas.parentElement;

    // Clear previous messages
    const existingMsg = container.querySelector('.no-data-msg');
    if (existingMsg) existingMsg.remove();

    // Check if Chart library is loaded
    if (typeof Chart === 'undefined') {
        container.innerHTML = '<div class="alert alert-danger">Error: Chart.js library not loaded. Check internet connection.</div>';
        return;
    }

    const ctx = canvas.getContext('2d');

    if (volumeChartInstance) {
        volumeChartInstance.destroy();
    }

    // Handle Empty Data
    if (!data || data.length === 0) {
        // Option 1: Show Message
        canvas.style.display = 'none';
        const msg = document.createElement('div');
        msg.className = 'd-flex justify-content-center align-items-center h-100 no-data-msg text-muted';
        msg.innerHTML = '<i class="bi bi-inbox me-2"></i> No data available for this period';
        container.appendChild(msg);
        return;
    }

    // Data exists, ensure canvas is visible
    canvas.style.display = 'block';

    const labels = data.map(item => item.label);
    const values = data.map(item => item.value);

    console.log(labels, values);

    volumeChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Remittance Volume',
                data: values,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [2, 4] }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
}

/**
 * Render Status Pie Chart
 */
function renderStatusChart(data) {
    console.log('Rendering Status Chart', data);
    const canvas = document.getElementById('statusChart');
    if (!canvas) return;

    const container = canvas.parentElement;
    const existingMsg = container.querySelector('.no-data-msg');
    if (existingMsg) existingMsg.remove();

    const ctx = canvas.getContext('2d');

    if (statusChartInstance) {
        statusChartInstance.destroy();
    }

    if (!data || data.length === 0) {
        canvas.style.display = 'none';
        const msg = document.createElement('div');
        msg.className = 'd-flex justify-content-center align-items-center h-100 no-data-msg text-muted';
        msg.innerHTML = '<i class="bi bi-inbox me-2"></i> No transaction data';
        container.appendChild(msg);
        return;
    }

    canvas.style.display = 'block';

    // Colors matching bootstrap badges
    const statusColors = {
        'completed': '#198754', // success
        'approved': '#0dcaf0',  // info
        'pending': '#ffc107',   // warning
        'rejected': '#dc3545',  // danger
        'cancelled': '#6c757d'  // secondary
    };

    const labels = data.map(item => item.label.charAt(0).toUpperCase() + item.label.slice(1));
    const values = data.map(item => item.value);
    const bgColors = data.map(item => statusColors[item.label] || '#6c757d');

    statusChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: bgColors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

/**
 * Load Tables
 */
function loadTables(params) {
    $.ajax({
        url: '?page=dashboard',
        method: 'POST',
        data: { ...params, action: 'get_tables' },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                // Recent Remittances
                let remRows = '';
                if (response.recent_remittances.length === 0) {
                    remRows = '<tr><td colspan="4" class="text-center text-muted">No recent transactions</td></tr>';
                } else {
                    response.recent_remittances.forEach(rem => {
                        let badgeClass = 'secondary';
                        if (rem.status === 'completed') badgeClass = 'success';
                        else if (rem.status === 'pending') badgeClass = 'warning';
                        else if (rem.status === 'approved') badgeClass = 'info';
                        else if (rem.status === 'rejected') badgeClass = 'danger';

                        remRows += `
                            <tr>
                                <td>${rem.transaction_id}</td>
                                <td>${rem.sender_name}</td>
                                <td class="fw-bold">${parseFloat(rem.amount_sent).toFixed(2)} ${rem.currency_sent}</td>
                                <td><span class="badge bg-${badgeClass}">${rem.status.toUpperCase()}</span></td>
                            </tr>
                        `;
                    });
                }
                $('#recentRemittancesTable').html(remRows);

                // Pending Settlements
                let settRows = '';
                if (response.pending_settlements.length === 0) {
                    settRows = '<tr><td colspan="4" class="text-center text-muted">No pending settlements</td></tr>';
                } else {
                    response.pending_settlements.forEach(sett => {
                        settRows += `
                            <tr>
                                <td>${sett.agent_name}</td>
                                <td class="fw-bold">${parseFloat(sett.requested_amount).toFixed(2)} ${sett.currency_code}</td>
                                <td>${new Date(sett.created_at).toLocaleDateString()}</td>
                                <td>
                                    <button onclick="openApproveModal(${sett.id})" class="btn btn-sm btn-success py-0 px-2">Approve</button>
                                </td>
                            </tr>
                        `;
                    });
                }
                $('#pendingSettlementsTable').html(settRows);
            }
        }
    });
}

/**
 * Open Approve Modal for Settlement
 */
function openApproveModal(id) {
    // Check key requirements: This assumes settlement_controller checks permissions
    // But we are on Dashboard page. 
    // AJAX needs to point to 'settlement' page for approval action?
    // OR we duplicate approval logic?
    // BETTER: Calls settlement controller for action.

    $('#approve_settlement_id').val(id);
    const modal = new bootstrap.Modal(document.getElementById('approveSettlementModal'));
    modal.show();
}

/**
 * Submit Approval
 */
function approveSettlement() {
    const formData = $('#approveSettlementForm').serialize() + '&action=approve';

    // Note: We send this to ?page=settlement (the settlement controller)
    // NOT dashboard controller, so we reuse the logic we just built!
    $.ajax({
        url: '?page=settlement',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                bootstrap.Modal.getInstance(document.getElementById('approveSettlementModal')).hide();
                Swal.fire('Success', 'Settlement Approved', 'success');
                // Refresh dashboard to remove from pending list
                refreshDashboard();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        }
    });
}

function formatCurrency(val) {
    return '$' + parseFloat(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
