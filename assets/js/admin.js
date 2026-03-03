/**
 * Institute Warehouse Admin JS
 */
jQuery(document).ready(function($) {
    // Initialize Select2 on all selects inside .iw-wrap
    window.iwInitSelect2 = function(container) {
        var $target = container ? $(container).find('select.regular-text, select.iw-select2') : $('.iw-wrap select.regular-text, .iw-wrap select.iw-select2');
        $target.not('.select2-hidden-accessible').each(function() {
            $(this).select2({
                dir: 'rtl',
                width: '100%',
                placeholder: $(this).find('option:first').text() || 'اختر...',
                allowClear: true
            });
        });
    };

    // Reinitialize a specific select after its options have been updated via AJAX
    window.iwRefreshSelect2 = function(selector) {
        var $el = $(selector);
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
        $el.select2({
            dir: 'rtl',
            width: '100%',
            placeholder: $el.find('option:first').text() || 'اختر...',
            allowClear: true
        });
    };

    // Initial Select2 setup (for selects that already have their options)
    iwInitSelect2();

    // Fix Select2 clear (X) button - ensure it clears selection properly
    $(document).on('select2:unselecting', '.iw-wrap select', function(e) {
        $(this).data('unselecting', true);
    });
    $(document).on('select2:opening', '.iw-wrap select', function(e) {
        if ($(this).data('unselecting')) {
            $(this).removeData('unselecting');
            e.preventDefault();
        }
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
