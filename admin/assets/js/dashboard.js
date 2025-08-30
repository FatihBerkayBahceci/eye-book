/**
 * Eye-Book Dashboard JavaScript
 * Modern dashboard functionality with Alpine.js
 *
 * @package EyeBook
 * @subpackage Admin/Assets/JS
 * @since 2.0.0
 */

(function($) {
    'use strict';

    // Dashboard specific functionality
    window.EyeBookDashboard = {
        
        charts: {},
        refreshInterval: null,

        /**
         * Initialize dashboard
         */
        init: function() {
            this.initializeCharts();
            this.bindEvents();
            this.startAutoRefresh();
            this.initializeSearchFunctionality();
            this.initializeNotifications();
        },

        /**
         * Initialize all charts
         */
        initializeCharts: function() {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not loaded');
                return;
            }

            // Main appointments chart
            this.initAppointmentsChart();
            
            // Patient demographics chart
            this.initPatientDemographicsChart();
            
            // Revenue chart
            this.initRevenueChart();
        },

        /**
         * Initialize appointments chart
         */
        initAppointmentsChart: function() {
            const ctx = document.getElementById('appointmentsChart');
            if (!ctx) return;

            // Destroy existing chart if it exists
            if (this.charts.appointments) {
                this.charts.appointments.destroy();
            }

            this.charts.appointments = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.getLast7Days(),
                    datasets: [{
                        label: eyeBookAdmin.strings.all_appointments || 'Appointments',
                        data: [12, 19, 15, 5, 8, 13, 7],
                        borderColor: 'rgb(14, 165, 233)',
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: 'rgb(14, 165, 233)',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 3,
                        pointRadius: 5,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: 'rgb(14, 165, 233)',
                            borderWidth: 1,
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                title: function(context) {
                                    return context[0].label;
                                },
                                label: function(context) {
                                    return `${context.parsed.y} appointments`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#6b7280',
                                font: {
                                    size: 12
                                },
                                callback: function(value) {
                                    return Math.floor(value);
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#6b7280',
                                font: {
                                    size: 12
                                }
                            }
                        }
                    },
                    elements: {
                        point: {
                            hoverBackgroundColor: 'rgb(14, 165, 233)',
                            hoverBorderColor: '#ffffff'
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutCubic'
                    }
                }
            });
        },

        /**
         * Initialize patient demographics chart
         */
        initPatientDemographicsChart: function() {
            const ctx = document.getElementById('demographicsChart');
            if (!ctx) return;

            if (this.charts.demographics) {
                this.charts.demographics.destroy();
            }

            this.charts.demographics = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['18-30', '31-45', '46-60', '60+'],
                    datasets: [{
                        data: [25, 35, 28, 12],
                        backgroundColor: [
                            'rgb(14, 165, 233)',
                            'rgb(34, 197, 94)',
                            'rgb(245, 158, 11)',
                            'rgb(239, 68, 68)'
                        ],
                        borderWidth: 3,
                        borderColor: '#ffffff',
                        hoverBorderWidth: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                color: '#374151'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${context.parsed}%`;
                                }
                            }
                        }
                    },
                    cutout: '60%',
                    animation: {
                        animateScale: true,
                        duration: 1000
                    }
                }
            });
        },

        /**
         * Initialize revenue chart
         */
        initRevenueChart: function() {
            const ctx = document.getElementById('revenueChart');
            if (!ctx) return;

            if (this.charts.revenue) {
                this.charts.revenue.destroy();
            }

            this.charts.revenue = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Revenue',
                        data: [12000, 15000, 13000, 17000, 16000, 19000],
                        backgroundColor: 'rgba(14, 165, 233, 0.8)',
                        borderColor: 'rgb(14, 165, 233)',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    return `$${context.parsed.y.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#6b7280',
                                callback: function(value) {
                                    return '$' + (value / 1000) + 'k';
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#6b7280'
                            }
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutBounce'
                    }
                }
            });
        },

        /**
         * Bind dashboard events
         */
        bindEvents: function() {
            // Chart period selector
            $(document).on('change', '.chart-period-selector', this.updateChartPeriod.bind(this));
            
            // Refresh button
            $(document).on('click', '.dashboard-refresh', this.refreshDashboard.bind(this));
            
            // Export buttons
            $(document).on('click', '.export-dashboard', this.exportDashboard.bind(this));
            
            // Quick action buttons
            $(document).on('click', '.quick-appointment', this.quickAppointment.bind(this));
            
            // Stat card clicks
            $(document).on('click', '.eye-book-stat-card', this.handleStatCardClick.bind(this));
            
            // Search functionality
            $(document).on('input', '.eye-book-search-input', this.debounce(this.performSearch.bind(this), 300));
            
            // User menu toggle
            $(document).on('click', '.eye-book-user-menu', this.toggleUserMenu.bind(this));
            
            // Notification toggle
            $(document).on('click', '.notification-toggle', this.toggleNotifications.bind(this));
        },

        /**
         * Update chart period
         */
        updateChartPeriod: function(e) {
            const period = $(e.target).val();
            const chartType = $(e.target).data('chart');
            
            // Show loading
            this.showChartLoading(chartType);
            
            // Fetch new data
            this.fetchChartData(chartType, period).then(data => {
                this.updateChart(chartType, data);
                this.hideChartLoading(chartType);
            }).catch(error => {
                console.error('Failed to update chart:', error);
                this.hideChartLoading(chartType);
                this.showError('Failed to update chart data');
            });
        },

        /**
         * Refresh entire dashboard
         */
        refreshDashboard: function() {
            const $btn = $('.dashboard-refresh');
            const originalText = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> ' + (eyeBookAdmin.strings.loading || 'Refreshing...'));
            $btn.prop('disabled', true);
            
            // Refresh stats
            this.refreshStats().then(() => {
                // Refresh charts
                return this.refreshAllCharts();
            }).then(() => {
                // Refresh recent activities
                return this.refreshRecentActivities();
            }).then(() => {
                this.showSuccess(eyeBookAdmin.strings.success || 'Dashboard refreshed successfully');
            }).catch(error => {
                console.error('Dashboard refresh failed:', error);
                this.showError('Failed to refresh dashboard');
            }).finally(() => {
                $btn.html(originalText);
                $btn.prop('disabled', false);
            });
        },

        /**
         * Export dashboard data
         */
        exportDashboard: function(e) {
            const format = $(e.target).data('format') || 'pdf';
            
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eye_book_export_dashboard',
                    format: format,
                    nonce: eyeBookAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Trigger download
                        const link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = response.data.filename;
                        link.click();
                    } else {
                        this.showError(response.data.message || 'Export failed');
                    }
                }.bind(this),
                error: function() {
                    this.showError('Export request failed');
                }.bind(this)
            });
        },

        /**
         * Handle stat card clicks
         */
        handleStatCardClick: function(e) {
            const $card = $(e.currentTarget);
            const type = $card.data('type');
            const url = $card.data('url');
            
            if (url) {
                window.location.href = url;
            } else {
                // Default actions based on type
                switch (type) {
                    case 'appointments':
                        window.location.href = eyeBookAdmin.pages.appointments || 'admin.php?page=eye-book-appointments';
                        break;
                    case 'patients':
                        window.location.href = eyeBookAdmin.pages.patients || 'admin.php?page=eye-book-patients';
                        break;
                    case 'providers':
                        window.location.href = eyeBookAdmin.pages.providers || 'admin.php?page=eye-book-providers';
                        break;
                }
            }
        },

        /**
         * Initialize search functionality
         */
        initializeSearchFunctionality: function() {
            const $searchInput = $('.eye-book-search-input');
            if (!$searchInput.length) return;

            // Add search results container
            $searchInput.parent().append('<div class="eye-book-search-results" style="display: none;"></div>');
        },

        /**
         * Perform search
         */
        performSearch: function(e) {
            const query = $(e.target).val().trim();
            const $results = $('.eye-book-search-results');
            
            if (query.length < 2) {
                $results.hide();
                return;
            }
            
            $results.html('<div class="search-loading"><i class="fas fa-spinner fa-spin"></i> Searching...</div>').show();
            
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eye_book_global_search',
                    query: query,
                    nonce: eyeBookAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.displaySearchResults(response.data);
                    } else {
                        $results.html('<div class="search-no-results">No results found</div>');
                    }
                }.bind(this),
                error: function() {
                    $results.html('<div class="search-error">Search failed</div>');
                }
            });
        },

        /**
         * Display search results
         */
        displaySearchResults: function(results) {
            const $results = $('.eye-book-search-results');
            let html = '';
            
            if (results.patients && results.patients.length > 0) {
                html += '<div class="search-section"><h4>Patients</h4>';
                results.patients.forEach(patient => {
                    html += `<div class="search-item" data-type="patient" data-id="${patient.id}">
                        <div class="search-item-icon"><i class="fas fa-user"></i></div>
                        <div class="search-item-content">
                            <div class="search-item-title">${patient.first_name} ${patient.last_name}</div>
                            <div class="search-item-meta">${patient.email}</div>
                        </div>
                    </div>`;
                });
                html += '</div>';
            }
            
            if (results.appointments && results.appointments.length > 0) {
                html += '<div class="search-section"><h4>Appointments</h4>';
                results.appointments.forEach(appointment => {
                    html += `<div class="search-item" data-type="appointment" data-id="${appointment.id}">
                        <div class="search-item-icon"><i class="fas fa-calendar"></i></div>
                        <div class="search-item-content">
                            <div class="search-item-title">${appointment.patient_name}</div>
                            <div class="search-item-meta">${appointment.date} at ${appointment.time}</div>
                        </div>
                    </div>`;
                });
                html += '</div>';
            }
            
            if (html === '') {
                html = '<div class="search-no-results">No results found</div>';
            }
            
            $results.html(html);
            
            // Bind click events
            $('.search-item').on('click', this.handleSearchItemClick.bind(this));
        },

        /**
         * Handle search item clicks
         */
        handleSearchItemClick: function(e) {
            const $item = $(e.currentTarget);
            const type = $item.data('type');
            const id = $item.data('id');
            
            $('.eye-book-search-results').hide();
            $('.eye-book-search-input').val('');
            
            // Navigate to the appropriate page
            switch (type) {
                case 'patient':
                    window.location.href = `admin.php?page=eye-book-patients&action=view&id=${id}`;
                    break;
                case 'appointment':
                    window.location.href = `admin.php?page=eye-book-appointments&action=edit&id=${id}`;
                    break;
            }
        },

        /**
         * Initialize notifications
         */
        initializeNotifications: function() {
            // Check for pending notifications
            this.checkNotifications();
            
            // Set up periodic checks
            setInterval(this.checkNotifications.bind(this), 300000); // 5 minutes
        },

        /**
         * Check for notifications
         */
        checkNotifications: function() {
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eye_book_check_notifications',
                    nonce: eyeBookAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.updateNotificationBadges(response.data);
                    }
                }.bind(this)
            });
        },

        /**
         * Update notification badges
         */
        updateNotificationBadges: function(notifications) {
            // Update appointment notification badge
            const appointmentCount = notifications.pending_appointments || 0;
            this.updateBadge('.notification-appointments', appointmentCount);
            
            // Update general notification badge
            const totalCount = notifications.total || 0;
            this.updateBadge('.notification-general', totalCount);
        },

        /**
         * Update a notification badge
         */
        updateBadge: function(selector, count) {
            const $badge = $(selector);
            if (count > 0) {
                $badge.text(count).show();
            } else {
                $badge.hide();
            }
        },

        /**
         * Start auto-refresh
         */
        startAutoRefresh: function() {
            // Clear any existing interval
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
            }
            
            // Set up new interval (5 minutes)
            this.refreshInterval = setInterval(() => {
                this.refreshStats();
                this.checkNotifications();
            }, 300000);
        },

        /**
         * Refresh statistics
         */
        refreshStats: function() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: eyeBookAdmin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'eye_book_dashboard_stats',
                        nonce: eyeBookAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            this.updateStatsDisplay(response.data);
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data.message || 'Failed to fetch stats'));
                        }
                    }.bind(this),
                    error: function() {
                        reject(new Error('Network error'));
                    }
                });
            });
        },

        /**
         * Update stats display
         */
        updateStatsDisplay: function(stats) {
            // Update stat card values with animation
            this.animateStatValue('[data-stat="today-appointments"]', stats.today_appointments || 0);
            this.animateStatValue('[data-stat="week-appointments"]', stats.week_appointments || 0);
            this.animateStatValue('[data-stat="total-patients"]', stats.total_patients || 0);
            this.animateStatValue('[data-stat="new-patients"]', stats.new_patients || 0);
        },

        /**
         * Animate stat value change
         */
        animateStatValue: function(selector, newValue) {
            const $element = $(selector);
            if (!$element.length) return;
            
            const currentValue = parseInt($element.text().replace(/,/g, '')) || 0;
            
            if (currentValue === newValue) return;
            
            // Add animation class
            $element.addClass('stat-updating');
            
            // Animate the counter
            $({ value: currentValue }).animate({ value: newValue }, {
                duration: 1000,
                easing: 'easeOutCubic',
                step: function() {
                    $element.text(Math.floor(this.value).toLocaleString());
                },
                complete: function() {
                    $element.text(newValue.toLocaleString());
                    $element.removeClass('stat-updating');
                }
            });
        },

        /**
         * Get last 7 days labels
         */
        getLast7Days: function() {
            const days = [];
            for (let i = 6; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                days.push(date.toLocaleDateString('en-US', { weekday: 'short' }));
            }
            return days;
        },

        /**
         * Fetch chart data
         */
        fetchChartData: function(chartType, period) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: eyeBookAdmin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'eye_book_chart_data',
                        chart_type: chartType,
                        period: period,
                        nonce: eyeBookAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data.message));
                        }
                    },
                    error: function() {
                        reject(new Error('Network error'));
                    }
                });
            });
        },

        /**
         * Show/hide chart loading
         */
        showChartLoading: function(chartType) {
            $(`#${chartType}Chart`).parent().addClass('loading');
        },

        hideChartLoading: function(chartType) {
            $(`#${chartType}Chart`).parent().removeClass('loading');
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
            const $notification = $(`
                <div class="eye-book-toast eye-book-notification-${type}">
                    <div class="eye-book-toast-icon">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    </div>
                    <div class="eye-book-toast-content">
                        <div class="eye-book-toast-message">${message}</div>
                    </div>
                    <button class="eye-book-toast-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `);

            // Add to container
            if (!$('.eye-book-toast-container').length) {
                $('body').append('<div class="eye-book-toast-container"></div>');
            }
            
            $('.eye-book-toast-container').append($notification);
            
            // Show with animation
            setTimeout(() => $notification.addClass('show'), 100);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                $notification.removeClass('show');
                setTimeout(() => $notification.remove(), 300);
            }, 5000);
            
            // Close button functionality
            $notification.find('.eye-book-toast-close').on('click', function() {
                $notification.removeClass('show');
                setTimeout(() => $notification.remove(), 300);
            });
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
        if ($('.eye-book-dashboard').length) {
            EyeBookDashboard.init();
        }
    });

})(jQuery);