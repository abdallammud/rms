/**
 * Main JavaScript File
 */

$(document).ready(function () {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Sidebar toggle for mobile
    $('[data-bs-toggle="collapse"]').on('click', function () {
        var target = $(this).data('bs-target');
        if ($(window).width() < 992) {
            $(target).toggleClass('show');
        }
    });

    // Close sidebar on outside click (mobile)
    $(document).on('click', function (e) {
        if ($(window).width() < 992) {
            if (!$(e.target).closest('#sidebarMenu, [data-bs-toggle="collapse"]').length) {
                $('#sidebarMenu').removeClass('show');
            }
        }
    });

    // Close sidebar when clicking a link on mobile
    $('#sidebarMenu a').on('click', function () {
        if ($(window).width() < 992) {
            $('#sidebarMenu').removeClass('show');
        }
    });

    // Add fade-in animation to cards
    $('.card').addClass('fade-in');

    // Auto-hide alerts after 5 seconds
    setTimeout(function () {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Smooth scroll for anchor links
    $('a[href^="#"]').on('click', function (e) {
        e.preventDefault();
        var target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 80
            }, 600);
        }
    });
});
