/**
 * Branch Management JavaScript
 */

let branchesTable;

$(document).ready(function () {
    // Initialize DataTable
    loadBranchesTable();

    // Form submission
    $('#branchForm').on('submit', function (e) {
        e.preventDefault();
        saveBranch();
    });

    // Reset form when modal is closed
    $('#branchModal').on('hidden.bs.modal', function () {
        resetBranchForm();
    });
});

/**
 * Load branches DataTable
 */
function loadBranchesTable() {
    branchesTable = $('#branchesTable').DataTable({
        ajax: {
            url: '?page=branches',
            type: 'POST',
            data: { action: 'list' },
            dataSrc: 'data'
        },
        columns: [
            { data: 'branch_code' },
            { data: 'branch_name' },
            {
                data: 'location',
                render: function (data) {
                    return data || '<span class="text-muted">N/A</span>';
                }
            },
            {
                data: 'manager_name',
                render: function (data) {
                    return data || '<span class="text-muted">N/A</span>';
                }
            },
            {
                data: 'phone',
                render: function (data) {
                    return data || '<span class="text-muted">N/A</span>';
                }
            },
            {
                data: 'email',
                render: function (data) {
                    return data || '<span class="text-muted">N/A</span>';
                }
            },
            {
                data: 'is_active',
                render: function (data, type, row) {
                    if (data == 1) {
                        return '<span class="badge bg-success">Active</span>';
                    } else {
                        return '<span class="badge bg-danger">Inactive</span>';
                    }
                }
            },
            {
                data: 'created_at',
                render: function (data) {
                    return new Date(data).toLocaleDateString();
                }
            },
            {
                data: null,
                orderable: false,
                render: function (data, type, row) {
                    let actions = '<div class="btn-group btn-group-sm">';

                    // View button
                    actions += `<button class="btn btn-info" onclick="viewBranch(${row.id})" title="View">
                                    <i class="bi bi-eye"></i>
                                </button>`;

                    // Edit button (if has permission)
                    actions += `<button class="btn btn-primary" onclick="editBranch(${row.id})" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>`;

                    // Toggle status button
                    if (row.is_active == 1) {
                        actions += `<button class="btn btn-warning" onclick="toggleBranchStatus(${row.id}, 0)" title="Deactivate">
                                        <i class="bi bi-pause-circle"></i>
                                    </button>`;
                    } else {
                        actions += `<button class="btn btn-success" onclick="toggleBranchStatus(${row.id}, 1)" title="Activate">
                                        <i class="bi bi-play-circle"></i>
                                    </button>`;
                    }

                    // Delete button (if has permission)
                    actions += `<button class="btn btn-danger" onclick="deleteBranch(${row.id})" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>`;

                    actions += '</div>';
                    return actions;
                }
            }
        ],
        order: [[7, 'desc']],
        pageLength: 10,
        responsive: true,
        language: {
            emptyTable: "No branches found"
        }
    });
}

/**
 * Show add branch modal
 */
function showAddBranchModal() {
    resetBranchForm();
    $('#branchModalLabel').text('Add New Branch');
    $('#branchModal').modal('show');
}

/**
 * View branch details
 */
function viewBranch(branchId) {
    $.ajax({
        url: '?page=branches',
        method: 'POST',
        data: {
            action: 'get',
            branch_id: branchId
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                const branch = response.data;

                Swal.fire({
                    title: branch.branch_name,
                    html: `
                        <div class="text-start">
                            <p><strong>Branch Code:</strong> ${branch.branch_code}</p>
                            <p><strong>Location:</strong> ${branch.location || 'N/A'}</p>
                            <p><strong>Manager:</strong> ${branch.manager_name || 'N/A'}</p>
                            <p><strong>Phone:</strong> ${branch.phone || 'N/A'}</p>
                            <p><strong>Email:</strong> ${branch.email || 'N/A'}</p>
                            <p><strong>Status:</strong> ${branch.is_active == 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'}</p>
                            <p><strong>Created:</strong> ${new Date(branch.created_at).toLocaleString()}</p>
                        </div>
                    `,
                    icon: 'info',
                    confirmButtonText: 'Close'
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'Failed to fetch branch details', 'error');
        }
    });
}

/**
 * Edit branch
 */
function editBranch(branchId) {
    $.ajax({
        url: '?page=branches',
        method: 'POST',
        data: {
            action: 'get',
            branch_id: branchId
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                const branch = response.data;

                $('#branch_id').val(branch.id);
                $('#branch_name').val(branch.branch_name);
                $('#branch_code').val(branch.branch_code);
                $('#location').val(branch.location);
                $('#manager_name').val(branch.manager_name);
                $('#phone').val(branch.phone);
                $('#email').val(branch.email);

                $('#branchModalLabel').text('Edit Branch');
                $('#branchModal').modal('show');
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'Failed to fetch branch details', 'error');
        }
    });
}

/**
 * Save branch
 */
function saveBranch() {
    const formData = $('#branchForm').serialize() + '&action=save';

    $.ajax({
        url: '?page=branches',
        method: 'POST',
        data: formData,
        dataType: 'json',
        beforeSend: function () {
            $('#branchForm button[type="submit"]').prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Saving...');
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

                $('#branchModal').modal('hide');
                branchesTable.ajax.reload();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'An error occurred. Please try again.', 'error');
        },
        complete: function () {
            $('#branchForm button[type="submit"]').prop('disabled', false).html('<i class="bi bi-save"></i> Save Branch');
        }
    });
}

/**
 * Delete branch
 */
function deleteBranch(branchId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '?page=branches',
                method: 'POST',
                data: {
                    action: 'delete',
                    branch_id: branchId
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

                        branchesTable.ajax.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Failed to delete branch', 'error');
                }
            });
        }
    });
}

/**
 * Toggle branch status
 */
function toggleBranchStatus(branchId, status) {
    const statusText = status == 1 ? 'activate' : 'deactivate';

    Swal.fire({
        title: 'Are you sure?',
        text: `Do you want to ${statusText} this branch?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ff6b35',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Yes, ${statusText} it!`
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '?page=branches',
                method: 'POST',
                data: {
                    action: 'toggle_status',
                    branch_id: branchId,
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

                        branchesTable.ajax.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Failed to update branch status', 'error');
                }
            });
        }
    });
}

/**
 * Reset branch form
 */
function resetBranchForm() {
    $('#branchForm')[0].reset();
    $('#branch_id').val('');
}
