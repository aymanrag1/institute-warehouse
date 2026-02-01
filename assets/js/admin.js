/**
 * Institute Warehouse Admin JS
 */
jQuery(document).ready(function($) {
    // Initialize Select2 on all existing selects
    function initSelect2() {
        $('.iw-wrap select.regular-text, .iw-wrap select.iw-select2').not('.select2-hidden-accessible').select2({
            dir: 'rtl',
            width: '100%',
            placeholder: 'اختر...',
            allowClear: true
        });
    }
    initSelect2();

    // Re-initialize Select2 when new rows are added
    var observer = new MutationObserver(function() {
        setTimeout(initSelect2, 150);
    });

    $('.iw-wrap').each(function() {
        observer.observe(this, { childList: true, subtree: true });
    });

    // Close modals when clicking outside
    $(document).on('click', '.iw-modal', function(e) {
        if (e.target === this) $(this).hide();
    });

    // Close modals with Escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') $('.iw-modal').hide();
    });
});
