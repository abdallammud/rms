/**
 * Commission Tier Management JavaScript
 */

let tiersTable;

$(document).ready(function () {
    // Initialize DataTable
    loadTiersTable();

    // Form submission
    $('#tierForm').on('submit', function (e) {
        e.preventDefault();
        saveTier();
    });

    // Reset form when modal is closed
    $('#tierModal').on('hidden.bs.modal', function () {
        resetTierForm();
    });
});

/**
 * Load tiers DataTable
 */
function loadTiersTable() {
    tiersTable = $('#tiersTable').DataTable({
        ajax: {
            url: '?page=commission',
            type: 'POST',
            data: { action: 'list' },
            dataSrc: 'data'
        },
        columns: [
            { data: 'tier_name' },
            {
                data: 'tier_type',
                render: function (data) {
                    const badgeClass = data === 'customer' ? 'bg-info' : 'bg-success';
                    return `<span class="badge ${badgeClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    const min = parseFloat(row.min_amount).toLocaleString('en-US', { minimumFractionDigits: 2 });
                    const max = row.max_amount ? parseFloat(row.max_amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) : 'Unlimited';
                    return `${row.currency_code} ${min} - ${max}`;
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    if (row.commission_type === 'percentage') {
                        return `${row.commission_value}%`;
                    } else {
                        return `${row.currency_code} ${parseFloat(row.commission_value).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
                    }
                }
            },
            { data: 'currency_code' },
            {
                data: 'is_active',
                render: function (data) {
                    if (data == 1) {
                        return '<span class="badge bg-success">Active</span>';
                    } else {
                        return '<span class="badge bg-danger">Inactive</span>';
                    }
                }
            },
            {
                data: 'created_by_name',
                render: function (data) {
                    return data || '<span class="text-muted">N/A</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function (data, type, row) {
                    let actions = '<div class="btn-group btn-group-sm">';

                    // View change log
                    actions += `<button class="btn btn-info" onclick="viewChangeLog(${row.id})" title="Change History">
                                    <i class="bi bi-clock-history"></i>
                                </button>`;

                    // Edit button
                    actions += `<button class="btn btn-primary" onclick="editTier(${row.id})" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>`;

                    // Toggle status
                    if (row.is_active == 1) {
                        actions += `<button class="btn btn-warning" onclick="toggleTierStatus(${row.id}, 0)" title="Deactivate">
                                        <i class="bi bi-pause-circle"></i>
                                    </button>`;
                    } else {
                        actions += `<button class="btn btn-success" onclick="toggleTierStatus(${row.id}, 1)" title="Activate">
                                        <i class="bi bi-play-circle"></i>
                                    </button>`;
                    }

                    // Delete button
                    actions += `<button class="btn btn-danger" onclick="deleteTier(${row.id})" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>`;

                    actions += '</div>';
                    return actions;
                }
            }
        ],
        order: [[1, 'asc'], [2, 'asc']],
        pageLength: 10,
        responsive: true
    });
}

/**
 * Show add tier modal
 */
function showAddTierModal() {
    resetTierForm();
    $('#tierModalLabel').text('Add Commission Tier');
    $('#tierModal').modal('show');
}

/**
 * Edit tier
 */
function editTier(tierId) {
    $.ajax({
        url: '?page=commission',
        method: 'POST',
        data: {
            action: 'get',
            tier_id: tierId
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                const tier = response.data;

                $('#tier_id').val(tier.id);
                $('#tier_name').val(tier.tier_name);
                $('#tier_type').val(tier.tier_type);
                $('#min_amount').val(tier.min_amount);
                $('#max_amount').val(tier.max_amount);
                $('#commission_type').val(tier.commission_type);
                $('#commission_value').val(tier.commission_value);
                $('#currency_code').val(tier.currency_code);

                $('#tierModalLabel').text('Edit Commission Tier');
                $('#tierModal').modal('show');
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'Failed to fetch tier details', 'error');
        }
    });
}

/**
 * Save tier
 */
function saveTier() {
    const formData = $('#tierForm').serialize() + '&action=save';

    $.ajax({
        url: '?page=commission',
        method: 'POST',
        data: formData,
        dataType: 'json',
        beforeSend: function () {
            $('#tierForm button[type="submit"]').prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Saving...');
        },
        success: function (response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });

                $('#tierModal').modal('hide');
                tiersTable.ajax.reload();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'An error occurred. Please try again.', 'error');
        },
        complete: function () {
            $('#tierForm button[type="submit"]').prop('disabled', false).html('<i class="bi bi-save"></i> Save Tier');
        }
    });
}

/**
 * Delete tier
 */
function deleteTier(tierId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently delete the commission tier!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '?page=commission',
                method: 'POST',
                data: {
                    action: 'delete',
                    tier_id: tierId
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        tiersTable.ajax.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Failed to delete tier', 'error');
                }
            });
        }
    });
}

/**
 * Toggle tier status
 */
function toggleTierStatus(tierId, status) {
    const statusText = status == 1 ? 'activate' : 'deactivate';

    Swal.fire({
        title: 'Are you sure?',
        text: `Do you want to ${statusText} this commission tier?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ff6b35',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Yes, ${statusText} it!`
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '?page=commission',
                method: 'POST',
                data: {
                    action: 'toggle_status',
                    tier_id: tierId,
                    status: status
                },
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

                        tiersTable.ajax.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Failed to update tier status', 'error');
                }
            });
        }
    });
}

/**
 * View change log
 */
function viewChangeLog(tierId) {
    $.ajax({
        url: '?page=commission',
        method: 'POST',
        data: {
            action: 'get_change_log',
            tier_id: tierId
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                displayChangeLog(response.data);
                $('#changeLogModal').modal('show');
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'Failed to fetch change log', 'error');
        }
    });
}

/**
 * Display change log
 */
function displayChangeLog(logs) {
    let html = '';

    if (logs.length === 0) {
        html = '<tr><td colspan="5" class="text-center text-muted">No changes recorded</td></tr>';
    } else {
        logs.forEach(log => {
            html += `
                <tr>
                    <td><strong>${log.field_changed.replace(/_/g, ' ').toUpperCase()}</strong></td>
                    <td>${log.old_value || 'N/A'}</td>
                    <td><span class="text-success">${log.new_value || 'N/A'}</span></td>
                    <td>${log.changed_by_name || 'System'}</td>
                    <td>${new Date(log.changed_at).toLocaleString()}</td>
                </tr>
            `;
        });
    }

    $('#changeLogBody').html(html);
}

/**
 * Reset tier form
 */
function resetTierForm() {
    $('#tierForm')[0].reset();
    $('#tier_id').val('');
}
