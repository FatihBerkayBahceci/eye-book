/**
 * Eye-Book Calendar Component
 *
 * @package EyeBook
 * @subpackage Admin/Assets/JS
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Eye-Book Calendar namespace
    window.EyeBook = window.EyeBook || {};
    window.EyeBook.Calendar = {
        
        // Configuration
        config: {
            currentDate: new Date(),
            currentView: 'month', // month, week, day
            selectedDate: null,
            events: [],
            providers: [],
            locations: [],
            appointmentTypes: []
        },

        // Initialize calendar
        init: function() {
            this.bindEvents();
            this.loadCalendarData();
            this.renderCalendar();
        },

        // Bind event handlers
        bindEvents: function() {
            var self = this;

            // Navigation buttons
            $(document).on('click', '#calendar-prev', function() {
                self.navigatePrevious();
            });

            $(document).on('click', '#calendar-next', function() {
                self.navigateNext();
            });

            // View switchers
            $(document).on('click', '.calendar-view-btn', function() {
                var view = $(this).data('view');
                self.switchView(view);
            });

            // Date cell clicks
            $(document).on('click', '.calendar-day', function() {
                var date = $(this).data('date');
                self.selectDate(date);
            });

            // Appointment clicks
            $(document).on('click', '.calendar-event', function(e) {
                e.stopPropagation();
                var appointmentId = $(this).data('appointment-id');
                self.showAppointmentDetails(appointmentId);
            });

            // Drag and drop for appointments
            this.initDragAndDrop();

            // Appointment creation
            $(document).on('dblclick', '.calendar-day, .time-slot', function() {
                var date = $(this).data('date');
                var time = $(this).data('time');
                self.createAppointment(date, time);
            });

            // Filter changes
            $(document).on('change', '#calendar-provider-filter, #calendar-location-filter', function() {
                self.applyFilters();
            });

            // Refresh calendar
            $(document).on('click', '#refresh-calendar', function() {
                self.refreshCalendar();
            });
        },

        // Load calendar data from server
        loadCalendarData: function() {
            var self = this;
            
            $.ajax({
                url: eyeBookAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eye_book_get_calendar_data',
                    nonce: eyeBookAdmin.nonce,
                    start_date: this.getStartDate(),
                    end_date: this.getEndDate(),
                    provider_id: $('#calendar-provider-filter').val(),
                    location_id: $('#calendar-location-filter').val()
                },
                success: function(response) {
                    if (response.success) {
                        self.config.events = response.data.appointments || [];
                        self.config.providers = response.data.providers || [];
                        self.config.locations = response.data.locations || [];
                        self.renderCalendar();
                    } else {
                        self.showError('Failed to load calendar data: ' + response.data);
                    }
                },
                error: function() {
                    self.showError('Failed to load calendar data');
                }
            });
        },

        // Render calendar based on current view
        renderCalendar: function() {
            switch (this.config.currentView) {
                case 'month':
                    this.renderMonthView();
                    break;
                case 'week':
                    this.renderWeekView();
                    break;
                case 'day':
                    this.renderDayView();
                    break;
            }
            
            this.updateTitle();
            this.updateViewButtons();
        },

        // Render month view
        renderMonthView: function() {
            var self = this;
            var startOfMonth = new Date(this.config.currentDate.getFullYear(), this.config.currentDate.getMonth(), 1);
            var endOfMonth = new Date(this.config.currentDate.getFullYear(), this.config.currentDate.getMonth() + 1, 0);
            var startOfWeek = new Date(startOfMonth);
            startOfWeek.setDate(startOfMonth.getDate() - startOfMonth.getDay());
            
            var html = '<div class="calendar-month">';
            
            // Week headers
            html += '<div class="calendar-header-row">';
            var dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            dayNames.forEach(function(day) {
                html += '<div class="calendar-header-cell">' + day + '</div>';
            });
            html += '</div>';
            
            // Calendar days
            var currentDate = new Date(startOfWeek);
            for (var week = 0; week < 6; week++) {
                html += '<div class="calendar-week">';
                
                for (var day = 0; day < 7; day++) {
                    var isCurrentMonth = currentDate.getMonth() === this.config.currentDate.getMonth();
                    var isToday = this.isToday(currentDate);
                    var isSelected = this.config.selectedDate && this.isSameDate(currentDate, this.config.selectedDate);
                    
                    var cssClass = 'calendar-day';
                    if (!isCurrentMonth) cssClass += ' other-month';
                    if (isToday) cssClass += ' today';
                    if (isSelected) cssClass += ' selected';
                    
                    var dateStr = this.formatDate(currentDate);
                    
                    html += '<div class="' + cssClass + '" data-date="' + dateStr + '">';
                    html += '<div class="day-number">' + currentDate.getDate() + '</div>';
                    
                    // Add appointments for this day
                    var dayEvents = this.getEventsForDate(currentDate);
                    if (dayEvents.length > 0) {
                        html += '<div class="day-events">';
                        dayEvents.slice(0, 3).forEach(function(event) {
                            html += self.renderEventSnippet(event);
                        });
                        if (dayEvents.length > 3) {
                            html += '<div class="more-events">+' + (dayEvents.length - 3) + ' more</div>';
                        }
                        html += '</div>';
                    }
                    
                    html += '</div>';
                    
                    currentDate.setDate(currentDate.getDate() + 1);
                }
                
                html += '</div>';
                
                // Break if we've passed the end of the month and completed a week
                if (currentDate.getMonth() !== this.config.currentDate.getMonth() && week >= 3) {
                    break;
                }
            }
            
            html += '</div>';
            
            $('#calendar-grid').html(html);
        },

        // Render week view
        renderWeekView: function() {
            var self = this;
            var startOfWeek = this.getStartOfWeek(this.config.currentDate);
            var html = '<div class="calendar-week-view">';
            
            // Time column header
            html += '<div class="time-column-header"></div>';
            
            // Day headers
            for (var i = 0; i < 7; i++) {
                var date = new Date(startOfWeek);
                date.setDate(date.getDate() + i);
                var isToday = this.isToday(date);
                
                html += '<div class="day-header' + (isToday ? ' today' : '') + '">';
                html += '<div class="day-name">' + this.getDayName(date) + '</div>';
                html += '<div class="day-date">' + date.getDate() + '</div>';
                html += '</div>';
            }
            
            // Time slots
            for (var hour = 8; hour < 18; hour++) {
                html += '<div class="time-row">';
                
                // Time label
                html += '<div class="time-label">' + this.formatTime(hour) + '</div>';
                
                // Day columns
                for (var day = 0; day < 7; day++) {
                    var date = new Date(startOfWeek);
                    date.setDate(date.getDate() + day);
                    date.setHours(hour, 0, 0, 0);
                    
                    var dateStr = this.formatDateTime(date);
                    html += '<div class="time-slot" data-date="' + this.formatDate(date) + '" data-time="' + hour + ':00">';
                    
                    // Add appointments for this time slot
                    var slotEvents = this.getEventsForDateTime(date);
                    slotEvents.forEach(function(event) {
                        html += self.renderEvent(event);
                    });
                    
                    html += '</div>';
                }
                
                html += '</div>';
            }
            
            html += '</div>';
            
            $('#calendar-grid').html(html);
        },

        // Render day view
        renderDayView: function() {
            var self = this;
            var currentDate = this.config.currentDate;
            var html = '<div class="calendar-day-view">';
            
            html += '<div class="day-header">';
            html += '<h3>' + this.formatFullDate(currentDate) + '</h3>';
            html += '</div>';
            
            // Time slots for the day
            for (var hour = 8; hour < 18; hour++) {
                for (var minutes = 0; minutes < 60; minutes += 30) {
                    var time = new Date(currentDate);
                    time.setHours(hour, minutes, 0, 0);
                    
                    html += '<div class="time-slot-detailed" data-date="' + this.formatDate(currentDate) + '" data-time="' + hour + ':' + (minutes < 10 ? '0' : '') + minutes + '">';
                    html += '<div class="time-label">' + this.formatTime(hour, minutes) + '</div>';
                    html += '<div class="slot-content">';
                    
                    // Add appointments for this time slot
                    var slotEvents = this.getEventsForDateTime(time);
                    slotEvents.forEach(function(event) {
                        html += self.renderDetailedEvent(event);
                    });
                    
                    html += '</div>';
                    html += '</div>';
                }
            }
            
            html += '</div>';
            
            $('#calendar-grid').html(html);
        },

        // Render event snippet for month view
        renderEventSnippet: function(event) {
            var statusClass = 'event-status-' + event.status;
            var html = '<div class="calendar-event event-snippet ' + statusClass + '" data-appointment-id="' + event.id + '">';
            html += '<span class="event-time">' + this.formatEventTime(event.start_datetime) + '</span>';
            html += '<span class="event-title">' + this.truncateText(event.patient_name, 15) + '</span>';
            html += '</div>';
            return html;
        },

        // Render full event for week view
        renderEvent: function(event) {
            var statusClass = 'event-status-' + event.status;
            var html = '<div class="calendar-event ' + statusClass + '" data-appointment-id="' + event.id + '">';
            html += '<div class="event-time">' + this.formatEventTime(event.start_datetime) + '</div>';
            html += '<div class="event-patient">' + event.patient_name + '</div>';
            html += '<div class="event-provider">' + event.provider_name + '</div>';
            html += '</div>';
            return html;
        },

        // Render detailed event for day view
        renderDetailedEvent: function(event) {
            var statusClass = 'event-status-' + event.status;
            var html = '<div class="calendar-event event-detailed ' + statusClass + '" data-appointment-id="' + event.id + '">';
            html += '<div class="event-header">';
            html += '<span class="event-time">' + this.formatEventTime(event.start_datetime) + ' - ' + this.formatEventTime(event.end_datetime) + '</span>';
            html += '<span class="event-status">' + event.status.replace('_', ' ').toUpperCase() + '</span>';
            html += '</div>';
            html += '<div class="event-patient"><strong>' + event.patient_name + '</strong></div>';
            html += '<div class="event-details">';
            html += '<div class="event-provider">' + event.provider_name + '</div>';
            html += '<div class="event-type">' + event.appointment_type + '</div>';
            if (event.chief_complaint) {
                html += '<div class="event-complaint">' + this.truncateText(event.chief_complaint, 50) + '</div>';
            }
            html += '</div>';
            html += '</div>';
            return html;
        },

        // Initialize drag and drop functionality
        initDragAndDrop: function() {
            var self = this;
            
            // Make appointments draggable
            $(document).on('mouseenter', '.calendar-event', function() {
                if (!$(this).hasClass('ui-draggable')) {
                    $(this).draggable({
                        containment: '#calendar-grid',
                        helper: 'clone',
                        opacity: 0.7,
                        revert: 'invalid',
                        start: function(event, ui) {
                            $(this).addClass('dragging');
                        },
                        stop: function(event, ui) {
                            $(this).removeClass('dragging');
                        }
                    });
                }
            });
            
            // Make time slots droppable
            $(document).on('mouseenter', '.time-slot, .calendar-day', function() {
                if (!$(this).hasClass('ui-droppable')) {
                    $(this).droppable({
                        accept: '.calendar-event',
                        hoverClass: 'drop-hover',
                        drop: function(event, ui) {
                            var appointmentId = ui.draggable.data('appointment-id');
                            var newDate = $(this).data('date');
                            var newTime = $(this).data('time') || '09:00';
                            
                            self.moveAppointment(appointmentId, newDate, newTime);
                        }
                    });
                }
            });
        },

        // Move appointment to new date/time
        moveAppointment: function(appointmentId, newDate, newTime) {
            var self = this;
            
            $.ajax({
                url: eyeBookAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eye_book_move_appointment',
                    nonce: eyeBookAdmin.nonce,
                    appointment_id: appointmentId,
                    new_date: newDate,
                    new_time: newTime
                },
                success: function(response) {
                    if (response.success) {
                        self.showSuccess('Appointment moved successfully');
                        self.refreshCalendar();
                    } else {
                        self.showError('Failed to move appointment: ' + response.data);
                    }
                },
                error: function() {
                    self.showError('Failed to move appointment');
                }
            });
        },

        // Navigate to previous period
        navigatePrevious: function() {
            switch (this.config.currentView) {
                case 'month':
                    this.config.currentDate.setMonth(this.config.currentDate.getMonth() - 1);
                    break;
                case 'week':
                    this.config.currentDate.setDate(this.config.currentDate.getDate() - 7);
                    break;
                case 'day':
                    this.config.currentDate.setDate(this.config.currentDate.getDate() - 1);
                    break;
            }
            this.loadCalendarData();
        },

        // Navigate to next period
        navigateNext: function() {
            switch (this.config.currentView) {
                case 'month':
                    this.config.currentDate.setMonth(this.config.currentDate.getMonth() + 1);
                    break;
                case 'week':
                    this.config.currentDate.setDate(this.config.currentDate.getDate() + 7);
                    break;
                case 'day':
                    this.config.currentDate.setDate(this.config.currentDate.getDate() + 1);
                    break;
            }
            this.loadCalendarData();
        },

        // Switch calendar view
        switchView: function(view) {
            this.config.currentView = view;
            this.renderCalendar();
        },

        // Select a date
        selectDate: function(dateStr) {
            this.config.selectedDate = new Date(dateStr);
            if (this.config.currentView === 'month') {
                this.config.currentDate = new Date(this.config.selectedDate);
                this.switchView('day');
            } else {
                this.renderCalendar();
            }
        },

        // Show appointment details
        showAppointmentDetails: function(appointmentId) {
            // This would open the appointment modal with details
            $('#appointment-modal').data('appointment-id', appointmentId);
            $('#appointment-modal').show();
            // Load and populate appointment data
            this.loadAppointmentDetails(appointmentId);
        },

        // Create new appointment
        createAppointment: function(date, time) {
            $('#appointment-modal').data('appointment-id', '');
            $('#appointment-date').val(date);
            $('#appointment-time').val(time || '09:00');
            $('#appointment-modal').show();
        },

        // Apply filters
        applyFilters: function() {
            this.loadCalendarData();
        },

        // Refresh calendar
        refreshCalendar: function() {
            this.loadCalendarData();
        },

        // Update calendar title
        updateTitle: function() {
            var title;
            switch (this.config.currentView) {
                case 'month':
                    title = this.config.currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                    break;
                case 'week':
                    var startOfWeek = this.getStartOfWeek(this.config.currentDate);
                    var endOfWeek = new Date(startOfWeek);
                    endOfWeek.setDate(endOfWeek.getDate() + 6);
                    title = this.formatDate(startOfWeek) + ' - ' + this.formatDate(endOfWeek);
                    break;
                case 'day':
                    title = this.formatFullDate(this.config.currentDate);
                    break;
            }
            $('#calendar-title').text(title);
        },

        // Update view buttons
        updateViewButtons: function() {
            $('.calendar-view-btn').removeClass('active');
            $('.calendar-view-btn[data-view="' + this.config.currentView + '"]').addClass('active');
        },

        // Helper functions
        getStartDate: function() {
            switch (this.config.currentView) {
                case 'month':
                    var start = new Date(this.config.currentDate.getFullYear(), this.config.currentDate.getMonth(), 1);
                    start.setDate(start.getDate() - start.getDay());
                    return this.formatDate(start);
                case 'week':
                    return this.formatDate(this.getStartOfWeek(this.config.currentDate));
                case 'day':
                    return this.formatDate(this.config.currentDate);
            }
        },

        getEndDate: function() {
            switch (this.config.currentView) {
                case 'month':
                    var end = new Date(this.config.currentDate.getFullYear(), this.config.currentDate.getMonth() + 1, 0);
                    end.setDate(end.getDate() + (6 - end.getDay()));
                    return this.formatDate(end);
                case 'week':
                    var end = this.getStartOfWeek(this.config.currentDate);
                    end.setDate(end.getDate() + 6);
                    return this.formatDate(end);
                case 'day':
                    return this.formatDate(this.config.currentDate);
            }
        },

        getStartOfWeek: function(date) {
            var start = new Date(date);
            start.setDate(date.getDate() - date.getDay());
            return start;
        },

        getEventsForDate: function(date) {
            var dateStr = this.formatDate(date);
            return this.config.events.filter(function(event) {
                return event.start_datetime.indexOf(dateStr) === 0;
            });
        },

        getEventsForDateTime: function(dateTime) {
            var self = this;
            return this.config.events.filter(function(event) {
                var eventStart = new Date(event.start_datetime);
                return self.isSameDateTime(eventStart, dateTime);
            });
        },

        isToday: function(date) {
            var today = new Date();
            return this.isSameDate(date, today);
        },

        isSameDate: function(date1, date2) {
            return date1.getFullYear() === date2.getFullYear() &&
                   date1.getMonth() === date2.getMonth() &&
                   date1.getDate() === date2.getDate();
        },

        isSameDateTime: function(date1, date2) {
            return this.isSameDate(date1, date2) &&
                   date1.getHours() === date2.getHours() &&
                   date1.getMinutes() === date2.getMinutes();
        },

        formatDate: function(date) {
            return date.getFullYear() + '-' + 
                   (date.getMonth() + 1).toString().padStart(2, '0') + '-' + 
                   date.getDate().toString().padStart(2, '0');
        },

        formatDateTime: function(date) {
            return this.formatDate(date) + ' ' + 
                   date.getHours().toString().padStart(2, '0') + ':' + 
                   date.getMinutes().toString().padStart(2, '0');
        },

        formatFullDate: function(date) {
            return date.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        },

        formatTime: function(hour, minutes) {
            minutes = minutes || 0;
            var ampm = hour >= 12 ? 'PM' : 'AM';
            hour = hour % 12;
            hour = hour ? hour : 12;
            return hour + ':' + (minutes < 10 ? '0' : '') + minutes + ' ' + ampm;
        },

        formatEventTime: function(datetime) {
            var date = new Date(datetime);
            return this.formatTime(date.getHours(), date.getMinutes());
        },

        getDayName: function(date) {
            return date.toLocaleDateString('en-US', { weekday: 'short' });
        },

        truncateText: function(text, maxLength) {
            if (text.length <= maxLength) return text;
            return text.substr(0, maxLength) + '...';
        },

        // Load appointment details for modal
        loadAppointmentDetails: function(appointmentId) {
            // This would load appointment details via AJAX
            // and populate the appointment modal form
        },

        // Show success message
        showSuccess: function(message) {
            // Implementation for success notifications
            console.log('Success:', message);
        },

        // Show error message
        showError: function(message) {
            // Implementation for error notifications
            console.log('Error:', message);
        }
    };

})(jQuery);