$(document).ready(function () {
    // Sidebar toggle
    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
    });

    // Close sidebar on mobile when clicking outside
    $(document).on('click', function(e) {
        if ($(window).width() <= 992) {
            if (!$(e.target).closest('#sidebar, #sidebarCollapse').length) {
                $('#sidebar').addClass('active');
            }
        }
    });

    // Add smooth hover effects to table rows
    $('.table-hover tbody tr').on('mouseenter', function() {
        $(this).find('.btn-sm').css('opacity', '1');
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut(400, function() {
            $(this).remove();
        });
    }, 5000);

    // Animate stat values on page load
    $('.stat-value').each(function() {
        var $this = $(this);
        var text = $this.text();
        if (text.match(/^\d/)) {
            var num = parseInt(text.replace(/[^0-9]/g, ''));
            var prefix = text.match(/^[^0-9]*/)[0];
            var suffix = text.match(/[^0-9]*$/)[0];
            var current = 0;
            var duration = 1200;
            var increment = num / (duration / 16);
            
            var timer = setInterval(function() {
                current += increment;
                if (current >= num) {
                    current = num;
                    clearInterval(timer);
                }
                $this.text(prefix + Math.floor(current).toLocaleString() + suffix);
            }, 16);
        }
    });

    // Add focus effects to form inputs
    $('.form-control, .form-select').on('focus', function() {
        $(this).closest('.mb-3, .mb-4').addClass('focused');
    }).on('blur', function() {
        $(this).closest('.mb-3, .mb-4').removeClass('focused');
    });
});
