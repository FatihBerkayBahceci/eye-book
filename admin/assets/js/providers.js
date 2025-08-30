/**
 * Eye Book Providers Management JavaScript
 * 
 * @package EyeBook
 * @subpackage Admin/Assets/JS
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Initialize namespace
    if (typeof window.EyeBook === 'undefined') {
        window.EyeBook = {};
    }

    window.EyeBook.Providers = {
        // Properties
        currentPage: 1,
        itemsPerPage: 20,
        totalItems: 0,
        sortBy: 'last_name',
        sortOrder: 'ASC',
        filters: {
            search: '',
            specialization: '',
            status: ''
        },

        // Initialize
        init: function() {
            this.bindEvents();
            this.loadProviders();
            this.setupModal();
        },

        // Bind event handlers
        bindEvents: function() {
            var self = this;

            // Add new provider button
            $('#add-new-provider').on('click', function(e) {
                e.preventDefault();
                self.openModal();
            });

            // Modal events
            $('.eye-book-modal-close, #provider-modal-cancel').on('click', function() {
                self.closeModal();
            });

            $('#provider-modal-save').on('click', function() {
                self.saveProvider();
            });

            // Tab navigation
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                var tabId = $(this).attr('href');
                self.switchTab(tabId);
            });

            // Filter events
            $('#provider-filter-apply').on('click', function() {
                self.applyFilters();
            });

            $('#provider-filter-clear').on('click', function() {
                self.clearFilters();
            });

            // Search with debounce
            var searchTimer;
            $('#provider-search').on('input', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function() {
                    self.applyFilters();
                }, 300);
            });

            // Sorting
            $('.manage-column.sortable a').on('click', function(e) {
                e.preventDefault();
                var column = $(this).closest('th').attr('class').match(/column-([^\s]+)/)[1];
                self.sortTable(column);
            });

            // Bulk actions
            $('#bulk-action-apply').on('click', function() {
                self.performBulkAction();
            });

            // Select all checkbox
            $('#cb-select-all').on('change', function() {
                $('.provider-checkbox').prop('checked', this.checked);
            });

            // Schedule management
            $('.day-enabled').on('change', function() {
                var dayRow = $(this).closest('.schedule-day');
                var timeInputs = dayRow.find('.start-time, .end-time, .has-break');
                if (this.checked) {
                    timeInputs.prop('disabled', false);
                } else {
                    timeInputs.prop('disabled', true);
                }
            });

            $('.has-break').on('change', function() {
                var dayRow = $(this).closest('.schedule-day');
                var breakInputs = dayRow.find('.break-start, .break-end');
                if (this.checked) {
                    breakInputs.prop('disabled', false);
                } else {
                    breakInputs.prop('disabled', true);
                }
            });

            // Export
            $('#export-providers').on('click', function() {
                self.exportProviders();
            });

            // Manage schedules
            $('#manage-schedules').on('click', function() {
                window.location.href = 'admin.php?page=eye-book-provider-schedules';
            });
        },

        // Setup modal
        setupModal: function() {
            // Phone number formatting
            $('#phone, #mobile-phone, #emergency-phone').on('input', function() {
                var value = this.value.replace(/\D/g, '');
                var formattedValue = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
                if (value.length >= 10) {
                    this.value = formattedValue;
                }
            });

            // Initialize schedule toggles
            $('.day-enabled').each(function() {
                $(this).trigger('change');
            });

            $('.has-break').each(function() {
                $(this).trigger('change');
            });
        },

        // Load providers list
        loadProviders: function() {
            var self = this;
            
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eye_book_get_providers',
                    nonce: eyeBookAdmin.nonce,
                    page: self.currentPage,
                    per_page: self.itemsPerPage,
                    sort_by: self.sortBy,
                    sort_order: self.sortOrder,
                    filters: self.filters
                },
                beforeSend: function() {
                    $('#providers-list').html('<tr><td colspan="9" class="eye-book-loading">Loading providers...</td></tr>');
                },
                success: function(response) {
                    if (response.success) {
                        self.renderProviders(response.data.providers);
                        self.updatePagination(response.data.pagination);
                        self.totalItems = response.data.total;
                        $('#total-providers').text(response.data.total);
                    } else {
                        self.showError(response.data || 'Failed to load providers');
                    }
                },
                error: function() {
                    self.showError('An error occurred while loading providers');
                }
            });
        },

        // Render providers table
        renderProviders: function(providers) {
            var html = '';
            
            if (providers.length === 0) {
                html = '<tr><td colspan="9" class="eye-book-no-results">No providers found.</td></tr>';
            } else {
                providers.forEach(function(provider) {
                    var fullName = provider.title ? provider.title + ' ' : '';
                    fullName += provider.first_name + ' ' + provider.last_name;
                    if (provider.credentials) {
                        fullName += ', ' + provider.credentials;
                    }
                    
                    var statusClass = 'status-' + provider.status;
                    var statusText = provider.status.charAt(0).toUpperCase() + provider.status.slice(1).replace('_', ' ');
                    
                    html += '<tr>';
                    html += '<th scope="row" class="check-column">';
                    html += '<input type="checkbox" class="provider-checkbox" value="' + provider.id + '">';
                    html += '</th>';
                    html += '<td>' + provider.id + '</td>';
                    html += '<td><strong>' + fullName + '</strong>';
                    if (provider.display_name && provider.display_name !== fullName) {
                        html += '<br><small>(' + provider.display_name + ')</small>';
                    }
                    html += '</td>';
                    html += '<td>' + provider.specialization.charAt(0).toUpperCase() + provider.specialization.slice(1).replace('_', ' ') + '</td>';
                    html += '<td>' + (provider.credentials || '-') + '</td>';
                    html += '<td>';
                    if (provider.locations && provider.locations.length > 0) {
                        html += provider.locations.slice(0, 2).join('<br>');
                        if (provider.locations.length > 2) {
                            html += '<br><small>+' + (provider.locations.length - 2) + ' more</small>';
                        }
                    } else {
                        html += '-';
                    }
                    html += '</td>';
                    html += '<td>';
                    if (provider.email) {
                        html += '<a href="mailto:' + provider.email + '">' + provider.email + '</a><br>';
                    }
                    if (provider.phone) {
                        html += '<a href="tel:' + provider.phone + '">' + provider.phone + '</a>';
                    }
                    html += '</td>';
                    html += '<td><span class="provider-status ' + statusClass + '">' + statusText + '</span></td>';
                    html += '<td class="row-actions">';
                    html += '<a href="#" class="edit-provider" data-id="' + provider.id + '">Edit</a> | ';
                    html += '<a href="#" class="view-schedule" data-id="' + provider.id + '">Schedule</a> | ';
                    html += '<a href="#" class="view-provider" data-id="' + provider.id + '">View</a> | ';
                    html += '<a href="#" class="delete-provider" data-id="' + provider.id + '" style="color: #a00;">Delete</a>';
                    html += '</td>';
                    html += '</tr>';
                });
            }

            $('#providers-list').html(html);
            this.bindRowActions();
        },

        // Bind row action events
        bindRowActions: function() {
            var self = this;

            $('.edit-provider').on('click', function(e) {
                e.preventDefault();
                var providerId = $(this).data('id');
                self.editProvider(providerId);
            });

            $('.view-provider').on('click', function(e) {
                e.preventDefault();
                var providerId = $(this).data('id');
                self.viewProvider(providerId);
            });

            $('.view-schedule').on('click', function(e) {
                e.preventDefault();
                var providerId = $(this).data('id');
                window.location.href = 'admin.php?page=eye-book-provider-schedules&provider_id=' + providerId;
            });

            $('.delete-provider').on('click', function(e) {
                e.preventDefault();
                var providerId = $(this).data('id');
                if (confirm('Are you sure you want to delete this provider?')) {
                    self.deleteProvider(providerId);
                }
            });
        },

        // Open modal for new/edit provider
        openModal: function(provider) {
            if (provider) {
                $('#provider-modal-title').text('Edit Provider');
                this.populateForm(provider);
            } else {
                $('#provider-modal-title').text('Add New Provider');
                this.clearForm();
            }
            $('#provider-modal').fadeIn();
        },

        // Close modal
        closeModal: function() {
            $('#provider-modal').fadeOut();
            this.clearForm();
        },

        // Switch tabs in modal
        switchTab: function(tabId) {
            $('.nav-tab').removeClass('nav-tab-active');
            $('.tab-content').removeClass('active');
            
            $('a[href="' + tabId + '"]').addClass('nav-tab-active');
            $(tabId).addClass('active');
        },

        // Clear form
        clearForm: function() {
            $('#provider-form')[0].reset();
            $('#provider-id').val('');
            $('.nav-tab').removeClass('nav-tab-active');
            $('.tab-content').removeClass('active');
            $('.nav-tab:first').addClass('nav-tab-active');
            $('.tab-content:first').addClass('active');
            
            // Reset schedule toggles
            $('.day-enabled').prop('checked', false).trigger('change');
            $('.has-break').prop('checked', false).trigger('change');
            
            // Clear location checkboxes
            $('input[name="locations[]"]').prop('checked', false);
        },

        // Populate form with provider data
        populateForm: function(provider) {
            $('#provider-id').val(provider.id);
            $('#first-name').val(provider.first_name);
            $('#last-name').val(provider.last_name);
            $('#title').val(provider.title);
            $('#suffix').val(provider.suffix);
            $('#display-name').val(provider.display_name);
            $('#provider-status').val(provider.status);
            $('#specialization').val(provider.specialization);
            $('#license-number').val(provider.license_number);
            $('#npi-number').val(provider.npi_number);
            $('#dea-number').val(provider.dea_number);
            $('#credentials').val(provider.credentials);
            $('#education').val(provider.education);
            $('#services').val(provider.services_provided);
            $('#email').val(provider.email);
            $('#phone').val(provider.phone);
            $('#mobile-phone').val(provider.mobile_phone);
            $('#emergency-phone').val(provider.emergency_phone);
            $('#address').val(provider.address);
            $('#city').val(provider.city);
            $('#state').val(provider.state);
            $('#zip-code').val(provider.zip_code);
            $('#default-appointment-duration').val(provider.default_appointment_duration);
            $('#buffer-time').val(provider.buffer_time);
            $('#provider-notes').val(provider.notes);

            // Populate schedule
            if (provider.schedule) {
                Object.keys(provider.schedule).forEach(function(day) {
                    var dayData = provider.schedule[day];
                    if (dayData.enabled) {
                        $('input[name="schedule[' + day + '][enabled]"]').prop('checked', true);
                        $('input[name="schedule[' + day + '][start_time]"]').val(dayData.start_time);
                        $('input[name="schedule[' + day + '][end_time]"]').val(dayData.end_time);
                        
                        if (dayData.has_break) {
                            $('input[name="schedule[' + day + '][has_break]"]').prop('checked', true);
                            $('input[name="schedule[' + day + '][break_start]"]').val(dayData.break_start);
                            $('input[name="schedule[' + day + '][break_end]"]').val(dayData.break_end);
                        }
                    }
                });
                
                // Trigger change events to update UI
                $('.day-enabled').trigger('change');
                $('.has-break').trigger('change');
            }

            // Populate locations
            if (provider.location_ids) {
                provider.location_ids.forEach(function(locationId) {
                    $('input[name="locations[]"][value="' + locationId + '"]').prop('checked', true);
                });
            }
        },

        // Save provider
        saveProvider: function() {
            var self = this;
            var formData = new FormData($('#provider-form')[0]);
            formData.append('action', 'eye_book_save_provider');
            formData.append('nonce', eyeBookAdmin.nonce);

            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('#provider-modal-save').prop('disabled', true).text('Saving...');
                },
                success: function(response) {
                    if (response.success) {
                        self.closeModal();
                        self.loadProviders();
                        self.showSuccess('Provider saved successfully');
                    } else {
                        self.showError(response.data || 'Failed to save provider');
                    }
                },
                error: function() {
                    self.showError('An error occurred while saving provider');
                },
                complete: function() {
                    $('#provider-modal-save').prop('disabled', false).text('Save Provider');
                }
            });
        },

        // Edit provider
        editProvider: function(providerId) {
            var self = this;
            
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eye_book_get_provider',
                    nonce: eyeBookAdmin.nonce,
                    provider_id: providerId
                },
                success: function(response) {
                    if (response.success) {
                        self.openModal(response.data);
                    } else {
                        self.showError(response.data || 'Failed to load provider data');
                    }
                },
                error: function() {
                    self.showError('An error occurred while loading provider data');
                }
            });
        },

        // View provider (redirect to provider detail page)
        viewProvider: function(providerId) {
            window.location.href = 'admin.php?page=eye-book-providers&action=view&provider_id=' + providerId;
        },

        // Delete provider
        deleteProvider: function(providerId) {
            var self = this;
            
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eye_book_delete_provider',
                    nonce: eyeBookAdmin.nonce,
                    provider_id: providerId
                },
                success: function(response) {
                    if (response.success) {
                        self.loadProviders();
                        self.showSuccess('Provider deleted successfully');
                    } else {
                        self.showError(response.data || 'Failed to delete provider');
                    }
                },
                error: function() {
                    self.showError('An error occurred while deleting provider');
                }
            });
        },

        // Apply filters
        applyFilters: function() {
            this.filters.search = $('#provider-search').val();
            this.filters.specialization = $('#provider-specialization-filter').val();
            this.filters.status = $('#provider-status-filter').val();
            this.currentPage = 1;
            this.loadProviders();
        },

        // Clear filters
        clearFilters: function() {
            $('#provider-search').val('');
            $('#provider-specialization-filter').val('');
            $('#provider-status-filter').val('');
            this.filters = { search: '', specialization: '', status: '' };
            this.currentPage = 1;
            this.loadProviders();
        },

        // Sort table
        sortTable: function(column) {
            if (this.sortBy === column) {
                this.sortOrder = this.sortOrder === 'ASC' ? 'DESC' : 'ASC';
            } else {
                this.sortBy = column;
                this.sortOrder = 'ASC';
            }
            this.loadProviders();
            this.updateSortIndicators(column);
        },

        // Update sort indicators
        updateSortIndicators: function(activeColumn) {
            $('.sorting-indicator').removeClass('sorted asc desc');
            var activeHeader = $('.column-' + activeColumn + ' .sorting-indicator');
            activeHeader.addClass('sorted ' + this.sortOrder.toLowerCase());
        },

        // Update pagination
        updatePagination: function(pagination) {
            var html = '';
            var totalPages = pagination.total_pages;
            
            if (totalPages > 1) {
                // Previous link
                if (pagination.current_page > 1) {
                    html += '<a class="prev-page button" href="#" data-page="' + (pagination.current_page - 1) + '">&lsaquo;</a>';
                } else {
                    html += '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
                }

                // Page numbers
                html += '<span class="paging-input">';
                html += '<label for="current-page-selector" class="screen-reader-text">Current Page</label>';
                html += '<input class="current-page" id="current-page-selector" type="text" name="paged" value="' + pagination.current_page + '" size="2" aria-describedby="table-paging">';
                html += '<span class="tablenav-paging-text"> of <span class="total-pages">' + totalPages + '</span></span>';
                html += '</span>';

                // Next link
                if (pagination.current_page < totalPages) {
                    html += '<a class="next-page button" href="#" data-page="' + (pagination.current_page + 1) + '">&rsaquo;</a>';
                } else {
                    html += '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
                }
            }

            $('#providers-pagination').html(html);
            this.bindPaginationEvents();
        },

        // Bind pagination events
        bindPaginationEvents: function() {
            var self = this;

            $('#providers-pagination .prev-page, #providers-pagination .next-page').on('click', function(e) {
                e.preventDefault();
                var page = parseInt($(this).data('page'));
                if (page > 0) {
                    self.currentPage = page;
                    self.loadProviders();
                }
            });

            $('#providers-pagination .current-page').on('change', function() {
                var page = parseInt($(this).val());
                var totalPages = parseInt($('.total-pages').text());
                if (page > 0 && page <= totalPages) {
                    self.currentPage = page;
                    self.loadProviders();
                }
            });
        },

        // Perform bulk action
        performBulkAction: function() {
            var action = $('#bulk-action-selector-bottom').val();
            if (action === '-1') {
                alert('Please select a bulk action.');
                return;
            }

            var selectedIds = [];
            $('.provider-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                alert('Please select providers to perform the action on.');
                return;
            }

            if (action === 'delete' && !confirm('Are you sure you want to delete the selected providers?')) {
                return;
            }

            var self = this;
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eye_book_bulk_action_providers',
                    nonce: eyeBookAdmin.nonce,
                    bulk_action: action,
                    provider_ids: selectedIds
                },
                success: function(response) {
                    if (response.success) {
                        self.loadProviders();
                        self.showSuccess(response.data || 'Bulk action completed successfully');
                    } else {
                        self.showError(response.data || 'Failed to perform bulk action');
                    }
                },
                error: function() {
                    self.showError('An error occurred while performing bulk action');
                }
            });
        },

        // Export providers
        exportProviders: function() {
            window.location.href = eyeBookAdmin.ajax_url + '?action=eye_book_export_providers&nonce=' + eyeBookAdmin.nonce;
        },

        // Show success message
        showSuccess: function(message) {
            this.showNotice(message, 'success');
        },

        // Show error message
        showError: function(message) {
            this.showNotice(message, 'error');
        },

        // Show notice
        showNotice: function(message, type) {
            var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.wrap h1').after(notice);
            
            setTimeout(function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

})(jQuery);