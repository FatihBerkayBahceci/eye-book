/**
 * Eye-Book Admin JavaScript
 *
 * @package EyeBook
 * @subpackage Admin/Assets/JS
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Global Eye-Book Admin object
    window.EyeBookAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initComponents();
            this.loadDashboardData();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Form validation
            $(document).on('submit', '.eye-book-form', this.validateForm);
            
            // AJAX form submissions
            $(document).on('submit', '.eye-book-ajax-form', this.submitAjaxForm);
            
            // Delete confirmations
            $(document).on('click', '.eye-book-delete', this.confirmDelete);
            
            // Tab navigation
            $(document).on('click', '.nav-tab', this.switchTab);
            
            // Search functionality
            $(document).on('input', '.eye-book-search', this.debounce(this.performSearch, 300));
            
            // Date picker initialization
            if ($.fn.datepicker) {
                $('.eye-book-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });
            }
            
            // Time picker initialization
            this.initTimePickers();
            
            // Auto-save functionality
            $(document).on('change', '.eye-book-auto-save', this.debounce(this.autoSave, 500));
        },

        /**
         * Initialize components
         */
        initComponents: function() {
            // Initialize tooltips
            if ($.fn.tooltip) {
                $('[data-tooltip]').tooltip();
            }
            
            // Initialize modals
            this.initModals();
            
            // Initialize data tables
            this.initDataTables();
            
            // Initialize calendar if present
            this.initCalendar();
            
            // Initialize charts if present
            this.initCharts();
        },

        /**
         * Load dashboard data
         */
        loadDashboardData: function() {
            if (!$('.eye-book-dashboard').length) {
                return;
            }

            // Refresh dashboard stats
            this.refreshDashboardStats();
            
            // Set up auto-refresh
            setInterval(this.refreshDashboardStats.bind(this), 300000); // 5 minutes
        },

        /**
         * Refresh dashboard statistics
         */
        refreshDashboardStats: function() {
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eye_book_dashboard_stats',
                    nonce: eyeBookAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        EyeBookAdmin.updateDashboardStats(response.data);
                    }
                },
                error: function() {
                    console.log('Failed to refresh dashboard stats');
                }
            });
        },

        /**
         * Update dashboard statistics display
         */
        updateDashboardStats: function(stats) {
            var statKeys = ['today_appointments', 'week_appointments', 'total_patients', 'new_patients'];
            
            $('.eye-book-stats-grid .stat-content h3').each(function(index) {
                if (statKeys[index] && stats[statKeys[index]] !== undefined) {
                    $(this).text(stats[statKeys[index]].toLocaleString());
                }
            });
        },

        /**
         * Validate forms before submission
         */
        validateForm: function(e) {
            var $form = $(this);
            var isValid = true;
            var errors = [];

            // Clear previous errors
            $form.find('.error').removeClass('error');
            $form.find('.error-message').remove();

            // Required field validation
            $form.find('[required]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (!value) {
                    $field.addClass('error');
                    $field.after('<span class="error-message">' + eyeBookAdmin.strings.error + '</span>');
                    isValid = false;
                    errors.push($field.attr('name') + ' is required');
                }
            });

            // Email validation
            $form.find('input[type="email"]').each(function() {
                var $field = $(this);
                var email = $field.val().trim();
                
                if (email && !EyeBookAdmin.isValidEmail(email)) {
                    $field.addClass('error');
                    $field.after('<span class="error-message">Invalid email format</span>');
                    isValid = false;
                    errors.push('Invalid email format');
                }
            });

            // Phone validation
            $form.find('input[type="tel"], .phone-field').each(function() {
                var $field = $(this);
                var phone = $field.val().trim();
                
                if (phone && !EyeBookAdmin.isValidPhone(phone)) {
                    $field.addClass('error');
                    $field.after('<span class="error-message">Invalid phone format</span>');
                    isValid = false;
                    errors.push('Invalid phone format');
                }
            });

            if (!isValid) {
                e.preventDefault();
                this.showErrors(errors);
            }

            return isValid;
        },

        /**
         * Handle AJAX form submissions
         */
        submitAjaxForm: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('[type="submit"]');
            var originalText = $submitButton.text();
            
            // Disable submit button
            $submitButton.prop('disabled', true).text(eyeBookAdmin.strings.loading);
            
            $.ajax({
                url: $form.attr('action') || eyeBookAdmin.ajax_url,
                type: $form.attr('method') || 'POST',
                data: $form.serialize(),
                success: function(response) {
                    if (response.success) {
                        EyeBookAdmin.showNotice(eyeBookAdmin.strings.save_success, 'success');
                        
                        // Redirect if specified
                        if (response.data && response.data.redirect) {
                            window.location.href = response.data.redirect;
                        }
                    } else {
                        var message = response.data && response.data.message ? response.data.message : eyeBookAdmin.strings.error;
                        EyeBookAdmin.showNotice(message, 'error');
                    }
                },
                error: function() {
                    EyeBookAdmin.showNotice(eyeBookAdmin.strings.error, 'error');
                },
                complete: function() {
                    // Re-enable submit button
                    $submitButton.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Confirm delete actions
         */
        confirmDelete: function(e) {
            if (!confirm(eyeBookAdmin.strings.confirm_delete)) {
                e.preventDefault();
                return false;
            }
        },

        /**
         * Switch between tabs
         */
        switchTab: function(e) {
            e.preventDefault();
            
            var $tab = $(this);
            var targetTab = $tab.attr('href');
            
            // Update active tab
            $tab.closest('.nav-tab-wrapper').find('.nav-tab').removeClass('nav-tab-active');
            $tab.addClass('nav-tab-active');
            
            // Show target content
            $('.tab-content').hide();
            $(targetTab).show();
            
            // Update URL hash
            if (history.pushState) {
                history.pushState(null, null, targetTab);
            }
        },

        /**
         * Perform search
         */
        performSearch: function() {
            var $searchInput = $(this);
            var searchTerm = $searchInput.val().trim();
            var $table = $searchInput.closest('.eye-book-table-container').find('table');
            
            if (searchTerm.length < 2) {
                $table.find('tr').show();
                return;
            }
            
            $table.find('tbody tr').each(function() {
                var $row = $(this);
                var rowText = $row.text().toLowerCase();
                
                if (rowText.indexOf(searchTerm.toLowerCase()) !== -1) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        },

        /**
         * Auto-save functionality
         */
        autoSave: function() {
            var $field = $(this);
            var $form = $field.closest('form');
            
            // Skip if form has errors
            if ($form.find('.error').length > 0) {
                return;
            }
            
            var data = $form.serialize();
            data += '&action=eye_book_auto_save&nonce=' + eyeBookAdmin.nonce;
            
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        $field.after('<span class="auto-save-indicator">✓ Saved</span>');
                        setTimeout(function() {
                            $('.auto-save-indicator').fadeOut();
                        }, 2000);
                    }
                }
            });
        },

        /**
         * Initialize time pickers
         */
        initTimePickers: function() {
            $('.eye-book-timepicker').each(function() {
                var $input = $(this);
                var $wrapper = $('<div class="time-picker-wrapper"></div>');
                $input.wrap($wrapper);
                
                var hours = [];
                var minutes = ['00', '15', '30', '45'];
                
                for (var i = 8; i <= 18; i++) {
                    hours.push(i.toString().padStart(2, '0'));
                }
                
                var $timeSelect = $('<select class="time-select"></select>');
                
                hours.forEach(function(hour) {
                    minutes.forEach(function(minute) {
                        var time = hour + ':' + minute;
                        var display = EyeBookAdmin.formatTime(time);
                        $timeSelect.append('<option value="' + time + '">' + display + '</option>');
                    });
                });
                
                $input.after($timeSelect);
                $input.hide();
                
                $timeSelect.on('change', function() {
                    $input.val($(this).val());
                });
            });
        },

        /**
         * Initialize modals
         */
        initModals: function() {
            // Simple modal implementation
            $(document).on('click', '[data-modal]', function(e) {
                e.preventDefault();
                var modalId = $(this).data('modal');
                $('#' + modalId).fadeIn();
            });
            
            $(document).on('click', '.modal-close, .modal-overlay', function() {
                $('.modal').fadeOut();
            });
            
            $(document).on('keyup', function(e) {
                if (e.keyCode === 27) { // ESC key
                    $('.modal').fadeOut();
                }
            });
        },

        /**
         * Initialize data tables
         */
        initDataTables: function() {
            if ($.fn.DataTable) {
                $('.eye-book-data-table').DataTable({
                    pageLength: 25,
                    responsive: true,
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries per page",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    }
                });
            }
        },

        /**
         * Initialize calendar
         */
        initCalendar: function() {
            var $calendar = $('.eye-book-calendar');
            if (!$calendar.length) {
                return;
            }
            
            // Basic calendar implementation
            this.renderCalendar($calendar);
            
            // Navigation
            $calendar.on('click', '.calendar-nav .prev', function() {
                EyeBookAdmin.navigateCalendar($calendar, -1);
            });
            
            $calendar.on('click', '.calendar-nav .next', function() {
                EyeBookAdmin.navigateCalendar($calendar, 1);
            });
        },

        /**
         * Render calendar
         */
        renderCalendar: function($calendar, date) {
            // Calendar rendering logic would go here
            // This is a simplified implementation
        },

        /**
         * Navigate calendar
         */
        navigateCalendar: function($calendar, direction) {
            // Calendar navigation logic would go here
        },

        /**
         * Initialize charts
         */
        initCharts: function() {
            if (typeof Chart === 'undefined') {
                return;
            }
            
            // Initialize any charts on the page
            $('.eye-book-chart').each(function() {
                var $canvas = $(this);
                var chartType = $canvas.data('chart-type') || 'line';
                var chartData = $canvas.data('chart-data');
                
                if (chartData) {
                    new Chart($canvas[0], {
                        type: chartType,
                        data: chartData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                }
            });
        },

        /**
         * Show notification
         */
        showNotice: function(message, type) {
            type = type || 'info';
            
            var $notice = $('<div class="eye-book-notice ' + type + '"><p>' + message + '</p></div>');
            
            $('.wrap').prepend($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Show errors
         */
        showErrors: function(errors) {
            var message = 'Please correct the following errors:<br>' + errors.join('<br>');
            this.showNotice(message, 'error');
        },

        /**
         * Validate email format
         */
        isValidEmail: function(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },

        /**
         * Validate phone format (US)
         */
        isValidPhone: function(phone) {
            var phoneRegex = /^[\+]?[1]?[\s]?[\(]?[0-9]{3}[\)]?[\s\-]?[0-9]{3}[\s\-]?[0-9]{4}$/;
            return phoneRegex.test(phone.replace(/\D/g, ''));
        },

        /**
         * Format time for display
         */
        formatTime: function(time) {
            var parts = time.split(':');
            var hour = parseInt(parts[0]);
            var minute = parts[1];
            var ampm = hour >= 12 ? 'PM' : 'AM';
            
            hour = hour % 12;
            hour = hour ? hour : 12; // 0 should be 12
            
            return hour + ':' + minute + ' ' + ampm;
        },

        /**
         * Debounce function
         */
        debounce: function(func, wait) {
            var timeout;
            return function executedFunction() {
                var context = this;
                var args = arguments;
                
                var later = function() {
                    timeout = null;
                    func.apply(context, args);
                };
                
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * Format currency
         */
        formatCurrency: function(amount) {
            return '$' + parseFloat(amount).toFixed(2);
        },

        /**
         * Format date
         */
        formatDate: function(date, format) {
            format = format || 'MM/DD/YYYY';
            
            var d = new Date(date);
            var month = (d.getMonth() + 1).toString().padStart(2, '0');
            var day = d.getDate().toString().padStart(2, '0');
            var year = d.getFullYear();
            
            return format
                .replace('MM', month)
                .replace('DD', day)
                .replace('YYYY', year);
        },

        /**
         * Get URL parameter
         */
        getUrlParameter: function(name) {
            name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
            var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
            var results = regex.exec(location.search);
            return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        EyeBookAdmin.init();
    });

})(jQuery);