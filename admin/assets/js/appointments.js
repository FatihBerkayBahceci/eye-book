/**
 * Eye-Book Appointments JavaScript
 * Modern appointments management functionality
 *
 * @package EyeBook
 * @subpackage Admin/Assets/JS
 * @since 2.0.0
 */

(function($) {
    'use strict';

    // Appointments specific functionality
    window.EyeBookAppointments = {
        
        currentView: 'list',
        selectedDate: null,
        selectedAppointments: [],

        /**
         * Initialize appointments page
         */
        init: function() {
            this.bindEvents();
            this.initializeDatePickers();
            this.initializeTimeSlots();
            this.setupFormValidation();
            this.loadProviderSchedules();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Form submission
            $(document).on('submit', '.eye-book-appointment-form', this.handleFormSubmission.bind(this));
            
            // Patient selection change
            $(document).on('change', '#patient_id', this.onPatientChange.bind(this));
            
            // Provider selection change  
            $(document).on('change', '#provider_id', this.onProviderChange.bind(this));
            
            // Appointment type change
            $(document).on('change', '#appointment_type_id', this.onAppointmentTypeChange.bind(this));
            
            // Date/time changes
            $(document).on('change', '#start_datetime', this.onStartDateTimeChange.bind(this));
            
            // Quick time slot selection
            $(document).on('click', '.eye-book-time-slot', this.selectTimeSlot.bind(this));
            
            // Bulk actions
            $(document).on('click', '.bulk-action-btn', this.handleBulkAction.bind(this));
            
            // Appointment status change
            $(document).on('change', '.appointment-status-select', this.changeAppointmentStatus.bind(this));
            
            // Search functionality
            $(document).on('input', '.appointment-search', this.debounce(this.searchAppointments.bind(this), 300));
            
            // Calendar view toggle
            $(document).on('click', '.view-toggle', this.toggleView.bind(this));
            
            // Export appointments
            $(document).on('click', '.export-appointments', this.exportAppointments.bind(this));
            
            // Print appointment
            $(document).on('click', '.print-appointment', this.printAppointment.bind(this));
        },

        /**
         * Initialize date pickers
         */
        initializeDatePickers: function() {
            if (typeof flatpickr !== 'undefined') {
                // Start date picker
                flatpickr('#start_datetime', {
                    enableTime: true,
                    dateFormat: 'Y-m-dTH:i',
                    minDate: 'today',
                    maxDate: new Date().fp_incr(90), // 90 days from now
                    minuteIncrement: 15,
                    time_24hr: false,
                    onChange: this.onStartDateTimeChange.bind(this)
                });

                // End date picker
                flatpickr('#end_datetime', {
                    enableTime: true,
                    dateFormat: 'Y-m-dTH:i',
                    minDate: 'today',
                    maxDate: new Date().fp_incr(90),
                    minuteIncrement: 15,
                    time_24hr: false
                });

                // Filter date pickers
                flatpickr('.date-filter', {
                    dateFormat: 'Y-m-d',
                    allowInput: true
                });
            }
        },

        /**
         * Initialize time slots
         */
        initializeTimeSlots: function() {
            this.generateTimeSlots();
        },

        /**
         * Generate available time slots
         */
        generateTimeSlots: function() {
            const providerId = $('#provider_id').val();
            const selectedDate = $('#start_datetime').val();
            
            if (!providerId || !selectedDate) {
                return;
            }
            
            const date = selectedDate.split('T')[0];
            
            // Fetch available slots from server
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eye_book_get_available_slots',
                    provider_id: providerId,
                    date: date,
                    nonce: eyeBookAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.displayTimeSlots(response.data);
                    }
                }.bind(this),
                error: function() {
                    console.error('Failed to load time slots');
                }
            });
        },

        /**
         * Display time slots
         */
        displayTimeSlots: function(slots) {
            const $container = $('.eye-book-time-slots');
            if (!$container.length) return;
            
            $container.empty();
            
            if (slots.length === 0) {
                $container.append('<p class="no-slots">No available time slots for this date</p>');
                return;
            }
            
            slots.forEach(slot => {
                const $button = $(`
                    <button type="button" class="eye-book-time-slot" 
                            data-time="${slot.datetime}" 
                            ${slot.available ? '' : 'disabled'}>
                        ${slot.formatted_time}
                    </button>
                `);
                
                if (!slot.available) {
                    $button.addClass('unavailable').attr('title', 'Time slot unavailable');
                }
                
                $container.append($button);
            });
            
            $container.show();
        },

        /**
         * Select time slot
         */
        selectTimeSlot: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            if ($button.prop('disabled')) return;
            
            const selectedTime = $button.data('time');
            $('#start_datetime').val(selectedTime);
            
            // Update active state
            $('.eye-book-time-slot').removeClass('active');
            $button.addClass('active');
            
            // Calculate end time
            this.calculateEndTime();
        },

        /**
         * Handle form submission
         */
        handleFormSubmission: function(e) {
            e.preventDefault();
            
            const $form = $(e.currentTarget);
            const $submitButton = $form.find('[type="submit"]');
            
            if (!this.validateForm($form)) {
                return;
            }
            
            // Show loading state
            this.setSubmitButtonLoading($submitButton, true);
            
            // Prepare form data
            const formData = new FormData($form[0]);
            formData.append('action', 'eye_book_save_appointment');
            formData.append('nonce', eyeBookAdmin.nonce);
            
            // Submit via AJAX
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        this.showSuccess('Appointment saved successfully');
                        
                        // Redirect to appointments list
                        setTimeout(() => {
                            window.location.href = eyeBookAdmin.pages?.appointments || 'admin.php?page=eye-book-appointments';
                        }, 1500);
                    } else {
                        this.showError(response.data?.message || 'Failed to save appointment');
                    }
                }.bind(this),
                error: function() {
                    this.showError('Network error occurred');
                }.bind(this),
                complete: function() {
                    this.setSubmitButtonLoading($submitButton, false);
                }.bind(this)
            });
        },

        /**
         * Validate appointment form
         */
        validateForm: function($form) {
            let isValid = true;
            const errors = [];
            
            // Clear previous errors
            $form.find('.eye-book-form-error').remove();
            $form.find('.error').removeClass('error');
            
            // Required fields
            const requiredFields = {
                'patient_id': 'Patient',
                'provider_id': 'Provider', 
                'start_datetime': 'Start Date & Time',
                'end_datetime': 'End Date & Time'
            };
            
            Object.keys(requiredFields).forEach(fieldName => {
                const $field = $form.find(`[name="${fieldName}"]`);
                const value = $field.val()?.trim();
                
                if (!value) {
                    this.addFieldError($field, `${requiredFields[fieldName]} is required`);
                    errors.push(`${requiredFields[fieldName]} is required`);
                    isValid = false;
                }
            });
            
            // Date/time validation
            const startDateTime = $form.find('[name="start_datetime"]').val();
            const endDateTime = $form.find('[name="end_datetime"]').val();
            
            if (startDateTime && endDateTime) {
                const start = new Date(startDateTime);
                const end = new Date(endDateTime);
                const now = new Date();
                
                // Start time must be in the future (for new appointments)
                if (!$form.find('[name="appointment_id"]').val() && start <= now) {
                    this.addFieldError($form.find('[name="start_datetime"]'), 'Start time must be in the future');
                    errors.push('Start time must be in the future');
                    isValid = false;
                }
                
                // End time must be after start time
                if (end <= start) {
                    this.addFieldError($form.find('[name="end_datetime"]'), 'End time must be after start time');
                    errors.push('End time must be after start time');
                    isValid = false;
                }
                
                // Check for reasonable duration (5 minutes to 8 hours)
                const durationMinutes = (end - start) / (1000 * 60);
                if (durationMinutes < 5 || durationMinutes > 480) {
                    this.addFieldError($form.find('[name="end_datetime"]'), 'Appointment duration should be between 5 minutes and 8 hours');
                    errors.push('Invalid appointment duration');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                this.showFormErrors(errors);
            }
            
            return isValid;
        },

        /**
         * Add field error
         */
        addFieldError: function($field, message) {
            $field.addClass('error');
            $field.after(`<div class="eye-book-form-error">${message}</div>`);
        },

        /**
         * Show form errors
         */
        showFormErrors: function(errors) {
            const message = `Please correct the following errors:<br>• ${errors.join('<br>• ')}`;
            this.showError(message);
        },

        /**
         * Patient selection change handler
         */
        onPatientChange: function(e) {
            const $select = $(e.currentTarget);
            const $option = $select.find('option:selected');
            
            if ($option.val()) {
                const email = $option.data('email');
                const phone = $option.data('phone');
                
                // Update patient info display
                const info = [email, phone].filter(Boolean).join(' • ');
                $('.selected-patient-info').text(info).show();
            } else {
                $('.selected-patient-info').hide();
            }
        },

        /**
         * Provider selection change handler
         */
        onProviderChange: function(e) {
            const providerId = $(e.currentTarget).val();
            
            if (providerId) {
                this.loadProviderSchedule(providerId);
                this.generateTimeSlots();
            }
        },

        /**
         * Load provider schedule
         */
        loadProviderSchedule: function(providerId) {
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eye_book_get_provider_schedule',
                    provider_id: providerId,
                    nonce: eyeBookAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.updateProviderScheduleInfo(response.data);
                    }
                }.bind(this)
            });
        },

        /**
         * Update provider schedule info display
         */
        updateProviderScheduleInfo: function(schedule) {
            // Update UI with provider schedule information
            const $info = $('.provider-schedule-info');
            if ($info.length) {
                let html = '<h4>Provider Schedule</h4><ul>';
                Object.keys(schedule).forEach(day => {
                    const daySchedule = schedule[day];
                    if (daySchedule.available) {
                        html += `<li><strong>${day}:</strong> ${daySchedule.start} - ${daySchedule.end}</li>`;
                    } else {
                        html += `<li><strong>${day}:</strong> Not available</li>`;
                    }
                });
                html += '</ul>';
                $info.html(html).show();
            }
        },

        /**
         * Load provider schedules
         */
        loadProviderSchedules: function() {
            // This would typically load all provider schedules for calendar view
        },

        /**
         * Appointment type change handler
         */
        onAppointmentTypeChange: function(e) {
            const $select = $(e.currentTarget);
            const $option = $select.find('option:selected');
            const duration = parseInt($option.data('duration')) || 30;
            
            // Update duration display
            $('.appointment-duration').text(`${duration} minutes`);
            
            // Recalculate end time
            this.calculateEndTime();
        },

        /**
         * Start date/time change handler
         */
        onStartDateTimeChange: function(e) {
            this.calculateEndTime();
            this.generateTimeSlots();
        },

        /**
         * Calculate end time based on start time and duration
         */
        calculateEndTime: function() {
            const startDateTime = $('#start_datetime').val();
            const $appointmentType = $('#appointment_type_id option:selected');
            const duration = parseInt($appointmentType.data('duration')) || 30;
            
            if (startDateTime) {
                const start = new Date(startDateTime);
                const end = new Date(start.getTime() + (duration * 60 * 1000));
                
                // Format for datetime-local input
                const endFormatted = end.toISOString().slice(0, 16);
                $('#end_datetime').val(endFormatted);
                
                // Update duration display
                $('.duration-display').text(`Duration: ${duration} minutes`);
            }
        },

        /**
         * Setup form validation
         */
        setupFormValidation: function() {
            // Real-time validation
            $(document).on('blur', '.eye-book-form-input, .eye-book-form-select', function() {
                const $field = $(this);
                $field.removeClass('error');
                $field.siblings('.eye-book-form-error').remove();
                
                if ($field.prop('required') && !$field.val().trim()) {
                    $field.addClass('error');
                    $field.after('<div class="eye-book-form-error">This field is required</div>');
                }
            });
        },

        /**
         * Change appointment status
         */
        changeAppointmentStatus: function(e) {
            const $select = $(e.currentTarget);
            const appointmentId = $select.data('appointment-id');
            const newStatus = $select.val();
            const originalStatus = $select.data('original-status');
            
            if (newStatus === originalStatus) return;
            
            // Confirm status change
            if (!confirm(`Are you sure you want to change the status to "${newStatus}"?`)) {
                $select.val(originalStatus);
                return;
            }
            
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eye_book_update_appointment_status',
                    appointment_id: appointmentId,
                    status: newStatus,
                    nonce: eyeBookAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.showSuccess('Status updated successfully');
                        $select.data('original-status', newStatus);
                        
                        // Update row styling based on status
                        this.updateRowStatus($select.closest('tr'), newStatus);
                    } else {
                        this.showError(response.data?.message || 'Failed to update status');
                        $select.val(originalStatus);
                    }
                }.bind(this),
                error: function() {
                    this.showError('Network error occurred');
                    $select.val(originalStatus);
                }.bind(this)
            });
        },

        /**
         * Update row status styling
         */
        updateRowStatus: function($row, status) {
            $row.removeClass('status-scheduled status-confirmed status-completed status-cancelled status-no-show');
            $row.addClass(`status-${status}`);
        },

        /**
         * Search appointments
         */
        searchAppointments: function(e) {
            const query = $(e.currentTarget).val().trim();
            const $table = $('.appointments-table tbody');
            
            if (query.length < 2) {
                $table.find('tr').show();
                return;
            }
            
            $table.find('tr').each(function() {
                const $row = $(this);
                const text = $row.text().toLowerCase();
                
                if (text.indexOf(query.toLowerCase()) !== -1) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        },

        /**
         * Toggle between list and calendar view
         */
        toggleView: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const view = $button.data('view');
            
            $('.view-toggle').removeClass('active');
            $button.addClass('active');
            
            $('.view-container').hide();
            $(`.${view}-view`).show();
            
            this.currentView = view;
            
            if (view === 'calendar') {
                this.initializeCalendar();
            }
        },

        /**
         * Initialize calendar view
         */
        initializeCalendar: function() {
            // Calendar initialization would go here
            // This would integrate with a calendar library like FullCalendar
        },

        /**
         * Export appointments
         */
        exportAppointments: function(e) {
            e.preventDefault();
            
            const format = $(e.currentTarget).data('format') || 'csv';
            const filters = this.getActiveFilters();
            
            // Create download URL
            const params = new URLSearchParams({
                action: 'eye_book_export_appointments',
                format: format,
                nonce: eyeBookAdmin.nonce,
                ...filters
            });
            
            window.open(`${eyeBookAdmin.ajax_url}?${params.toString()}`);
        },

        /**
         * Get active filters
         */
        getActiveFilters: function() {
            const filters = {};
            
            $('.filter-input').each(function() {
                const $input = $(this);
                const value = $input.val();
                if (value) {
                    filters[$input.attr('name')] = value;
                }
            });
            
            return filters;
        },

        /**
         * Print appointment
         */
        printAppointment: function(e) {
            e.preventDefault();
            
            const appointmentId = $(e.currentTarget).data('appointment-id');
            const printUrl = `${eyeBookAdmin.ajax_url}?action=eye_book_print_appointment&id=${appointmentId}&nonce=${eyeBookAdmin.nonce}`;
            
            const printWindow = window.open(printUrl, 'print', 'width=800,height=600');
            printWindow.onload = function() {
                printWindow.print();
            };
        },

        /**
         * Handle bulk actions
         */
        handleBulkAction: function(e) {
            e.preventDefault();
            
            const action = $('.bulk-actions select').val();
            const selectedIds = [];
            
            $('.appointment-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            if (selectedIds.length === 0) {
                this.showError('Please select at least one appointment');
                return;
            }
            
            if (!confirm(`Are you sure you want to ${action} ${selectedIds.length} appointment(s)?`)) {
                return;
            }
            
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eye_book_bulk_appointment_action',
                    bulk_action: action,
                    appointment_ids: selectedIds,
                    nonce: eyeBookAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.showSuccess(`${action} completed successfully`);
                        location.reload(); // Refresh the page
                    } else {
                        this.showError(response.data?.message || 'Bulk action failed');
                    }
                }.bind(this),
                error: function() {
                    this.showError('Network error occurred');
                }.bind(this)
            });
        },

        /**
         * Set submit button loading state
         */
        setSubmitButtonLoading: function($button, loading) {
            if (loading) {
                $button.prop('disabled', true);
                $button.find('.button-text').hide();
                $button.find('.loading-text').show();
            } else {
                $button.prop('disabled', false);
                $button.find('.button-text').show();
                $button.find('.loading-text').hide();
            }
        },

        /**
         * Show success message
         */
        showSuccess: function(message) {
            this.showNotification(message, 'success');
        },

        /**
         * Show error message
         */
        showError: function(message) {
            this.showNotification(message, 'error');
        },

        /**
         * Show notification
         */
        showNotification: function(message, type = 'info') {
            // Use the global notification system from dashboard.js
            if (window.EyeBookDashboard && window.EyeBookDashboard.showNotification) {
                window.EyeBookDashboard.showNotification(message, type);
            } else {
                // Fallback alert
                alert(message);
            }
        },

        /**
         * Debounce function
         */
        debounce: function(func, wait, immediate) {
            let timeout;
            return function executedFunction() {
                const context = this;
                const args = arguments;
                const later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.eye-book-appointment-form').length || $('.appointments-page').length) {
            EyeBookAppointments.init();
        }
    });

})(jQuery);