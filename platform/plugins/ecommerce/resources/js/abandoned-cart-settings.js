/**
 * Abandoned Cart Settings JavaScript
 * Handles test email functionality and form interactions
 */

class AbandonedCartSettings {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeTooltips();
        this.initializeCounters();
    }

    bindEvents() {
        // Test email functionality
        $(document).on('click', '.btn-send-test-email', this.handleTestEmail.bind(this));
        
        // Bulk send functionality
        $(document).on('click', '.btn-bulk-send', this.handleBulkSend.bind(this));
        
        // Preview functionality
        $(document).on('click', '.btn-preview-emails', this.handlePreview.bind(this));
        
        // Form validation
        $(document).on('change', 'input[name="abandoned_cart_delay_hours"]', this.validateDelayHours.bind(this));
        $(document).on('change', 'input[name="abandoned_cart_max_hours"]', this.validateMaxHours.bind(this));
        
        // Enable/disable dependent fields
        $(document).on('change', 'input[name="abandoned_cart_enabled"]', this.toggleDependentFields.bind(this));
        
        // Real-time counter updates
        $(document).on('input', 'input[data-counter]', this.updateCounter.bind(this));
    }

    handleTestEmail(e) {
        e.preventDefault();
        
        const $button = $(e.currentTarget);
        const email = $('input[name="abandoned_cart_test_email"]').val();
        const template = $('select[name="abandoned_cart_email_template"]').val() || 'abandoned_cart';
        
        if (!email) {
            this.showMessage(this.getLocalizedMessage('test_email_required'), 'error');
            return;
        }
        
        if (!this.isValidEmail(email)) {
            this.showMessage(this.getLocalizedMessage('test_email_invalid'), 'error');
            return;
        }
        
        this.setButtonLoading($button, true);
        
        $.ajax({
            url: '/admin/ecommerce/settings/abandoned-cart/send-test-email',
            method: 'POST',
            data: {
                email: email,
                template: template,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: (response) => {
                if (response.error) {
                    this.showMessage(response.message, 'error');
                } else {
                    this.showMessage(response.message, 'success');
                }
            },
            error: (xhr) => {
                const response = xhr.responseJSON;
                const message = response?.message || 'Failed to send test email';
                this.showMessage(message, 'error');
            },
            complete: () => {
                this.setButtonLoading($button, false);
            }
        });
    }

    handleBulkSend(e) {
        e.preventDefault();
        
        const $button = $(e.currentTarget);
        const delayHours = $('input[name="abandoned_cart_delay_hours"]').val() || 1;
        const maxHours = $('input[name="abandoned_cart_max_hours"]').val() || 168;
        const limit = $('input[name="abandoned_cart_email_limit"]').val() || 50;
        const template = $('select[name="abandoned_cart_email_template"]').val() || 'abandoned_cart';
        
        if (!confirm(this.getLocalizedMessage('bulk_send_confirm'))) {
            return;
        }
        
        this.setButtonLoading($button, true);
        
        $.ajax({
            url: '/admin/ecommerce/settings/abandoned-cart/bulk-send',
            method: 'POST',
            data: {
                hours: delayHours,
                max_hours: maxHours,
                limit: limit,
                template: template,
                dry_run: false,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: (response) => {
                if (response.error) {
                    this.showMessage(response.message, 'error');
                } else {
                    this.showMessage(response.message, 'success');
                }
            },
            error: (xhr) => {
                const response = xhr.responseJSON;
                const message = response?.message || 'Failed to send bulk emails';
                this.showMessage(message, 'error');
            },
            complete: () => {
                this.setButtonLoading($button, false);
            }
        });
    }

    handlePreview(e) {
        e.preventDefault();
        
        const $button = $(e.currentTarget);
        const delayHours = $('input[name="abandoned_cart_delay_hours"]').val() || 1;
        const maxHours = $('input[name="abandoned_cart_max_hours"]').val() || 168;
        const limit = $('input[name="abandoned_cart_email_limit"]').val() || 50;
        
        this.setButtonLoading($button, true);
        
        $.ajax({
            url: '/admin/ecommerce/settings/abandoned-cart/bulk-send',
            method: 'POST',
            data: {
                hours: delayHours,
                max_hours: maxHours,
                limit: limit,
                dry_run: true,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: (response) => {
                if (response.error) {
                    this.showMessage(response.message, 'error');
                } else {
                    this.showMessage(response.message, 'info');
                    if (response.orders && response.orders.length > 0) {
                        this.showPreviewModal(response.orders);
                    }
                }
            },
            error: (xhr) => {
                const response = xhr.responseJSON;
                const message = response?.message || 'Failed to preview emails';
                this.showMessage(message, 'error');
            },
            complete: () => {
                this.setButtonLoading($button, false);
            }
        });
    }

    validateDelayHours(e) {
        const value = parseInt($(e.target).val());
        const maxHours = parseInt($('input[name="abandoned_cart_max_hours"]').val());
        
        if (value >= maxHours) {
            this.showMessage(this.getLocalizedMessage('delay_hours_validation'), 'warning');
            $(e.target).focus();
        }
    }

    validateMaxHours(e) {
        const value = parseInt($(e.target).val());
        const delayHours = parseInt($('input[name="abandoned_cart_delay_hours"]').val());
        
        if (value <= delayHours) {
            this.showMessage(this.getLocalizedMessage('max_hours_validation'), 'warning');
            $(e.target).focus();
        }
    }

    toggleDependentFields(e) {
        const isEnabled = $(e.target).is(':checked');
        const $form = $('.abandoned-cart-form');
        
        $form.find('input, select, textarea').not('[name="abandoned_cart_enabled"]').prop('disabled', !isEnabled);
        $form.find('.btn').not('.btn-primary').prop('disabled', !isEnabled);
        
        if (isEnabled) {
            $form.removeClass('form-disabled');
        } else {
            $form.addClass('form-disabled');
        }
    }

    updateCounter(e) {
        const $input = $(e.target);
        const counter = $input.attr('data-counter');
        const value = parseInt($input.val()) || 0;
        
        let $counterDisplay = $input.next('.counter-display');
        if ($counterDisplay.length === 0) {
            $counterDisplay = $('<span class="counter-display text-muted small"></span>');
            $input.after($counterDisplay);
        }
        
        $counterDisplay.text(` / ${counter}`);
        
        // Color coding based on percentage
        const percentage = (value / parseInt(counter)) * 100;
        $counterDisplay.removeClass('text-success text-warning text-danger text-muted');
        
        if (percentage < 50) {
            $counterDisplay.addClass('text-success');
        } else if (percentage < 80) {
            $counterDisplay.addClass('text-warning');
        } else {
            $counterDisplay.addClass('text-danger');
        }
    }

    initializeTooltips() {
        $('[data-bs-toggle="tooltip"], [title]').tooltip();
    }

    initializeCounters() {
        $('input[data-counter]').each((index, element) => {
            this.updateCounter({ target: element });
        });
    }

    showMessage(message, type = 'info') {
        // Remove existing messages
        $('.form-message').remove();
        
        const alertClass = type === 'error' ? 'error' : (type === 'warning' ? 'warning' : type);
        const iconClass = type === 'error' ? 'fas fa-exclamation-circle' : 
                         type === 'warning' ? 'fas fa-exclamation-triangle' :
                         type === 'success' ? 'fas fa-check-circle' : 'fas fa-info-circle';
        
        const $message = $(`
            <div class="form-message ${alertClass}">
                <i class="${iconClass} me-2"></i>
                ${message}
            </div>
        `);
        
        $('.abandoned-cart-form').prepend($message);
        
        // Auto-hide after 5 seconds for success messages
        if (type === 'success') {
            setTimeout(() => {
                $message.fadeOut(300, () => $message.remove());
            }, 5000);
        }
        
        // Scroll to message
        $('html, body').animate({
            scrollTop: $message.offset().top - 100
        }, 300);
    }

    showPreviewModal(orders) {
        const modalHtml = `
            <div class="modal fade" id="previewModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-eye me-2"></i>
                                Email Preview - ${orders.length} cart(s) found
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Order Code</th>
                                            <th>Customer</th>
                                            <th>Email</th>
                                            <th>Items</th>
                                            <th>Amount</th>
                                            <th>Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${orders.map(order => `
                                            <tr>
                                                <td><code>${order.id}</code></td>
                                                <td>${order.customer}</td>
                                                <td>${order.email}</td>
                                                <td>${order.items}</td>
                                                <td>${order.amount}</td>
                                                <td>${order.created}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal
        $('#previewModal').remove();
        
        // Add and show new modal
        $('body').append(modalHtml);
        $('#previewModal').modal('show');
    }

    setButtonLoading($button, loading = true) {
        if (loading) {
            $button.prop('disabled', true);
            $button.find('.btn-text').hide();
            $button.append('<span class="loading-spinner me-2"></span>');
        } else {
            $button.prop('disabled', false);
            $button.find('.loading-spinner').remove();
            $button.find('.btn-text').show();
        }
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    getLocalizedMessage(key) {
        const messages = {
            'en': {
                'test_email_required': 'Please enter a test email address',
                'test_email_invalid': 'Please enter a valid email address',
                'bulk_send_confirm': 'Are you sure you want to send abandoned cart emails? This action cannot be undone.',
                'delay_hours_validation': 'Delay hours must be less than maximum cart age',
                'max_hours_validation': 'Maximum cart age must be greater than delay hours'
            },
            'es': {
                'test_email_required': 'Por favor ingresa una dirección de correo de prueba',
                'test_email_invalid': 'Por favor ingresa una dirección de correo válida',
                'bulk_send_confirm': '¿Estás seguro de que quieres enviar correos de carrito abandonado? Esta acción no se puede deshacer.',
                'delay_hours_validation': 'Las horas de retraso deben ser menores que la edad máxima del carrito',
                'max_hours_validation': 'La edad máxima del carrito debe ser mayor que las horas de retraso'
            }
        };

        const locale = document.documentElement.lang || 'en';
        const supportedLocale = messages[locale] ? locale : 'en';
        
        return messages[supportedLocale][key] || messages['en'][key] || key;
    }
}

// Initialize when document is ready
$(document).ready(function() {
    new AbandonedCartSettings();
    
    // Initialize enabled state on page load
    const isEnabled = $('input[name="abandoned_cart_enabled"]').is(':checked');
    if (!isEnabled) {
        $('.abandoned-cart-form').addClass('form-disabled');
        $('.abandoned-cart-form input, .abandoned-cart-form select, .abandoned-cart-form textarea')
            .not('[name="abandoned_cart_enabled"]')
            .prop('disabled', true);
    }
});