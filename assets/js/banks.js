/**
 * Banking Management JavaScript
 * Handles Banks, Bank Accounts, and Settlements
 */

let banksTable;
let accountsTable;
let settlementsTable;

$(document).ready(function () {
    // Initialize DataTables
    loadBanksTable();
    loadAccountsTable();
    loadSettlementsTable();

    // Load active banks for dropdowns  
    loadActiveBanks();
    console.log('Initial bank load triggered');

    // Form submissions
    $('#bankForm').on('submit', function (e) {
        e.preventDefault();
        saveBank();
    });

    $('#accountForm').on('submit', function (e) {
        e.preventDefault();
        saveAccount();
    });

    $('#settlementForm').on('submit', function (e) {
        e.preventDefault();
        requestSettlement();
    });

    $('#approveSettlementForm').on('submit', function (e) {
        e.preventDefault();
        approveSettlement();
    });

    // Reset forms when modals close
    $('#bankModal').on('hidden.bs.modal', function () {
        $('#bankForm')[0].reset();
        $('#bank_id').val('');
    });

    $('#accountModal').on('hidden.bs.modal', function () {
        $('#accountForm')[0].reset();
        $('#account_id').val('');
        $('#initial_balance_group').show();
    });

    $('#settlementModal').on('hidden.bs.modal', function () {
        $('#settlementForm')[0].reset();
    });
});

// ==============================================
// BANKS MANAGEMENT
// ==============================================

/**
 * Load banks DataTable
 */
function loadBanksTable() {
    banksTable = $('#banksTable').DataTable({
        ajax: {
            url: '?page=bank',
            type: 'POST',
            data: { action: 'list' },
            dataSrc: 'data'
        },
        columns: [
            { data: 'bank_name' },
            {
                data: 'account_count', render: function (data) {
                    return data || '0';
                }
            },
            {
                data: 'is_active',
                render: function (data) {
                    return data == 1
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-danger">Inactive</span>';
                }
            },
            {
                data: 'created_by_name', render: function (data) {
                    return data || 'N/A';
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

                    actions += `<button class="btn btn-primary" onclick="editBank(${row.id})" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>`;

                    if (row.is_active == 1) {
                        actions += `<button class="btn btn-warning" onclick="toggleBankStatus(${row.id}, 0)" title="Deactivate">
                                        <i class="bi bi-pause-circle"></i>
                                    </button>`;
                    } else {
                        actions += `<button class="btn btn-success" onclick="toggleBankStatus(${row.id}, 1)" title="Activate">
                                        <i class="bi bi-play-circle"></i>
                                    </button>`;
                    }

                    if (!row.account_count || row.account_count == 0) {
                        actions += `<button class="btn btn-danger" onclick="deleteBank(${row.id})" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>`;
                    }

                    actions += '</div>';
                    return actions;
                }
            }
        ],
        order: [[0, 'asc']],
        pageLength: 10
    });
}

function showAddBankModal() {
    $('#bankForm')[0].reset();
    $('#bank_id').val('');
    $('#bankModalLabel').text('Add Bank');
    const modal = new bootstrap.Modal(document.getElementById('bankModal'));
    modal.show();
}

function editBank(bankId) {
    $.ajax({
        url: '?page=bank',
        method: 'POST',
        data: { action: 'get', bank_id: bankId },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                const bank = response.data;
                $('#bank_id').val(bank.id);
                $('#bank_name').val(bank.bank_name);
                $('#bankModalLabel').text('Edit Bank');
                const modal = new bootstrap.Modal(document.getElementById('bankModal'));
                modal.show();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        }
    });
}

function saveBank() {
    const formData = $('#bankForm').serialize() + '&action=save';

    $.ajax({
        url: '?page=bank',
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
                bootstrap.Modal.getInstance(document.getElementById('bankModal')).hide();
                banksTable.ajax.reload();
                loadActiveBanks();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        }
    });
}

function deleteBank(bankId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently delete the bank!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '?page=bank',
                method: 'POST',
                data: { action: 'delete', bank_id: bankId },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        Swal.fire('Deleted!', response.message, 'success');
                        banksTable.ajax.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        }
    });
}

function toggleBankStatus(bankId, status) {
    $.ajax({
        url: '?page=bank',
        method: 'POST',
        data: { action: 'toggle_status', bank_id: bankId, status: status },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                banksTable.ajax.reload();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        }
    });
}

function loadActiveBanks() {
    $.ajax({
        url: '?page=bank',
        method: 'POST',
        data: { action: 'list' },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                let options = '<option value="">Select Bank</option>';
                response.data.forEach(bank => {
                    if (bank.is_active == 1) {
                        options += `<option value="${bank.id}">${bank.bank_name}</option>`;
                    }
                });
                $('#bank_id').html(options);
            } else {
                console.error('Failed to load banks:', response.message);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error loading banks:', error);
        }
    });
}

