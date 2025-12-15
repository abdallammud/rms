/**
 * Profile JavaScript
 */

$(document).ready(function () {

    // Update Profile
    $('#profileForm').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();

        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

        $.ajax({
            url: '?page=profile',
            method: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload(); // Reload to update header info
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'An unexpected error occurred', 'error');
            },
            complete: function () {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Tab Class Toggling
    const activeClasses = ['bg-primary', 'text-white', 'fw-bold'];
    const inactiveClasses = ['bg-secondary', 'bg-opacity-10', 'text-dark', 'fw-bold'];

    function updateTabClasses() {
        $('#profileTabs .nav-link').each(function () {
            if ($(this).hasClass('active')) {
                $(this).addClass(activeClasses.join(' ')).removeClass(inactiveClasses.join(' '));
            } else {
                $(this).addClass(inactiveClasses.join(' ')).removeClass(activeClasses.join(' '));
            }
        });
    }

    // Initial run
    updateTabClasses();

    // On tab shown event
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        updateTabClasses();
    });

    // Change Password
    $('#passwordForm').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();

        const newPass = form.find('input[name="new_password"]').val();
        const confirmPass = form.find('input[name="confirm_password"]').val();

        if (newPass !== confirmPass) {
            Swal.fire('Error', 'New passwords do not match', 'error');
            return;
        }

        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Updating...');

        $.ajax({
            url: '?page=profile',
            method: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    Swal.fire('Success', response.message, 'success');
                    form[0].reset();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'An unexpected error occurred', 'error');
            },
            complete: function () {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
