/**
 * Institute Warehouse Admin JS
 */
jQuery(document).ready(function($) {
    // Close modals when clicking outside
    $(document).on('click', '.iw-modal', function(e) {
        if (e.target === this) $(this).hide();
    });

    // Close modals with Escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') $('.iw-modal').hide();
    });
});