// ==============================================
// BANK ACCOUNTS MANAGEMENT
// ==============================================

function loadAccountsTable() {
    accountsTable = $('#accountsTable').DataTable({
        ajax: {
            url: '?page=bank_account',
            type: 'POST',
            data: { action: 'list' },
            dataSrc: 'data'
        },
        columns: [
            {
                data: 'holder_name',
                render: function (data, type, row) {
                    return `${data}<br><small class="text-muted">${row.username}</small>`;
                }
            },
            { data: 'bank_name' },
            {
                data: 'account_number',
                render: function (data) {
                    return '****' + data.slice(-4);
                }
            },
            {
                data: 'balance',
                render: function (data, type, row) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }) + ' ' + row.currency_code;
                }
            },
            { data: 'currency_code' },
            {
                data: 'is_default',
                render: function (data) {
                    return data == 1
                        ? '<span class="badge bg-success">Default</span>'
                        : '<span class="badge bg-secondary">Secondary</span>';
                }
            },
            {
                data: 'is_active',
                render: function (data) {
                    return data == 1
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-danger">Inactive</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function (data, type, row) {
                    let actions = '<div class="btn-group btn-group-sm">';

                    actions += `<button class="btn btn-info" onclick="viewActivity(${row.id})" title="View Activity">
                                    <i class="bi bi-list-ul"></i>
                                </button>`;

                    actions += `<button class="btn btn-primary" onclick="editAccount(${row.id})" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>`;

                    if (row.is_default != 1) {
                        actions += `<button class="btn btn-warning" onclick="setDefaultAccount(${row.id})" title="Set as Default">
                                        <i class="bi bi-star"></i>
                                    </button>`;
                    }

                    if (row.is_active == 1) {
                        actions += `<button class="btn btn-secondary" onclick="toggleAccountStatus(${row.id}, 0)" title="Deactivate">
                                        <i class="bi bi-pause-circle"></i>
                                    </button>`;
                    } else {
                        actions += `<button class="btn btn-success" onclick="toggleAccountStatus(${row.id}, 1)" title="Activate">
                                        <i class="bi bi-play-circle"></i>
                                    </button>`;
                    }

                    actions += '</div>';
                    return actions;
                }
            }
        ],
        order: [[0, 'asc']],
        pageLength: 10
    });
}

function showAddAccountModal() {
    $('#accountForm')[0].reset();
    $('#account_id').val('');
    $('#initial_balance_group').show();
    $('#accountModalLabel').text('Add Bank Account');
    const modal = new bootstrap.Modal(document.getElementById('accountModal'));
    modal.show();
    // Load banks after modal is shown
    setTimeout(loadActiveBanks, 100);
}

function editAccount(accountId) {
    $.ajax({
        url: '?page=bank_account',
        method: 'POST',
        data: { action: 'get', account_id: accountId },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                const account = response.data;
                $('#account_id').val(account.id);
                $('#account_holder_id').val(account.account_holder_id);
                $('#bank_id').val(account.bank_id);
                $('#account_number').val(account.account_number);
                $('#currency_code').val(account.currency_code);
                $('#is_default').prop('checked', account.is_default == 1);
                $('#account_notes').val(account.notes);
                $('#initial_balance_group').hide();
                $('#accountModalLabel').text('Edit Bank Account');
                const modal = new bootstrap.Modal(document.getElementById('accountModal'));
                modal.show();
                loadActiveBanks();
                setTimeout(() => $('#bank_id').val(account.bank_id), 300);
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        }
    });
}

function saveAccount() {
    const formData = $('#accountForm').serialize() + '&action=save';

    $.ajax({
        url: '?page=bank_account',
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
                bootstrap.Modal.getInstance(document.getElementById('accountModal')).hide();
                accountsTable.ajax.reload();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        }
    });
}

function setDefaultAccount(accountId) {
    Swal.fire({
        title: 'Set as Default?',
        text: "This will be set as the default account",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, set as default!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '?page=bank_account',
                method: 'POST',
                data: { action: 'set_default', account_id: accountId },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        Swal.fire('Success!', response.message, 'success');
                        accountsTable.ajax.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        }
    });
}

function toggleAccountStatus(accountId, status) {
    $.ajax({
        url: '?page=bank_account',
        method: 'POST',
        data: { action: 'toggle_status', account_id: accountId, status: status },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                accountsTable.ajax.reload();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        }
    });
}

