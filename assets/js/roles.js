/**
 * Role Management JavaScript
 */

let rolesTable;

$(document).ready(function () {
    // Initialize DataTable
    loadRolesTable();

    // Role form submission
    $('#roleForm').on('submit', function (e) {
        e.preventDefault();
        saveRole();
    });

    // Permissions form submission
    $('#permissionsForm').on('submit', function (e) {
        e.preventDefault();
        savePermissions();
    });

    // Reset forms when modals are closed
    $('#roleModal').on('hidden.bs.modal', function () {
        resetRoleForm();
    });
});

/**
 * Load roles DataTable
 */
function loadRolesTable() {
    rolesTable = $('#rolesTable').DataTable({
        ajax: {
            url: '?page=roles',
            type: 'POST',
            data: { action: 'list' },
            dataSrc: 'data'
        },
        columns: [
            { data: 'role_name' },
            {
                data: 'description',
                render: function (data) {
                    return data || '<span class="text-muted">N/A</span>';
                }
            },
            {
                data: 'user_count',
                render: function (data) {
                    return '<span class="badge bg-info">' + data + ' users</span>';
                }
            },
            {
                data: 'permission_count',
                render: function (data) {
                    return '<span class="badge bg-secondary">' + data + ' permissions</span>';
                }
            },
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

                    // Permissions button
                    actions += `<button class="btn btn-info" onclick="showPermissionsModal(${row.id})" title="Manage Permissions">
                                    <i class="bi bi-shield-check"></i>
                                </button>`;

                    // Edit button
                    actions += `<button class="btn btn-primary" onclick="editRole(${row.id})" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>`;

                    // Delete button
                    if (row.user_count == 0) {
                        actions += `<button class="btn btn-danger" onclick="deleteRole(${row.id})" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>`;
                    }

                    actions += '</div>';
                    return actions;
                }
            }
        ],
        order: [[5, 'desc']],
        pageLength: 10,
        responsive: true
    });
}

/**
 * Show add role modal
 */
function showAddRoleModal() {
    resetRoleForm();
    $('#roleModalLabel').text('Add New Role');
    $('#roleModal').modal('show');
}

/**
 * Edit role
 */
function editRole(roleId) {
    $.ajax({
        url: '?page=roles',
        method: 'POST',
        data: {
            action: 'get',
            role_id: roleId
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                const role = response.data;

                $('#role_id').val(role.id);
                $('#role_name').val(role.role_name);
                $('#description').val(role.description);

                $('#roleModalLabel').text('Edit Role');
                $('#roleModal').modal('show');
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'Failed to fetch role details', 'error');
        }
    });
}

/**
 * Save role
 */
function saveRole() {
    const formData = $('#roleForm').serialize() + '&action=save';

    $.ajax({
        url: '?page=roles',
        method: 'POST',
        data: formData,
        dataType: 'json',
        beforeSend: function () {
            $('#roleForm button[type="submit"]').prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Saving...');
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

                $('#roleModal').modal('hide');
                rolesTable.ajax.reload();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'An error occurred. Please try again.', 'error');
        },
        complete: function () {
            $('#roleForm button[type="submit"]').prop('disabled', false).html('<i class="bi bi-save"></i> Save Role');
        }
    });
}

/**
 * Delete role
 */
function deleteRole(roleId) {
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
                url: '?page=roles',
                method: 'POST',
                data: {
                    action: 'delete',
                    role_id: roleId
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

                        rolesTable.ajax.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Failed to delete role', 'error');
                }
            });
        }
    });
}

/**
 * Show permissions modal
 */
function showPermissionsModal(roleId) {
    $('#perm_role_id').val(roleId);
    $('#permissionsModal').modal('show');
    loadPermissions(roleId);
}

/**
 * Load permissions
 */
function loadPermissions(roleId) {
    // Load all permissions
    $.ajax({
        url: '?page=roles',
        method: 'POST',
        data: { action: 'get_permissions' },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                const permissionsGrouped = response.data;

                // Load role's current permissions
                $.ajax({
                    url: '?page=roles',
                    method: 'POST',
                    data: {
                        action: 'get_role_permissions',
                        role_id: roleId
                    },
                    dataType: 'json',
                    success: function (rolePerms) {
                        displayPermissions(permissionsGrouped, rolePerms.data);
                    }
                });
            }
        }
    });
}

/**
 * Display permissions checkboxes
 */
function displayPermissions(permissionsGrouped, selectedPermissions) {
    let html = '';

    for (const module in permissionsGrouped) {
        html += `
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <strong>${module}</strong>
                    <div class="form-check form-check-inline float-end">
                        <input class="form-check-input module-select-all" type="checkbox" data-module="${module}">
                        <label class="form-check-label">Select All</label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
        `;

        permissionsGrouped[module].forEach(permission => {
            const checked = selectedPermissions.includes(permission.id.toString()) ? 'checked' : '';
            html += `
                <div class="col-md-6 mb-2">
                    <div class="form-check">
                        <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" 
                               value="${permission.id}" id="perm_${permission.id}" data-module="${module}" ${checked}>
                        <label class="form-check-label" for="perm_${permission.id}">
                            ${permission.permission_name}
                            <small class="text-muted d-block">${permission.description || ''}</small>
                        </label>
                    </div>
                </div>
            `;
        });

        html += `
                    </div>
                </div>
            </div>
        `;
    }

    $('#permissionsContainer').html(html);

    // Handle select all for each module
    $('.module-select-all').on('change', function () {
        const module = $(this).data('module');
        const checked = $(this).is(':checked');
        $(`.permission-checkbox[data-module="${module}"]`).prop('checked', checked);
    });
}

/**
 * Save permissions
 */
function savePermissions() {
    const formData = $('#permissionsForm').serialize() + '&action=save_permissions';

    $.ajax({
        url: '?page=roles',
        method: 'POST',
        data: formData,
        dataType: 'json',
        beforeSend: function () {
            $('#permissionsForm button[type="submit"]').prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Saving...');
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

                $('#permissionsModal').modal('hide');
                rolesTable.ajax.reload();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'Failed to save permissions', 'error');
        },
        complete: function () {
            $('#permissionsForm button[type="submit"]').prop('disabled', false).html('<i class="bi bi-save"></i> Save Permissions');
        }
    });
}

/**
 * Reset role form
 */
function resetRoleForm() {
    $('#roleForm')[0].reset();
    $('#role_id').val('');
}
