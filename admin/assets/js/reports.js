/**
 * Eye-Book Reports Management
 * 
 * @package EyeBook
 * @since 1.0.0
 */
(function($) {
    'use strict';

    let reportsModule = {
        init: function() {
            this.bindEvents();
            this.initializeDatePickers();
            this.loadInitialData();
        },

        bindEvents: function() {
            $(document).on('change', '#report_type', this.onReportTypeChange.bind(this));
            $(document).on('change', '#date_from, #date_to', this.onDateRangeChange.bind(this));
            $(document).on('click', '.generate-report-btn', this.generateReport.bind(this));
            $(document).on('click', '.export-report-btn', this.exportReport.bind(this));
            $(document).on('click', '.print-report-btn', this.printReport.bind(this));
        },

        initializeDatePickers: function() {
            if (typeof flatpickr !== 'undefined') {
                flatpickr('#date_from', {
                    dateFormat: 'Y-m-d',
                    maxDate: 'today',
                    onChange: this.onDateRangeChange.bind(this)
                });

                flatpickr('#date_to', {
                    dateFormat: 'Y-m-d',
                    maxDate: 'today',
                    onChange: this.onDateRangeChange.bind(this)
                });
            }
        },

        loadInitialData: function() {
            const reportType = $('#report_type').val();
            if (reportType) {
                this.generateReport();
            }
        },

        onReportTypeChange: function(e) {
            const reportType = $(e.target).val();
            this.updateReportOptions(reportType);
            this.generateReport();
        },

        onDateRangeChange: function() {
            // Auto-generate report when date range changes
            clearTimeout(this.dateChangeTimeout);
            this.dateChangeTimeout = setTimeout(() => {
                this.generateReport();
            }, 1000);
        },

        updateReportOptions: function(reportType) {
            const $options = $('#report-options');
            
            // Clear existing options
            $options.empty();

            // Add type-specific options
            switch (reportType) {
                case 'appointments':
                    $options.append(this.getAppointmentReportOptions());
                    break;
                case 'patients':
                    $options.append(this.getPatientReportOptions());
                    break;
                case 'providers':
                    $options.append(this.getProviderReportOptions());
                    break;
                case 'revenue':
                    $options.append(this.getRevenueReportOptions());
                    break;
            }
        },

        getAppointmentReportOptions: function() {
            return `
                <div class="report-option">
                    <label>
                        <input type="checkbox" name="include_cancelled" value="1">
                        Include Cancelled Appointments
                    </label>
                </div>
                <div class="report-option">
                    <label for="provider_filter">Provider:</label>
                    <select id="provider_filter" name="provider_filter">
                        <option value="">All Providers</option>
                        ${this.getProvidersOptions()}
                    </select>
                </div>
                <div class="report-option">
                    <label for="location_filter">Location:</label>
                    <select id="location_filter" name="location_filter">
                        <option value="">All Locations</option>
                        ${this.getLocationsOptions()}
                    </select>
                </div>
            `;
        },

        getPatientReportOptions: function() {
            return `
                <div class="report-option">
                    <label>
                        <input type="checkbox" name="new_patients_only" value="1">
                        New Patients Only
                    </label>
                </div>
                <div class="report-option">
                    <label>
                        <input type="checkbox" name="include_demographics" value="1" checked>
                        Include Demographics
                    </label>
                </div>
            `;
        },

        getProviderReportOptions: function() {
            return `
                <div class="report-option">
                    <label>
                        <input type="checkbox" name="include_utilization" value="1" checked>
                        Include Utilization Stats
                    </label>
                </div>
                <div class="report-option">
                    <label>
                        <input type="checkbox" name="include_revenue" value="1">
                        Include Revenue Data
                    </label>
                </div>
            `;
        },

        getRevenueReportOptions: function() {
            return `
                <div class="report-option">
                    <label for="group_by">Group By:</label>
                    <select id="group_by" name="group_by">
                        <option value="day">Daily</option>
                        <option value="week">Weekly</option>
                        <option value="month">Monthly</option>
                    </select>
                </div>
            `;
        },

        generateReport: function(e) {
            if (e) e.preventDefault();

            const $button = $(e ? e.target : '.generate-report-btn');
            const originalText = $button.text();
            
            $button.text('Generating...').prop('disabled', true);

            const reportData = {
                action: 'eye_book_generate_report',
                nonce: eyeBookAdmin.nonce,
                report_type: $('#report_type').val(),
                date_from: $('#date_from').val(),
                date_to: $('#date_to').val(),
                options: this.getReportOptions()
            };

            $.post(eyeBookAdmin.ajax_url, reportData)
                .done((response) => {
                    if (response.success) {
                        this.displayReport(response.data);
                    } else {
                        this.showError(response.data.message || 'Failed to generate report');
                    }
                })
                .fail((xhr, status, error) => {
                    this.showError('Network error: ' + error);
                })
                .always(() => {
                    $button.text(originalText).prop('disabled', false);
                });
        },

        getReportOptions: function() {
            const options = {};
            $('#report-options input, #report-options select').each(function() {
                const $field = $(this);
                if ($field.is(':checkbox')) {
                    options[$field.attr('name')] = $field.is(':checked') ? 1 : 0;
                } else {
                    options[$field.attr('name')] = $field.val();
                }
            });
            return options;
        },

        displayReport: function(data) {
            const $container = $('#report-container');
            
            if (!data || !data.type) {
                $container.html('<div class="notice notice-warning"><p>No data available for the selected criteria.</p></div>');
                return;
            }

            // Clear existing content
            $container.empty();

            // Generate report based on type
            switch (data.type) {
                case 'appointments':
                    this.displayAppointmentsReport(data.data, $container);
                    break;
                case 'patients':
                    this.displayPatientsReport(data.data, $container);
                    break;
                case 'providers':
                    this.displayProvidersReport(data.data, $container);
                    break;
                case 'revenue':
                    this.displayRevenueReport(data.data, $container);
                    break;
                default:
                    $container.html('<div class="notice notice-error"><p>Unknown report type.</p></div>');
            }

            // Show export options
            this.showExportOptions();
        },

        displayAppointmentsReport: function(data, $container) {
            let html = '<div class="report-section">';
            html += '<h3>Appointments Summary</h3>';
            
            if (data.appointments_by_day) {
                html += '<div class="chart-container">';
                html += '<canvas id="appointments-chart"></canvas>';
                html += '</div>';
                
                // Create chart after HTML is added
                setTimeout(() => {
                    this.createAppointmentsChart(data.appointments_by_day);
                }, 100);
            }

            if (data.appointments_by_status) {
                html += '<div class="status-breakdown">';
                html += '<h4>By Status</h4>';
                html += '<table class="wp-list-table widefat fixed striped">';
                html += '<thead><tr><th>Status</th><th>Count</th></tr></thead>';
                html += '<tbody>';
                
                data.appointments_by_status.forEach(status => {
                    html += `<tr><td>${status.status}</td><td>${status.count}</td></tr>`;
                });
                
                html += '</tbody></table>';
                html += '</div>';
            }

            html += '</div>';
            $container.html(html);
        },

        displayPatientsReport: function(data, $container) {
            let html = '<div class="report-section">';
            html += '<h3>Patients Report</h3>';
            
            if (data.total_patients !== undefined) {
                html += `<div class="stat-box">Total Patients: <strong>${data.total_patients}</strong></div>`;
            }

            html += '</div>';
            $container.html(html);
        },

        displayProvidersReport: function(data, $container) {
            let html = '<div class="report-section">';
            html += '<h3>Providers Report</h3>';
            
            if (data.provider_utilization) {
                html += '<table class="wp-list-table widefat fixed striped">';
                html += '<thead><tr><th>Provider</th><th>Appointments</th></tr></thead>';
                html += '<tbody>';
                
                data.provider_utilization.forEach(provider => {
                    const providerName = provider.wp_user_id ? `User ${provider.wp_user_id}` : 'Unknown';
                    html += `<tr><td>${providerName}</td><td>${provider.appointment_count}</td></tr>`;
                });
                
                html += '</tbody></table>';
            }

            html += '</div>';
            $container.html(html);
        },

        displayRevenueReport: function(data, $container) {
            let html = '<div class="report-section">';
            html += '<h3>Revenue Report</h3>';
            html += '<p>Revenue reporting requires payment integration.</p>';
            html += '</div>';
            $container.html(html);
        },

        createAppointmentsChart: function(data) {
            if (typeof Chart === 'undefined') return;

            const ctx = document.getElementById('appointments-chart');
            if (!ctx) return;

            const labels = data.map(item => item.date);
            const counts = data.map(item => item.count);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Appointments',
                        data: counts,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        },

        showExportOptions: function() {
            $('.export-options').show();
        },

        exportReport: function(e) {
            e.preventDefault();
            
            const format = $(e.target).data('format') || 'pdf';
            const reportData = this.getCurrentReportData();
            
            // Create download link
            const params = new URLSearchParams({
                action: 'eye_book_export_report',
                format: format,
                ...reportData,
                nonce: eyeBookAdmin.nonce
            });
            
            const url = eyeBookAdmin.ajax_url + '?' + params.toString();
            window.open(url, '_blank');
        },

        printReport: function(e) {
            e.preventDefault();
            window.print();
        },

        getCurrentReportData: function() {
            return {
                report_type: $('#report_type').val(),
                date_from: $('#date_from').val(),
                date_to: $('#date_to').val(),
                options: JSON.stringify(this.getReportOptions())
            };
        },

        getProvidersOptions: function() {
            // This would typically be populated from server data
            return '<option value="1">Dr. Smith</option><option value="2">Dr. Johnson</option>';
        },

        getLocationsOptions: function() {
            // This would typically be populated from server data
            return '<option value="1">Main Clinic</option><option value="2">Downtown Office</option>';
        },

        showError: function(message) {
            const $container = $('#report-container');
            $container.html(`<div class="notice notice-error"><p>${message}</p></div>`);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('body').hasClass('toplevel_page_eye-book-reports')) {
            reportsModule.init();
        }
    });

})(jQuery);