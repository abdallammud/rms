/**
 * Remittance Management JavaScript
 * Handles remittance CRUD operations
 */

let remittancesTable;

$(document).ready(function () {
    // Initialize DataTable
    loadRemittancesTable();

    // Form submission
    $('#remittanceForm').on('submit', function (e) {
        e.preventDefault();
        saveRemittance();
    });

    // Reset form when modal closes
    $('#remittanceModal').on('hidden.bs.modal', function () {
        $('#remittanceForm')[0].reset();
        $('#remittance_id').val('');
        $('#customer_commission_display').text('$0.00');
        $('#total_deduction_display').text('$0.00');
        $('#bank_details_section').show();
        $('#mobile_details_section').hide();
    });
});

/**
 * Load remittances DataTable
 */
function loadRemittancesTable() {
    remittancesTable = $('#remittancesTable').DataTable({
        ajax: {
            url: '?page=remittances',
            type: 'POST',
            data: { action: 'list' },
            dataSrc: 'data'
        },
        columns: [
            { data: 'transaction_id' },
            {
                data: 'sender_name',
                render: function (data, type, row) {
                    return `${data}<br><small class="text-muted">${row.sender_phone || 'N/A'}</small>`;
                }
            },
            {
                data: 'receiver_name',
                render: function (data, type, row) {
                    return `${data}<br><small class="text-muted">${row.receiver_phone || 'N/A'}</small>`;
                }
            },
            {
                data: 'amount_sent',
                render: function (data, type, row) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }) + ' ' + row.currency_sent;
                }
            },
            {
                data: 'total_commission',
                render: function (data) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    if (row.bank_name && row.account_number) {
                        return `${row.bank_name}<br><small>****${row.account_number.slice(-4)}</small>`;
                    }
                    return 'N/A';
                }
            },
            {
                data: 'status',
                render: function (data) {
                    let badgeClass = 'secondary';
                    if (data === 'completed') badgeClass = 'success';
                    else if (data === 'approved') badgeClass = 'info';
                    else if (data === 'rejected') badgeClass = 'danger';
                    else if (data === 'pending') badgeClass = 'warning';
                    return `<span class="badge bg-${badgeClass}">${data.toUpperCase()}</span>`;
                }
            },
            {
                data: 'created_at',
                render: function (data) {
                    return data ? new Date(data).toLocaleDateString() : '';
                }
            },
            {
                data: null,
                orderable: false,
                render: function (data, type, row) {
                    let actions = '<div class="btn-group btn-group-sm">';

                    actions += `<button class="btn btn-info" onclick="viewRemittance(${row.id})" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </button>`;

                    // Only allow edit for pending/approved status
                    if (row.status === 'pending' || row.status === 'approved') {
                        actions += `<button class="btn btn-primary" onclick="editRemittance(${row.id})" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>`;
                    }

                    // Delete only for current user's remittances
                    actions += `<button class="btn btn-danger" onclick="deleteRemittance(${row.id})" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>`;

                    actions += '</div>';
                    return actions;
                }
            }
        ],
        order: [[7, 'desc']],
        pageLength: 10
    });
}

/**
 * Show Add Remittance Modal
 */
function showAddRemittanceModal() {
    $('#remittanceForm')[0].reset();
    $('#remittance_id').val('');
    $('#remittanceModalLabel').text('Add Remittance');
    $('#bank_details_section').show();
    $('#mobile_details_section').hide();
    loadUserBankAccounts();
    const modal = new bootstrap.Modal(document.getElementById('remittanceModal'));
    modal.show();
}

/**
 * Load user's bank accounts
 */
function loadUserBankAccounts() {
    const currentUserId = window.currentUserId || '';

    $.ajax({
        url: '?page=bank_account',
        method: 'POST',
        data: { action: 'get_user_accounts', user_id: currentUserId },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                let options = '<option value="">Select Bank Account</option>';
                response.data.forEach(account => {
                    const balance = parseFloat(account.balance).toFixed(2);
                    options += `<option value="${account.id}">${account.bank_name} - ****${account.account_number.slice(-4)} (Balance: ${balance} ${account.currency_code})</option>`;
                });
                $('#bank_account_id').html(options);
            } else {
                Swal.fire('Warning', 'No active bank accounts found. Please add a bank account first.', 'warning');
            }
        },
        error: function () {
            Swal.fire('Error', 'Failed to load bank accounts', 'error');
        }
    });
}

/**
 * Toggle receive method (Bank Account vs Mobile Money)
 */
function toggleReceiveMethod() {
    const method = $('input[name="receive_method"]:checked').val();

    if (method === 'bank') {
        $('#bank_details_section').show();
        $('#mobile_details_section').hide();
    } else {
        $('#bank_details_section').hide();
        $('#mobile_details_section').show();
    }
}

/**
 * Calculate commission in real-time
 */
function calculateCommission() {
    const amount = parseFloat($('#amount_sent').val()) || 0;
    const currency = $('#currency_sent').val();

    if (amount <= 0) {
        $('#customer_commission_display').text('$0.00');
        $('#total_deduction_display').text('$0.00');
        return;
    }

    $.ajax({
        url: '?page=remittances',
        method: 'POST',
        data: {
            action: 'calculate_commission',
            amount: amount,
            currency: currency
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                const customerCommission = parseFloat(response.customer_commission).toFixed(2);
                const totalDeduction = (amount + parseFloat(response.customer_commission)).toFixed(2);

                $('#customer_commission_display').text('$' + customerCommission);
                $('#total_deduction_display').text('$' + totalDeduction);
            }
        }
    });
}

