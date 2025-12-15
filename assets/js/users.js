/**
 * User Management JavaScript
 */

let usersTable;

$(document).ready(function () {
    // Initialize DataTable
    loadUsersTable();

    // User form submission
    $('#userForm').on('submit', function (e) {
        e.preventDefault();
        saveUser();
    });

    // Password form submission
    $('#passwordForm').on('submit', function (e) {
        e.preventDefault();
        changePassword();
    });

    // Reset forms when modals are closed
    $('#userModal').on('hidden.bs.modal', function () {
        resetUserForm();
    });

    $('#passwordModal').on('hidden.bs.modal', function () {
        $('#passwordForm')[0].reset();
    });
});

/**
 * Load users DataTable
 */
function loadUsersTable() {
    usersTable = $('#usersTable').DataTable({
        ajax: {
            url: '?page=users',
            type: 'POST',
            data: { action: 'list' },
            dataSrc: 'data'
        },
        columns: [
            { data: 'username' },
            { data: 'full_name' },
            { data: 'email' },
            {
                data: 'role_name',
                render: function (data) {
                    return '<span class="badge bg-primary">' + data + '</span>';
                }
            },
            {
                data: 'branch_name',
                render: function (data) {
                    return data || '<span class="text-muted">N/A</span>';
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    let badges = '';

                    if (row.is_suspended == 1) {
                        badges += '<span class="badge bg-danger me-1">Suspended</span>';
                    } else if (row.is_active == 1) {
                        badges += '<span class="badge bg-success me-1">Active</span>';
                    } else {
                        badges += '<span class="badge bg-secondary me-1">Inactive</span>';
                    }

                    return badges;
                }
            },
            {
                data: 'last_login',
                render: function (data) {
                    return data ? new Date(data).toLocaleString() : '<span class="text-muted">Never</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function (data, type, row) {
                    let actions = '<div class="btn-group btn-group-sm">';

                    // View button
                    actions += `<button class="btn btn-info" onclick="viewUser(${row.id})" title="View">
                                    <i class="bi bi-eye"></i>
                                </button>`;

                    // Edit button
                    actions += `<button class="btn btn-primary" onclick="editUser(${row.id})" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>`;

                    // Password button
                    actions += `<button class="btn btn-warning" onclick="showPasswordModal(${row.id})" title="Change Password">
                                    <i class="bi bi-key"></i>
                                </button>`;

                    // Suspend/Unsuspend button
                    if (row.is_suspended == 1) {
                        actions += `<button class="btn btn-success" onclick="suspendUser(${row.id}, 0)" title="Unsuspend">
                                        <i class="bi bi-play-circle"></i>
                                    </button>`;
                    } else {
                        actions += `<button class="btn btn-secondary" onclick="suspendUser(${row.id}, 1)" title="Suspend">
                                        <i class="bi bi-pause-circle"></i>
                                    </button>`;
                    }

                    // Delete button
                    actions += `<button class="btn btn-danger" onclick="deleteUser(${row.id})" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>`;

                    actions += '</div>';
                    return actions;
                }
            }
        ],
        order: [[6, 'desc']],
        pageLength: 10,
        responsive: true
    });
}

/**
 * Show add user modal
 */
function showAddUserModal() {
    resetUserForm();
    $('#userModalLabel').text('Add New User');
    $('#password').prop('required', true);
    $('#passwordRequired').show();
    $('#userModal').modal('show');
}

/**
 * View user details
 */
