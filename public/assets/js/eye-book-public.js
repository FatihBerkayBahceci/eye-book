/**
 * Eye-Book Public JavaScript
 *
 * @package EyeBook
 * @subpackage Public/Assets/JS
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Global Eye-Book Public object
    window.EyeBookPublic = {
        
        /**
         * Initialize public functionality
         */
        init: function() {
            this.bindEvents();
            this.initComponents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Booking form events
            $(document).on('click', '.next-step', this.nextStep);
            $(document).on('click', '.prev-step', this.prevStep);
            $(document).on('submit', '#eye-book-booking-form', this.submitBookingForm);
            
            // Date and time selection
            $(document).on('change', '#appointment_date, #provider_id, #location_id, #appointment_type_id', this.loadAvailableSlots);
            
            // Phone number formatting
            $(document).on('input', 'input[type="tel"], .phone-field', this.formatPhoneNumber);
            
            // Form validation
            $(document).on('blur', '.eye-book-form input, .eye-book-form select', this.validateField);
            
            // Calendar events
            $(document).on('click', '.calendar-day:not(.other-month)', this.selectCalendarDate);
            $(document).on('click', '.calendar-nav .prev', this.prevMonth);
            $(document).on('click', '.calendar-nav .next', this.nextMonth);
            
            // Patient portal events
            $(document).on('click', '.portal-logout', this.logout);
            $(document).on('submit', '.patient-login-form', this.submitLoginForm);
        },

        /**
         * Initialize components
         */
        initComponents: function() {
            // Initialize date pickers
            this.initDatePickers();
            
            // Initialize calendar if present
            this.initCalendar();
            
            // Set up form progress tracking
            this.initFormProgress();
            
            // Initialize tooltips
            this.initTooltips();
        },

        /**
         * Initialize date pickers
         */
        initDatePickers: function() {
            // Set minimum and maximum dates for appointment booking
            var today = new Date();
            var minDate = new Date(today.getTime() + 24 * 60 * 60 * 1000); // Tomorrow
            var maxDate = new Date(today.getTime() + (eyeBookPublic.settings.booking_advance_days * 24 * 60 * 60 * 1000));
            
            $('#appointment_date').attr('min', this.formatDate(minDate));
            $('#appointment_date').attr('max', this.formatDate(maxDate));
            
            // Disable weekends if needed (could be a setting)
            $('#appointment_date').on('input', function() {
                var selectedDate = new Date($(this).val());
                var dayOfWeek = selectedDate.getDay();
                
                // 0 = Sunday, 6 = Saturday
                if (dayOfWeek === 0 || dayOfWeek === 6) {
                    $(this).val('');
                    EyeBookPublic.showNotice(eyeBookPublic.strings.weekend_not_allowed || 'Weekend appointments are not available', 'error');
                }
            });
        },

        /**
         * Initialize calendar component
         */
        initCalendar: function() {
            var $calendar = $('.eye-book-calendar');
            if (!$calendar.length) {
                return;
            }
            
            this.currentDate = new Date();
            this.renderCalendar();
        },

        /**
         * Render calendar
         */
        renderCalendar: function() {
            var $calendar = $('.eye-book-calendar');
            var year = this.currentDate.getFullYear();
            var month = this.currentDate.getMonth();
            
            // Update header
            var monthNames = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            
            $calendar.find('.calendar-header h3').text(monthNames[month] + ' ' + year);
            
            // Generate calendar grid
            this.generateCalendarGrid(year, month);
            
            // Load appointments for the month
            this.loadCalendarAppointments(year, month);
        },

        /**
         * Generate calendar grid
         */
        generateCalendarGrid: function(year, month) {
            var $calendar = $('.eye-book-calendar');
            var $grid = $calendar.find('.calendar-grid');
            
            // Clear existing content
            $grid.empty();
            
            // Add day headers
            var dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            dayHeaders.forEach(function(day) {
                $grid.append('<div class="calendar-day-header">' + day + '</div>');
            });
            
            // Get first day of month and number of days
            var firstDay = new Date(year, month, 1);
            var lastDay = new Date(year, month + 1, 0);
            var daysInMonth = lastDay.getDate();
            var startingDayOfWeek = firstDay.getDay();
            
            // Add empty cells for days before month starts
            for (var i = 0; i < startingDayOfWeek; i++) {
                var prevMonthDay = new Date(year, month, 0 - (startingDayOfWeek - 1 - i));
                $grid.append('<div class="calendar-day other-month" data-date="' + this.formatDate(prevMonthDay) + '">' +
                           '<div class="calendar-day-number">' + prevMonthDay.getDate() + '</div>' +
                           '</div>');
            }
            
            // Add days of current month
            var today = new Date();
            for (var day = 1; day <= daysInMonth; day++) {
                var currentDay = new Date(year, month, day);
                var isToday = currentDay.toDateString() === today.toDateString();
                var dayClass = 'calendar-day' + (isToday ? ' today' : '');
                
                $grid.append('<div class="' + dayClass + '" data-date="' + this.formatDate(currentDay) + '">' +
                           '<div class="calendar-day-number">' + day + '</div>' +
                           '<div class="calendar-appointments"></div>' +
                           '</div>');
            }
            
            // Add empty cells for days after month ends
            var totalCells = $grid.find('.calendar-day, .calendar-day-header').length - 7; // Subtract headers
            var remainingCells = 42 - totalCells; // 6 rows * 7 days
            
            for (var i = 1; i <= remainingCells; i++) {
                var nextMonthDay = new Date(year, month + 1, i);
                $grid.append('<div class="calendar-day other-month" data-date="' + this.formatDate(nextMonthDay) + '">' +
                           '<div class="calendar-day-number">' + i + '</div>' +
                           '</div>');
            }
        },

        /**
         * Load appointments for calendar
         */
        loadCalendarAppointments: function(year, month) {
            // This would load appointments via AJAX
            // For now, just a placeholder
        },

        /**
         * Initialize form progress tracking
         */
        initFormProgress: function() {
            if (!$('#eye-book-booking-form').length) {
                return;
            }
            
            this.currentStep = 1;
            this.totalSteps = $('.booking-step').length;
            
            // Add progress indicator
            this.addProgressIndicator();
        },

        /**
         * Add progress indicator to form
         */
        addProgressIndicator: function() {
            var progressHTML = '<div class="booking-progress">';
            
            for (var i = 1; i <= this.totalSteps; i++) {
                var stepClass = 'progress-step' + (i === 1 ? ' active' : '');
                var stepLabel = '';
                
                switch (i) {
                    case 1:
                        stepLabel = 'Appointment';
                        break;
                    case 2:
                        stepLabel = 'Information';
                        break;
                    case 3:
                        stepLabel = 'Confirm';
                        break;
                }
                
                progressHTML += '<div class="' + stepClass + '">' +
                              '<div class="step-number">' + i + '</div>' +
                              '<div class="step-label">' + stepLabel + '</div>' +
                              '</div>';
                
                if (i < this.totalSteps) {
                    progressHTML += '<div class="progress-connector"></div>';
                }
            }
            
            progressHTML += '</div>';
            
            $('.eye-book-booking-form').prepend(progressHTML);
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                var $element = $(this);
                var tooltipText = $element.data('tooltip');
                
                $element.on('mouseenter', function() {
                    EyeBookPublic.showTooltip($(this), tooltipText);
                });
                
                $element.on('mouseleave', function() {
                    EyeBookPublic.hideTooltip();
                });
            });
        },

        /**
         * Show tooltip
         */
        showTooltip: function($element, text) {
            var $tooltip = $('<div class="eye-book-tooltip">' + text + '</div>');
            $('body').append($tooltip);
            
            var elementOffset = $element.offset();
            var elementHeight = $element.outerHeight();
            
            $tooltip.css({
                top: elementOffset.top + elementHeight + 10,
                left: elementOffset.left,
                position: 'absolute',
                zIndex: 9999
            });
        },

        /**
         * Hide tooltip
         */
        hideTooltip: function() {
            $('.eye-book-tooltip').remove();
        },

        /**
         * Handle next step
         */
        nextStep: function(e) {
            e.preventDefault();
            
            if (EyeBookPublic.validateCurrentStep()) {
                if (EyeBookPublic.currentStep < EyeBookPublic.totalSteps) {
                    EyeBookPublic.showStep(EyeBookPublic.currentStep + 1);
                }
            }
        },

        /**
         * Handle previous step
         */
        prevStep: function(e) {
            e.preventDefault();
            
            if (EyeBookPublic.currentStep > 1) {
                EyeBookPublic.showStep(EyeBookPublic.currentStep - 1);
            }
        },

        /**
         * Show specific step
         */
        showStep: function(step) {
            $('.booking-step').hide();
            $('#step-' + step).show();
            
            // Update progress indicator
            $('.progress-step').removeClass('active completed');
            
            for (var i = 1; i <= this.totalSteps; i++) {
                if (i < step) {
                    $('.progress-step').eq(i - 1).addClass('completed');
                } else if (i === step) {
                    $('.progress-step').eq(i - 1).addClass('active');
                }
            }
            
            this.currentStep = step;
            
            if (step === 3) {
                this.updateSummary();
            }
            
            // Scroll to top of form
            $('html, body').animate({
                scrollTop: $('.eye-book-booking-form').offset().top - 20
            }, 300);
        },

        /**
         * Validate current step
         */
        validateCurrentStep: function() {
            var isValid = true;
            var currentStepElement = $('#step-' + this.currentStep);
            
            // Clear previous errors
            currentStepElement.find('.error').removeClass('error');
            currentStepElement.find('.error-message').remove();
            
            // Check required fields
            currentStepElement.find('[required]').each(function() {
                var $field = $(this);
                var value = $field.val();
                
                if (!value || (Array.isArray(value) && value.length === 0)) {
                    $field.addClass('error');
                    EyeBookPublic.showFieldError($field, 'This field is required');
                    isValid = false;
                }
            });
            
            // Email validation
            currentStepElement.find('input[type="email"]').each(function() {
                var $field = $(this);
                var email = $field.val();
                
                if (email && !EyeBookPublic.isValidEmail(email)) {
                    $field.addClass('error');
                    EyeBookPublic.showFieldError($field, 'Please enter a valid email address');
                    isValid = false;
                }
            });
            
            // Phone validation
            currentStepElement.find('input[type="tel"], .phone-field').each(function() {
                var $field = $(this);
                var phone = $field.val();
                
                if (phone && !EyeBookPublic.isValidPhone(phone)) {
                    $field.addClass('error');
                    EyeBookPublic.showFieldError($field, 'Please enter a valid phone number');
                    isValid = false;
                }
            });
            
            if (!isValid) {
                this.showNotice('Please correct the errors below', 'error');
            }
            
            return isValid;
        },

        /**
         * Show field error
         */
        showFieldError: function($field, message) {
            $field.after('<div class="error-message">' + message + '</div>');
        },

        /**
         * Validate individual field
         */
        validateField: function() {
            var $field = $(this);
            var value = $field.val();
            var isValid = true;
            
            // Remove previous error
            $field.removeClass('error');
            $field.next('.error-message').remove();
            
            // Required field check
            if ($field.attr('required') && !value) {
                isValid = false;
                EyeBookPublic.showFieldError($field, 'This field is required');
            }
            
            // Email validation
            if ($field.attr('type') === 'email' && value && !EyeBookPublic.isValidEmail(value)) {
                isValid = false;
                EyeBookPublic.showFieldError($field, 'Please enter a valid email address');
            }
            
            // Phone validation
            if (($field.attr('type') === 'tel' || $field.hasClass('phone-field')) && value && !EyeBookPublic.isValidPhone(value)) {
                isValid = false;
                EyeBookPublic.showFieldError($field, 'Please enter a valid phone number');
            }
            
            if (!isValid) {
                $field.addClass('error');
            }
        },

        /**
         * Update appointment summary
         */
        updateSummary: function() {
            // Update appointment details
            var date = $('#appointment_date').val();
            var time = $('#appointment_time option:selected').text();
            var provider = $('#provider_id option:selected').text();
            var location = $('#location_id option:selected').text();
            var type = $('#appointment_type_id option:selected').text();
            
            $('#summary-date').text(this.formatDisplayDate(date));
            $('#summary-time').text(time);
            $('#summary-provider').text(provider);
            $('#summary-location').text(location);
            $('#summary-type').text(type);
            
            // Update patient details
            var name = $('#first_name').val() + ' ' + $('#last_name').val();
            var email = $('#email').val();
            var phone = $('#phone').val();
            
            $('#summary-name').text(name);
            $('#summary-email').text(email);
            $('#summary-phone').text(phone);
        },

        /**
         * Load available time slots
         */
        loadAvailableSlots: function() {
            var providerID = $('#provider_id').val();
            var locationID = $('#location_id').val();
            var appointmentTypeID = $('#appointment_type_id').val();
            var date = $('#appointment_date').val();
            
            if (!providerID || !locationID || !appointmentTypeID || !date) {
                return;
            }
            
            $('.loading-slots').show();
            $('#appointment_time').prop('disabled', true).html('<option value="">' + eyeBookPublic.strings.loading + '</option>');
            
            $.post(eyeBookPublic.ajax_url, {
                action: 'eye_book_get_available_slots',
                nonce: eyeBookPublic.nonce,
                provider_id: providerID,
                location_id: locationID,
                appointment_type_id: appointmentTypeID,
                date: date
            }).done(function(response) {
                $('.loading-slots').hide();
                
                if (response.success) {
                    var options = '<option value="">' + eyeBookPublic.strings.select_time + '</option>';
                    
                    if (response.data.length > 0) {
                        $.each(response.data, function(index, slot) {
                            options += '<option value="' + slot.time + '">' + slot.display + '</option>';
                        });
                    } else {
                        options = '<option value="">No available times</option>';
                    }
                    
                    $('#appointment_time').html(options).prop('disabled', false);
                } else {
                    $('#appointment_time').html('<option value="">' + (response.data || eyeBookPublic.strings.error) + '</option>');
                }
            }).fail(function() {
                $('.loading-slots').hide();
                $('#appointment_time').html('<option value="">' + eyeBookPublic.strings.error + '</option>');
            });
        },

        /**
         * Submit booking form
         */
        submitBookingForm: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('[type="submit"]');
            var originalText = $submitButton.text();
            
            // Final validation
            if (!EyeBookPublic.validateCurrentStep()) {
                return false;
            }
            
            // Check terms agreement
            if (!$('#agree_terms').is(':checked')) {
                EyeBookPublic.showNotice('Please agree to the terms of service', 'error');
                return false;
            }
            
            // Disable submit button
            $submitButton.prop('disabled', true).text(eyeBookPublic.strings.loading);
            
            // Submit via AJAX
            $.post(eyeBookPublic.ajax_url, $form.serialize() + '&action=eye_book_book_appointment')
                .done(function(response) {
                    if (response.success) {
                        EyeBookPublic.showNotice(eyeBookPublic.strings.book_success, 'success');
                        
                        // Show success message with appointment details
                        var successMessage = '<div class="booking-success">' +
                                           '<h3>Appointment Booked Successfully!</h3>' +
                                           '<p>Your appointment ID is: <strong>' + response.data.appointment_id + '</strong></p>' +
                                           '<p>You will receive a confirmation email shortly.</p>' +
                                           '</div>';
                        
                        $form.replaceWith(successMessage);
                    } else {
                        EyeBookPublic.showNotice(response.data || eyeBookPublic.strings.book_error, 'error');
                        $submitButton.prop('disabled', false).text(originalText);
                    }
                })
                .fail(function() {
                    EyeBookPublic.showNotice(eyeBookPublic.strings.book_error, 'error');
                    $submitButton.prop('disabled', false).text(originalText);
                });
        },

        /**
         * Format phone number
         */
        formatPhoneNumber: function() {
            var $input = $(this);
            var value = $input.val().replace(/\D/g, '');
            var formattedValue = '';
            
            if (value.length >= 6) {
                formattedValue = '(' + value.substring(0, 3) + ') ' + value.substring(3, 6) + '-' + value.substring(6, 10);
            } else if (value.length >= 3) {
                formattedValue = '(' + value.substring(0, 3) + ') ' + value.substring(3);
            } else {
                formattedValue = value;
            }
            
            $input.val(formattedValue);
        },

        /**
         * Select calendar date
         */
        selectCalendarDate: function() {
            var $day = $(this);
            var date = $day.data('date');
            
            // Remove previous selection
            $('.calendar-day').removeClass('selected');
            
            // Add selection to clicked day
            $day.addClass('selected');
            
            // Update appointment date field if present
            $('#appointment_date').val(date).trigger('change');
        },

        /**
         * Navigate to previous month
         */
        prevMonth: function(e) {
            e.preventDefault();
            EyeBookPublic.currentDate.setMonth(EyeBookPublic.currentDate.getMonth() - 1);
            EyeBookPublic.renderCalendar();
        },

        /**
         * Navigate to next month
         */
        nextMonth: function(e) {
            e.preventDefault();
            EyeBookPublic.currentDate.setMonth(EyeBookPublic.currentDate.getMonth() + 1);
            EyeBookPublic.renderCalendar();
        },

        /**
         * Submit login form
         */
        submitLoginForm: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('[type="submit"]');
            var originalText = $submitButton.text();
            
            $submitButton.prop('disabled', true).text('Logging in...');
            
            // Submit form normally for now (could be AJAX)
            $form.off('submit').submit();
        },

        /**
         * Logout
         */
        logout: function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = $(this).attr('href');
            }
        },

        /**
         * Show notification
         */
        showNotice: function(message, type) {
            type = type || 'info';
            
            var $notice = $('<div class="eye-book-' + type + '">' + message + '</div>');
            
            $('.eye-book-booking-form, .eye-book-patient-portal').first().prepend($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Scroll to notice
            $('html, body').animate({
                scrollTop: $notice.offset().top - 20
            }, 300);
        },

        /**
         * Validate email format
         */
        isValidEmail: function(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },

        /**
         * Validate phone format
         */
        isValidPhone: function(phone) {
            var phoneRegex = /^\(?([0-9]{3})\)?[-. ]?([0-9]{3})[-. ]?([0-9]{4})$/;
            return phoneRegex.test(phone);
        },

        /**
         * Format date for input
         */
        formatDate: function(date) {
            var year = date.getFullYear();
            var month = (date.getMonth() + 1).toString().padStart(2, '0');
            var day = date.getDate().toString().padStart(2, '0');
            
            return year + '-' + month + '-' + day;
        },

        /**
         * Format date for display
         */
        formatDisplayDate: function(dateString) {
            if (!dateString) return '';
            
            var date = new Date(dateString);
            var options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            
            return date.toLocaleDateString('en-US', options);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        EyeBookPublic.init();
        
        // Enable terms checkbox functionality
        $('#agree_terms, #confirm_info').on('change', function() {
            var termsAgreed = $('#agree_terms').is(':checked');
            var infoConfirmed = $('#confirm_info').is(':checked');
            
            $('.submit-booking').prop('disabled', !(termsAgreed && infoConfirmed));
        });
    });

})(jQuery);