function viewActivity(accountId) {
    $.ajax({
        url: '?page=bank_account',
        method: 'POST',
        data: { action: 'get_activity', account_id: accountId },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                let html = '';
                if (response.data.length === 0) {
                    html = '<tr><td colspan="7" class="text-center text-muted">No activity found</td></tr>';
                } else {
                    response.data.forEach(activity => {
                        const directionBadge = activity.transaction_direction === 'credit'
                            ? '<span class="badge bg-success">Credit</span>'
                            : '<span class="badge bg-danger">Debit</span>';

                        html += `<tr>
                            <td>${new Date(activity.created_at).toLocaleString()}</td>
                            <td><span class="badge bg-info">${activity.transaction_type}</span></td>
                            <td>${directionBadge}</td>
                            <td>${parseFloat(activity.amount).toFixed(2)}</td>
                            <td>${parseFloat(activity.balance_before).toFixed(2)}</td>
                            <td>${parseFloat(activity.balance_after).toFixed(2)}</td>
                            <td>${activity.description || 'N/A'}</td>
                        </tr>`;
                    });
                }
                $('#activityTable tbody').html(html);
                const modal = new bootstrap.Modal(document.getElementById('activityModal'));
                modal.show();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        }
    });
}

// ==============================================
// SETTLEMENTS MANAGEMENT
// ==============================================

function loadSettlementsTable() {
    settlementsTable = $('#settlementsTable').DataTable({
        ajax: {
            url: '?page=settlement',
            type: 'POST',
            data: { action: 'list' },
            dataSrc: 'data'
        },
        columns: [
            { data: 'settlement_code' },
            { data: 'agent_name' },
            {
                data: null,
                render: function (data, type, row) {
                    return `${row.bank_name}<br><small>****${row.account_number ? row.account_number.slice(-4) : ''}</small>`;
                }
            },
            {
                data: 'requested_amount',
                render: function (data, type, row) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2
                    }) + ' ' + row.currency_code;
                }
            },
            {
                data: 'payment_method', render: function (data) {
                    return data.replace('_', ' ').toUpperCase();
                }
            },
            {
                data: 'status',
                render: function (data) {
                    let badgeClass = 'secondary';
                    if (data === 'approved') badgeClass = 'success';
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

                    if (row.status === 'pending') {
                        actions += `<button class="btn btn-success" onclick="showApproveModal(${row.id})" title="Approve">
                                        <i class="bi bi-check-circle"></i>
                                    </button>`;
                        actions += `<button class="btn btn-danger" onclick="rejectSettlement(${row.id})" title="Reject">
                                        <i class="bi bi-x-circle"></i>
                                    </button>`;
                    }

                    actions += '</div>';
                    return actions;
                }
            }
        ],
        order: [[6, 'desc']],
        pageLength: 10
    });
}

function showRequestSettlementModal() {
    $('#settlementForm')[0].reset();
    loadUserBankAccounts();
    const modal = new bootstrap.Modal(document.getElementById('settlementModal'));
    modal.show();
}

function loadUserBankAccounts() {
    // Get  current user ID from session/global variable
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
                    options += `<option value="${account.id}">${account.bank_name} - ****${account.account_number.slice(-4)} (Balance: ${parseFloat(account.balance).toFixed(2)})</option>`;
                });
                $('#settlement_bank_account_id').html(options);
            }
        }
    });
}

function requestSettlement() {
    const formData = $('#settlementForm').serialize() + '&action=request';

    $.ajax({
        url: '?page=settlement',
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
                bootstrap.Modal.getInstance(document.getElementById('settlementModal')).hide();
                settlementsTable.ajax.reload();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        }
    });
}

function showApproveModal(settlementId) {
    $('#approve_settlement_id').val(settlementId);
    const modal = new bootstrap.Modal(document.getElementById('approveSettlementModal'));
    modal.show();
}

function approveSettlement() {
    const formData = $('#approveSettlementForm').serialize() + '&action=approve';

    $.ajax({
        url: '?page=settlement',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Approved!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                bootstrap.Modal.getInstance(document.getElementById('approveSettlementModal')).hide();
                settlementsTable.ajax.reload();
                accountsTable.ajax.reload();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        }
    });
}

function rejectSettlement(settlementId) {
    Swal.fire({
        title: 'Reject Settlement',
        input: 'textarea',
        inputLabel: 'Rejection Reason',
        inputPlaceholder: 'Enter reason for rejection...',
        inputAttributes: { required: true },
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Reject'
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            $.ajax({
                url: '?page=settlement',
                method: 'POST',
                data: {
                    action: 'reject',
                    settlement_id: settlementId,
                    rejection_reason: result.value
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        Swal.fire('Rejected!', response.message, 'success');
                        settlementsTable.ajax.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        }
    });
}