function viewUser(userId) {
    $.ajax({
        url: '?page=users',
        method: 'POST',
        data: {
            action: 'get',
            user_id: userId
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                const user = response.data;

                let statusBadge = user.is_suspended == 1 ?
                    '<span class="badge bg-danger">Suspended</span>' :
                    user.is_active == 1 ?
                        '<span class="badge bg-success">Active</span>' :
                        '<span class="badge bg-secondary">Inactive</span>';

                Swal.fire({
                    title: user.full_name,
                    html: `
                        <div class="text-start">
                            <p><strong>Username:</strong> ${user.username}</p>
                            <p><strong>Email:</strong> ${user.email}</p>
                            <p><strong>Phone:</strong> ${user.phone || 'N/A'}</p>
                            <p><strong>Role:</strong> <span class="badge bg-primary">${user.role_name}</span></p>
                            <p><strong>Branch:</strong> ${user.branch_name || 'N/A'}</p>
                            <p><strong>Status:</strong> ${statusBadge}</p>
                            <p><strong>Last Login:</strong> ${user.last_login ? new Date(user.last_login).toLocaleString() : 'Never'}</p>
                            <p><strong>Created:</strong> ${new Date(user.created_at).toLocaleString()}</p>
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
            Swal.fire('Error', 'Failed to fetch user details', 'error');
        }
    });
}

/**
 * Edit user
 */
function editUser(userId) {
    $.ajax({
        url: '?page=users',
        method: 'POST',
        data: {
            action: 'get',
            user_id: userId
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                const user = response.data;

                $('#user_id').val(user.id);
                $('#username').val(user.username);
                $('#full_name').val(user.full_name);
                $('#email').val(user.email);
                $('#phone').val(user.phone);
                $('#role_id').val(user.role_id);
                $('#branch_id').val(user.branch_id);

                $('#password').prop('required', false);
                $('#passwordRequired').hide();

                $('#userModalLabel').text('Edit User');
                $('#userModal').modal('show');
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'Failed to fetch user details', 'error');
        }
    });
}

/**
 * Save user
 */
function saveUser() {
    const formData = $('#userForm').serialize() + '&action=save';

    $.ajax({
        url: '?page=users',
        method: 'POST',
        data: formData,
        dataType: 'json',
        beforeSend: function () {
            $('#userForm button[type="submit"]').prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Saving...');
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

                $('#userModal').modal('hide');
                usersTable.ajax.reload();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            // Sometimes the server returns a response that can't be parsed by jQuery
            // (e.g., stray whitespace or warnings) which triggers the error handler
            // even though the operation actually succeeded. Try to parse the response
            // and handle a successful logical response gracefully.
            try {
                var parsed = JSON.parse(jqXHR.responseText);
                if (parsed && parsed.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: parsed.message || 'Operation completed successfully',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    $('#userModal').modal('hide');
                    usersTable.ajax.reload();
                    return;
                } else if (parsed && parsed.message) {
                    Swal.fire('Error', parsed.message, 'error');
                    return;
                }
            } catch (e) {
                // Not JSON or parse failed - fall through to generic error
            }

            Swal.fire('Error', 'An error occurred. Please try again.', 'error');
        },
        complete: function () {
            $('#userForm button[type="submit"]').prop('disabled', false).html('<i class="bi bi-save"></i> Save User');
        }
    });
}

/**
 * Delete user
 */
function deleteUser(userId) {
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
                url: '?page=users',
                method: 'POST',
                data: {
                    action: 'delete',
                    user_id: userId
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

                        usersTable.ajax.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Failed to delete user', 'error');
                }
            });
        }
    });
}

/**
 * Suspend/Unsuspend user
 */
function suspendUser(userId, status) {
    const statusText = status == 1 ? 'suspend' : 'unsuspend';

    Swal.fire({
        title: 'Are you sure?',
        text: `Do you want to ${statusText} this user?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ff6b35',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Yes, ${statusText} user!`
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '?page=users',
                method: 'POST',
                data: {
                    action: 'suspend',
                    user_id: userId,
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

                        usersTable.ajax.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Failed to update user status', 'error');
                }
            });
        }
    });
}

/**
 * Show password change modal
 */
function showPasswordModal(userId) {
    $('#pwd_user_id').val(userId);
    $('#passwordModal').modal('show');
}

/**
 * Change password
 */
function changePassword() {
    const newPassword = $('#new_password').val();
    const confirmPassword = $('#confirm_password').val();

    if (newPassword !== confirmPassword) {
        Swal.fire('Error', 'Passwords do not match', 'error');
        return;
    }

    if (newPassword.length < 6) {
        Swal.fire('Error', 'Password must be at least 6 characters', 'error');
        return;
    }

    const formData = $('#passwordForm').serialize() + '&action=change_password';

    $.ajax({
        url: '?page=users',
        method: 'POST',
        data: formData,
        dataType: 'json',
        beforeSend: function () {
            $('#passwordForm button[type="submit"]').prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Changing...');
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

                $('#passwordModal').modal('hide');
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'Failed to change password', 'error');
        },
        complete: function () {
            $('#passwordForm button[type="submit"]').prop('disabled', false).html('<i class="bi bi-key"></i> Change Password');
        }
    });
}

/**
 * Reset user form
 */
function resetUserForm() {
    $('#userForm')[0].reset();
    $('#user_id').val('');
    $('#password').prop('required', true);
    $('#passwordRequired').show();
}
