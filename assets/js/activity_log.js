/**
 * Activity Log JavaScript
 */

$(document).ready(function () {
    // Initialize DataTable
    const table = $('#activityLogTable').DataTable({
        order: [[0, 'desc']], // Sort by Time desc
        columns: [
            {
                data: 'created_at',
                render: function (data) {
                    return new Date(data).toLocaleString();
                }
            },
            {
                data: 'username',
                render: function (data, type, row) {
                    return `<div><strong>${row.full_name || 'System'}</strong></div><small class="text-muted">@${data || 'system'}</small>`;
                }
            },
            { data: 'role_name', defaultContent: '-' },
            {
                data: 'module',
                render: function (data) {
                    return `<span class="badge bg-secondary">${data || 'General'}</span>`;
                }
            },
            { data: 'action' },
            { data: 'description' },
            { data: 'ip_address', defaultContent: '-' }
        ]
    });

    // Load Filters
    loadFilters();

    // Initial Data Load
    loadLogs();

    // Handle Filter Submit
    $('#logFilterForm').on('submit', function (e) {
        e.preventDefault();
        loadLogs();
    });

    function loadFilters() {
        $.ajax({
            url: '?page=activity_log',
            method: 'POST',
            data: { action: 'get_filters' },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    // Populate Modules
                    const moduleSelect = $('#moduleFilter');
                    response.modules.forEach(m => {
                        moduleSelect.append(`<option value="${m.module}">${m.module.charAt(0).toUpperCase() + m.module.slice(1)}</option>`);
                    });

                    // Populate Users
                    const userSelect = $('#userFilter');
                    response.users.forEach(u => {
                        userSelect.append(`<option value="${u.id}">${u.username}</option>`);
                    });
                }
            }
        });
    }

    function loadLogs() {
        const formData = $('#logFilterForm').serialize();

        // Show loading state if needed, but DataTables handles processing indicator usually
        // Manually handling data fetch since we are not using server-side processing mode for simplicity

        $.ajax({
            url: '?page=activity_log',
            method: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function () {
                // table.clear().draw(); 
                // Maybe show a spinner?
            },
            success: function (response) {
                if (response.success) {
                    table.clear();
                    table.rows.add(response.data);
                    table.draw();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Failed to load activity logs', 'error');
            }
        });
    }
});
