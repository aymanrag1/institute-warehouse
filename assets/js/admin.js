/**
 * Institute Warehouse - Admin JavaScript
 * نظام إدارة مستودعات المعهد - جافاسكريبت لوحة التحكم
 */

(function($) {
    'use strict';

    // Global namespace
    window.IW = window.IW || {};

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        IW.init();
    });

    /**
     * Main initialization
     */
    IW.init = function() {
        IW.initModals();
        IW.initTabs();
        IW.initConfirmDelete();
        IW.initFileUpload();
        IW.initAutocomplete();
        IW.initDatepicker();
        IW.initPermitItems();
        IW.initAlerts();
    };

    /**
     * Modal functionality
     */
    IW.initModals = function() {
        // Open modal
        $(document).on('click', '[data-modal]', function(e) {
            e.preventDefault();
            var modalId = $(this).data('modal');
            IW.openModal(modalId);
        });

        // Close modal on X button
        $(document).on('click', '.iw-modal-close', function() {
            $(this).closest('.iw-modal').removeClass('active');
        });

        // Close modal on outside click
        $(document).on('click', '.iw-modal', function(e) {
            if ($(e.target).hasClass('iw-modal')) {
                $(this).removeClass('active');
            }
        });

        // Close modal on ESC key
        $(document).on('keyup', function(e) {
            if (e.keyCode === 27) {
                $('.iw-modal.active').removeClass('active');
            }
        });
    };

    IW.openModal = function(modalId) {
        $('#' + modalId).addClass('active');
    };

    IW.closeModal = function(modalId) {
        $('#' + modalId).removeClass('active');
    };

    /**
     * Tab functionality
     */
    IW.initTabs = function() {
        $(document).on('click', '.iw-tab', function(e) {
            e.preventDefault();
            var tabId = $(this).data('tab');
            var $container = $(this).closest('.iw-tabs-container');

            // Update active tab
            $container.find('.iw-tab').removeClass('active');
            $(this).addClass('active');

            // Show corresponding content
            $container.find('.iw-tab-content').removeClass('active');
            $container.find('#' + tabId).addClass('active');
        });
    };

    /**
     * Confirm delete dialogs
     */
    IW.initConfirmDelete = function() {
        $(document).on('click', '.confirm-delete', function(e) {
            var message = $(this).data('confirm') || iw_strings.confirm_delete;
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    };

    /**
     * File upload drag & drop
     */
    IW.initFileUpload = function() {
        var $dropZone = $('.iw-file-upload');

        if (!$dropZone.length) return;

        $dropZone.on('click', function() {
            $(this).find('input[type="file"]').click();
        });

        $dropZone.on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('dragover');
        });

        $dropZone.on('dragleave drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
        });

        $dropZone.on('drop', function(e) {
            var files = e.originalEvent.dataTransfer.files;
            var $input = $(this).find('input[type="file"]');
            $input[0].files = files;
            $input.trigger('change');
        });
    };

    /**
     * Autocomplete functionality
     */
    IW.initAutocomplete = function() {
        var $autocompletes = $('.iw-autocomplete input');

        $autocompletes.each(function() {
            var $input = $(this);
            var $wrapper = $input.closest('.iw-autocomplete');
            var source = $input.data('source');
            var minChars = $input.data('min-chars') || 2;
            var $results = $('<div class="iw-autocomplete-results"></div>');

            $wrapper.append($results);

            var searchTimeout;

            $input.on('input', function() {
                var query = $(this).val();

                clearTimeout(searchTimeout);

                if (query.length < minChars) {
                    $results.removeClass('active').empty();
                    return;
                }

                searchTimeout = setTimeout(function() {
                    IW.searchAutocomplete(source, query, $results, $input);
                }, 300);
            });

            $input.on('blur', function() {
                setTimeout(function() {
                    $results.removeClass('active');
                }, 200);
            });

            $(document).on('click', '.iw-autocomplete-item', function() {
                var value = $(this).data('value');
                var text = $(this).text();
                $input.val(text).data('selected-id', value);
                $results.removeClass('active');
                $input.trigger('autocomplete:select', [value, text]);
            });
        });
    };

    IW.searchAutocomplete = function(source, query, $results, $input) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: source,
                search: query,
                nonce: iw_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.length) {
                    var html = '';
                    $.each(response.data, function(i, item) {
                        html += '<div class="iw-autocomplete-item" data-value="' + item.id + '">' + item.text + '</div>';
                    });
                    $results.html(html).addClass('active');
                } else {
                    $results.removeClass('active').empty();
                }
            }
        });
    };

    /**
     * Initialize datepicker
     */
    IW.initDatepicker = function() {
        if ($.fn.datepicker) {
            $('.iw-datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                isRTL: true
            });
        }
    };

    /**
     * Permit items management
     */
    IW.initPermitItems = function() {
        // Add new item row
        $(document).on('click', '.add-permit-item', function(e) {
            e.preventDefault();
            var $table = $(this).closest('.iw-box').find('.iw-items-table tbody');
            var $template = $table.find('tr.template').clone();

            $template.removeClass('template').show();
            $table.append($template);

            IW.updateItemNumbers();
        });

        // Remove item row
        $(document).on('click', '.remove-item', function(e) {
            e.preventDefault();
            var $row = $(this).closest('tr');

            if ($('.iw-items-table tbody tr:visible').length > 1) {
                $row.remove();
                IW.updateItemNumbers();
                IW.calculateTotals();
            } else {
                alert(iw_strings.min_one_item);
            }
        });

        // Product selection change
        $(document).on('change', '.item-product', function() {
            var $row = $(this).closest('tr');
            var productId = $(this).val();

            if (productId) {
                IW.loadProductInfo(productId, $row);
            }
        });

        // Quantity change
        $(document).on('input', '.item-quantity, .item-price', function() {
            IW.calculateRowTotal($(this).closest('tr'));
            IW.calculateTotals();
        });
    };

    IW.updateItemNumbers = function() {
        $('.iw-items-table tbody tr:visible').each(function(index) {
            $(this).find('.item-number').text(index + 1);
        });
    };

    IW.loadProductInfo = function(productId, $row) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'iw_get_product_info',
                product_id: productId,
                nonce: iw_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var product = response.data;
                    $row.find('.item-unit').text(product.unit);
                    $row.find('.item-price').val(product.price);
                    $row.find('.item-available').text(product.available_stock);
                    $row.find('.item-location').val(product.storage_location);
                    IW.calculateRowTotal($row);
                    IW.calculateTotals();
                }
            }
        });
    };

    IW.calculateRowTotal = function($row) {
        var quantity = parseFloat($row.find('.item-quantity').val()) || 0;
        var price = parseFloat($row.find('.item-price').val()) || 0;
        var total = quantity * price;
        $row.find('.item-total').text(IW.formatNumber(total));
    };

    IW.calculateTotals = function() {
        var grandTotal = 0;
        var totalItems = 0;

        $('.iw-items-table tbody tr:visible').each(function() {
            var quantity = parseFloat($(this).find('.item-quantity').val()) || 0;
            var price = parseFloat($(this).find('.item-price').val()) || 0;
            grandTotal += quantity * price;
            totalItems += quantity;
        });

        $('.grand-total').text(IW.formatNumber(grandTotal));
        $('.total-items').text(totalItems);
    };

    /**
     * Format number with locale
     */
    IW.formatNumber = function(num) {
        return num.toLocaleString('ar-SA', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    /**
     * Alerts management
     */
    IW.initAlerts = function() {
        // Mark alert as read
        $(document).on('click', '.mark-alert-read', function(e) {
            e.preventDefault();
            var alertId = $(this).data('alert-id');
            var $item = $(this).closest('.iw-alert-item');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'iw_mark_alert_read',
                    alert_id: alertId,
                    nonce: iw_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $item.fadeOut();
                    }
                }
            });
        });

        // Resolve alert
        $(document).on('click', '.resolve-alert', function(e) {
            e.preventDefault();
            var alertId = $(this).data('alert-id');
            var $item = $(this).closest('.iw-alert-item');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'iw_resolve_alert',
                    alert_id: alertId,
                    nonce: iw_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $item.fadeOut();
                    }
                }
            });
        });
    };

    /**
     * AJAX helper function
     */
    IW.ajax = function(action, data, successCallback, errorCallback) {
        data.action = action;
        data.nonce = iw_ajax.nonce;

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            beforeSend: function() {
                IW.showLoading();
            },
            success: function(response) {
                IW.hideLoading();
                if (response.success) {
                    if (typeof successCallback === 'function') {
                        successCallback(response.data);
                    }
                } else {
                    if (typeof errorCallback === 'function') {
                        errorCallback(response.data);
                    } else {
                        IW.showError(response.data);
                    }
                }
            },
            error: function(xhr, status, error) {
                IW.hideLoading();
                if (typeof errorCallback === 'function') {
                    errorCallback(error);
                } else {
                    IW.showError(iw_strings.ajax_error);
                }
            }
        });
    };

    /**
     * Show loading overlay
     */
    IW.showLoading = function() {
        if (!$('.iw-overlay').length) {
            $('body').append('<div class="iw-overlay"><div class="iw-loading"></div></div>');
        }
        $('.iw-overlay').show();
    };

    /**
     * Hide loading overlay
     */
    IW.hideLoading = function() {
        $('.iw-overlay').hide();
    };

    /**
     * Show success message
     */
    IW.showSuccess = function(message) {
        IW.showNotice(message, 'success');
    };

    /**
     * Show error message
     */
    IW.showError = function(message) {
        IW.showNotice(message, 'error');
    };

    /**
     * Show notice
     */
    IW.showNotice = function(message, type) {
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').first().after($notice);

        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    };

    /**
     * Print functionality
     */
    IW.print = function(elementId) {
        var $element = $('#' + elementId);
        var printContents = $element.html();
        var originalContents = document.body.innerHTML;

        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;

        // Re-initialize after restoring content
        IW.init();
    };

    /**
     * Excel Export functionality
     */
    IW.exportToExcel = function(tableId, filename) {
        var $table = $('#' + tableId);
        var html = $table.prop('outerHTML');

        // Create blob and download
        var blob = new Blob(['\ufeff', html], {
            type: 'application/vnd.ms-excel'
        });

        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = filename + '.xls';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    };

    /**
     * Form validation
     */
    IW.validateForm = function($form) {
        var isValid = true;
        var firstError = null;

        $form.find('[required]').each(function() {
            var $field = $(this);
            var value = $field.val();

            if (!value || value.trim() === '') {
                isValid = false;
                $field.addClass('error');

                if (!firstError) {
                    firstError = $field;
                }
            } else {
                $field.removeClass('error');
            }
        });

        if (firstError) {
            firstError.focus();
        }

        return isValid;
    };

    /**
     * Warehouse stock check
     */
    IW.checkStock = function(productId, warehouseId, quantity, callback) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'iw_check_stock',
                product_id: productId,
                warehouse_id: warehouseId,
                quantity: quantity,
                nonce: iw_ajax.nonce
            },
            success: function(response) {
                if (typeof callback === 'function') {
                    callback(response);
                }
            }
        });
    };

    /**
     * Setup Wizard
     */
    IW.setupWizard = {
        currentStep: 1,
        totalSteps: 3,

        init: function() {
            this.updateStepIndicators();
        },

        nextStep: function() {
            if (this.currentStep < this.totalSteps) {
                if (this.validateStep(this.currentStep)) {
                    this.currentStep++;
                    this.showStep(this.currentStep);
                }
            }
        },

        prevStep: function() {
            if (this.currentStep > 1) {
                this.currentStep--;
                this.showStep(this.currentStep);
            }
        },

        showStep: function(step) {
            $('.iw-setup-step-content').removeClass('active');
            $('#step-' + step).addClass('active');
            this.updateStepIndicators();
            this.updateButtons();
        },

        validateStep: function(step) {
            var $stepContent = $('#step-' + step);
            return IW.validateForm($stepContent);
        },

        updateStepIndicators: function() {
            var self = this;
            $('.iw-setup-step').each(function(index) {
                var stepNum = index + 1;
                $(this).removeClass('active completed');

                if (stepNum === self.currentStep) {
                    $(this).addClass('active');
                } else if (stepNum < self.currentStep) {
                    $(this).addClass('completed');
                }
            });
        },

        updateButtons: function() {
            var $prev = $('.setup-prev');
            var $next = $('.setup-next');
            var $submit = $('.setup-submit');

            if (this.currentStep === 1) {
                $prev.hide();
            } else {
                $prev.show();
            }

            if (this.currentStep === this.totalSteps) {
                $next.hide();
                $submit.show();
            } else {
                $next.show();
                $submit.hide();
            }
        }
    };

    /**
     * Excel Import Handler
     */
    IW.excelImport = {
        file: null,
        data: null,

        init: function() {
            var self = this;

            $('#import-file').on('change', function(e) {
                self.file = e.target.files[0];
                if (self.file) {
                    self.readFile();
                }
            });
        },

        readFile: function() {
            var self = this;
            var reader = new FileReader();

            reader.onload = function(e) {
                var data = new Uint8Array(e.target.result);
                var workbook = XLSX.read(data, {type: 'array'});
                var firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                self.data = XLSX.utils.sheet_to_json(firstSheet);
                self.showPreview();
            };

            reader.readAsArrayBuffer(this.file);
        },

        showPreview: function() {
            if (!this.data || !this.data.length) {
                IW.showError(iw_strings.empty_file);
                return;
            }

            var headers = Object.keys(this.data[0]);
            var html = '<table class="iw-table"><thead><tr>';

            headers.forEach(function(header) {
                html += '<th>' + header + '</th>';
            });
            html += '</tr></thead><tbody>';

            // Show first 5 rows as preview
            var previewRows = this.data.slice(0, 5);
            previewRows.forEach(function(row) {
                html += '<tr>';
                headers.forEach(function(header) {
                    html += '<td>' + (row[header] || '') + '</td>';
                });
                html += '</tr>';
            });

            html += '</tbody></table>';

            if (this.data.length > 5) {
                html += '<p class="description">عرض ' + Math.min(5, this.data.length) + ' من ' + this.data.length + ' صف</p>';
            }

            $('#import-preview').html(html).show();
            $('#import-submit').show();
        },

        submit: function(importType) {
            var self = this;

            IW.ajax('iw_import_' + importType, {
                data: JSON.stringify(this.data)
            }, function(response) {
                IW.showSuccess(response.message);
                $('#import-preview').hide();
                $('#import-submit').hide();
                $('#import-file').val('');
                self.file = null;
                self.data = null;
            });
        }
    };

    /**
     * Reports functionality
     */
    IW.reports = {
        filters: {},

        applyFilters: function() {
            this.filters = {
                date_from: $('#filter-date-from').val(),
                date_to: $('#filter-date-to').val(),
                warehouse_id: $('#filter-warehouse').val(),
                category_id: $('#filter-category').val(),
                department_id: $('#filter-department').val()
            };

            this.loadReport();
        },

        loadReport: function() {
            var reportType = $('#report-type').val();

            IW.ajax('iw_get_report', {
                report_type: reportType,
                filters: this.filters
            }, function(data) {
                $('#report-content').html(data.html);
            });
        },

        exportPDF: function() {
            // This would typically use a PDF library
            window.print();
        },

        exportExcel: function() {
            IW.exportToExcel('report-table', 'تقرير_المستودع');
        }
    };

})(jQuery);

// Localization strings (will be overridden by wp_localize_script)
var iw_strings = iw_strings || {
    confirm_delete: 'هل أنت متأكد من الحذف؟',
    ajax_error: 'حدث خطأ في الاتصال',
    min_one_item: 'يجب أن يحتوي الإذن على صنف واحد على الأقل',
    empty_file: 'الملف فارغ أو غير صالح'
};

var iw_ajax = iw_ajax || {
    nonce: ''
};