/**
 * Edit remittance
 */
function editRemittance(remittanceId) {
    $.ajax({
        url: '?page=remittances',
        method: 'POST',
        data: { action: 'get', remittance_id: remittanceId },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                const rem = response.data;

                // Populate sender info
                $('#remittance_id').val(rem.id);
                $('#sender_name').val(rem.sender_name);
                $('#sender_phone').val(rem.sender_phone);
                $('#sender_email').val(rem.sender_email);
                $('#sender_address').val(rem.sender_address);
                $('#sender_id_type').val(rem.sender_id_type);
                $('#sender_id_number').val(rem.sender_id_number);
                $('#sender_relation_to_receiver').val(rem.sender_relation_to_receiver);

                // Populate receiver info
                $('#receiver_name').val(rem.receiver_name);
                $('#receiver_phone').val(rem.receiver_phone);
                $('#receiver_email').val(rem.receiver_email);
                $('#receiver_address').val(rem.receiver_address);
                $('#receiver_id_type').val(rem.receiver_id_type);
                $('#receiver_id_number').val(rem.receiver_id_number);
                $('#receiver_bank_name').val(rem.receiver_bank_name);
                $('#receiver_account_number').val(rem.receiver_account_number);
                $('#receiver_account_holder').val(rem.receiver_account_holder);

                // Populate transaction info (read-only for edit)
                $('#amount_sent').val(rem.amount_sent).prop('readonly', true);
                $('#currency_sent').val(rem.currency_sent).prop('disabled', true);
                $('#exchange_rate').val(rem.exchange_rate);
                $('#bank_account_id').val(rem.bank_account_id).prop('disabled', true);

                // Display commission
                $('#customer_commission_display').text('$' + parseFloat(rem.customer_commission).toFixed(2));
                $('#total_deduction_display').text('$' + (parseFloat(rem.amount_sent) + parseFloat(rem.customer_commission)).toFixed(2));

                $('#remittanceModalLabel').text('Edit Remittance');
                loadUserBankAccounts();
                setTimeout(() => $('#bank_account_id').val(rem.bank_account_id), 300);

                const modal = new bootstrap.Modal(document.getElementById('remittanceModal'));
                modal.show();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        }
    });
}

/**
 * Save remittance
 */
function saveRemittance() {
    const formData = $('#remittanceForm').serialize() + '&action=save';

    $.ajax({
        url: '?page=remittances',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                bootstrap.Modal.getInstance(document.getElementById('remittanceModal')).hide();
                remittancesTable.ajax.reload();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'Failed to save remittance', 'error');
        }
    });
}

/**
 * View remittance details
 */
function viewRemittance(remittanceId) {
    $.ajax({
        url: '?page=remittances',
        method: 'POST',
        data: { action: 'get', remittance_id: remittanceId },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                const rem = response.data;

                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Sender Information</h6>
                            <p><strong>Name:</strong> ${rem.sender_name}</p>
                            <p><strong>Phone:</strong> ${rem.sender_phone || 'N/A'}</p>
                            <p><strong>Email:</strong> ${rem.sender_email || 'N/A'}</p>
                            <p><strong>Address:</strong> ${rem.sender_address || 'N/A'}</p>
                            <p><strong>ID:</strong> ${rem.sender_id_type || 'N/A'} - ${rem.sender_id_number || 'N/A'}</p>
                            <p><strong>Relation:</strong> ${rem.sender_relation_to_receiver || 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Receiver Information</h6>
                            <p><strong>Name:</strong> ${rem.receiver_name}</p>
                            <p><strong>Phone:</strong> ${rem.receiver_phone || 'N/A'}</p>
                            <p><strong>Email:</strong> ${rem.receiver_email || 'N/A'}</p>
                            <p><strong>Address:</strong> ${rem.receiver_address || 'N/A'}</p>
                            <p><strong>ID:</strong> ${rem.receiver_id_type || 'N/A'} - ${rem.receiver_id_number || 'N/A'}</p>
                            <p><strong>Bank:</strong> ${rem.receiver_bank_name || 'N/A'}</p>
                            <p><strong>Account:</strong> ${rem.receiver_account_number || 'N/A'}</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">Transaction Details</h6>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Transaction ID:</strong> ${rem.transaction_id}</p>
                            <p><strong>Amount Sent:</strong> ${parseFloat(rem.amount_sent).toFixed(2)} ${rem.currency_sent}</p>
                            <p><strong>Exchange Rate:</strong> ${rem.exchange_rate}</p>
                            <p><strong>Amount Received:</strong> ${parseFloat(rem.amount_received).toFixed(2)} ${rem.currency_received}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Customer Commission:</strong> $${parseFloat(rem.customer_commission).toFixed(2)}</p>
                            <p><strong>Agent Commission:</strong> $${parseFloat(rem.agent_commission).toFixed(2)}</p>
                            <p><strong>Total Commission:</strong> $${parseFloat(rem.total_commission).toFixed(2)}</p>
                            <p><strong>Status:</strong> <span class="badge bg-success">${rem.status.toUpperCase()}</span></p>
                        </div>
                    </div>
                `;

                $('#remittanceDetailsContent').html(html);
                const modal = new bootstrap.Modal(document.getElementById('viewRemittanceModal'));
                modal.show();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        }
    });
}

/**
 * Delete remittance
 */
function deleteRemittance(remittanceId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently delete the remittance!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '?page=remittances',
                method: 'POST',
                data: { action: 'delete', remittance_id: remittanceId },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        Swal.fire('Deleted!', response.message, 'success');
                        remittancesTable.ajax.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        }
    });
}
