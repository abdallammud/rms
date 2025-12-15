/**
 * Reports JavaScript
 * Handles report generation and display
 */

// Definition of report columns
const reportConfig = {
    'daily_remittance': {
        title: 'Daily Remittances',
        filters: ['filter_status', 'filter_agent'],
        headers: ['Date', 'Trans ID', 'Agent', 'Sender', 'Receiver', 'Amount', 'Comm.', 'Status'],
        renderRow: (row) => `
            <tr>
                <td>${formatDate(row.created_at)}</td>
                <td>${row.transaction_id}</td>
                <td>${row.agent_name || 'N/A'}</td>
                <td>${row.sender_name}</td>
                <td>${row.receiver_name}</td>
                <td class="fw-bold">${formatMoney(row.amount_sent, row.currency_sent)}</td>
                <td>${formatMoney(row.total_commission)}</td>
                <td>${formatStatus(row.status)}</td>
            </tr>
        `
    },
    'agent_performance': {
        title: 'Agent Performance',
        filters: [],
        headers: ['Agent Name', 'Username', 'Count', 'Total Volume', 'Comm. Generated', 'Rejected'],
        renderRow: (row) => `
            <tr>
                <td>${row.agent_name}</td>
                <td>${row.username}</td>
                <td>${row.transaction_count}</td>
                <td class="fw-bold">${formatMoney(row.total_volume)}</td>
                <td class="text-success">${formatMoney(row.total_commission_generated)}</td>
                <td class="text-danger">${row.rejected_count}</td>
            </tr>
        `
    },
    'earnings': {
        title: 'Earnings Report',
        filters: ['filter_agent'],
        headers: ['Date', 'Count', 'Volume', 'Cust. Comm', 'Agent Comm', 'Total Comm'],
        renderRow: (row) => `
            <tr>
                <td>${formatDate(row.date, false)}</td>
                <td>${row.count}</td>
                <td>${formatMoney(row.volume)}</td>
                <td>${formatMoney(row.customer_comm)}</td>
                <td class="text-danger">-${formatMoney(row.agent_comm)}</td>
                <td class="fw-bold text-success">${formatMoney(row.total_comm)}</td>
            </tr>
        `
    },
    'settlements': {
        title: 'Settlement History',
        filters: ['filter_status'],
        headers: ['Created At', 'Code', 'Agent', 'Amount', 'Status', 'Approved By'],
        renderRow: (row) => `
            <tr>
                <td>${formatDate(row.created_at)}</td>
                <td>${row.settlement_code}</td>
                <td>${row.agent_name}</td>
                <td class="fw-bold">${formatMoney(row.requested_amount)}</td>
                <td>${formatStatus(row.status)}</td>
                <td>${row.approved_by || '-'}</td>
            </tr>
        `
    },
    'bank_activity': {
        title: 'Bank Account Activity',
        filters: ['filter_bank_account'],
        headers: ['Date', 'Bank', 'Account', 'Type', 'Dir.', 'Amount', 'End Balance', 'Desc'],
        renderRow: (row) => `
            <tr>
                <td>${formatDate(row.created_at)}</td>
                <td>${row.bank_name}</td>
                <td>****${row.account_number.slice(-4)}</td>
                <td>${row.transaction_type}</td>
                <td>${row.transaction_direction === 'credit' ? '<span class="badge bg-success">Credit</span>' : '<span class="badge bg-danger">Debit</span>'}</td>
                <td class="fw-bold">${formatMoney(row.amount)}</td>
                <td>${formatMoney(row.balance_after)}</td>
                <td><small>${row.description}</small></td>
            </tr>
        `
    }
};

$(document).ready(function () {
    // Initial Load - default to daily remittance
    // Trigger first generation? Maybe wait for user.

    $('#reportFilterForm').on('submit', function (e) {
        e.preventDefault();
        generateReport();
    });
});

function selectReport(element) {
    // UI Update
    $('.list-group-item').removeClass('active');
    $(element).addClass('active');

    const reportType = $(element).data('report');
    const config = reportConfig[reportType];

    if (!config) return;

    // Set Action
    $('#reportAction').val(reportType);
    $('#reportTitle').text(config.title);

    // Toggle Filters
    $('.dynamic-filter').addClass('d-none');
    if (config.filters) {
        config.filters.forEach(id => $(`#${id}`).removeClass('d-none'));
    }

    // Add logic to auto-submit or clear table?
    // Let's clear table to indicate state change
    $('#reportHeader').empty();
    $('#reportBody').html('<tr><td colspan="10" class="text-center text-muted">Click Generate to load data</td></tr>');
}

function generateReport() {
    const reportType = $('#reportAction').val();
    const config = reportConfig[reportType];
    const formData = $('#reportFilterForm').serialize();

    $('#reportBody').html('<tr><td colspan="10" class="text-center text-muted"><div class="spinner-border spinner-border-sm"></div> Loading...</td></tr>');

    $.ajax({
        url: '?page=reports',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                renderTable(response.data, config);
            } else {
                Swal.fire('Error', response.message, 'error');
                $('#reportBody').html('<tr><td colspan="10" class="text-center text-danger">Error loading data</td></tr>');
            }
        },
        error: function (xhr, status, error) {
            console.error('Report Error:', error);
            $('#reportBody').html(`<tr><td colspan="10" class="text-center text-danger">Server Error: ${error}</td></tr>`);
        }
    });
}

function renderTable(data, config) {
    // Render Headers
    let headerHtml = '<tr>';
    config.headers.forEach(h => headerHtml += `<th>${h}</th>`);
    headerHtml += '</tr>';
    $('#reportHeader').html(headerHtml);

    // Render Body
    if (data.length === 0) {
        $('#reportBody').html('<tr><td colspan="' + config.headers.length + '" class="text-center text-muted">No records found for selected period</td></tr>');
        return;
    }

    let rowsHtml = '';
    data.forEach(row => {
        rowsHtml += config.renderRow(row);
    });
    $('#reportBody').html(rowsHtml);
}

function formatMoney(amount, currency = 'USD') {
    return parseFloat(amount).toLocaleString('en-US', {
        style: 'currency',
        currency: currency
    });
}

function formatDate(dateString, includeTime = true) {
    const d = new Date(dateString);
    if (includeTime) return d.toLocaleString();
    return d.toLocaleDateString();
}

function formatStatus(status) {
    let badge = 'secondary';
    if (status === 'approved' || status === 'completed') badge = 'success';
    if (status === 'pending') badge = 'warning';
    if (status === 'rejected' || status === 'cancelled') badge = 'danger';
    return `<span class="badge bg-${badge}">${status.toUpperCase()}</span>`;
}

function printReport() {
    window.print();
}
