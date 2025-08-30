/**
 * Eye Book Locations Management JavaScript
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

    window.EyeBook.Locations = {
        // Properties
        currentPage: 1,
        itemsPerPage: 20,
        totalItems: 0,
        sortBy: 'name',
        sortOrder: 'ASC',
        filters: {
            search: '',
            status: ''
        },

        // Initialize
        init: function() {
            this.bindEvents();
            this.loadLocations();
            this.setupModal();
        },

        // Bind event handlers
        bindEvents: function() {
            var self = this;

            // Add new location button
            $('#add-new-location').on('click', function(e) {
                e.preventDefault();
                self.openModal();
            });

            // Modal events
            $('.eye-book-modal-close, #location-modal-cancel').on('click', function() {
                self.closeModal();
            });

            $('#location-modal-save').on('click', function() {
                self.saveLocation();
            });

            // Tab navigation
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                var tabId = $(this).attr('href');
                self.switchTab(tabId);
            });

            // Filter events
            $('#location-filter-apply').on('click', function() {
                self.applyFilters();
            });

            $('#location-filter-clear').on('click', function() {
                self.clearFilters();
            });

            // Search with debounce
            var searchTimer;
            $('#location-search').on('input', function() {
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
                $('.location-checkbox').prop('checked', this.checked);
            });

            // Schedule management
            $('.day-enabled').on('change', function() {
                var dayRow = $(this).closest('.schedule-day');
                var timeInputs = dayRow.find('.open-time, .close-time, .has-break');
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
            $('#export-locations').on('click', function() {
                self.exportLocations();
            });
        },

        // Setup modal
        setupModal: function() {
            var self = this;

            // Phone number formatting
            $('#phone, #fax').on('input', function() {
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

            // Auto-generate location code based on name
            $('#location-name').on('input', function() {
                if (!$('#location-code').val()) {
                    var code = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 8);
                    $('#location-code').val(code);
                }
            });
        },

        // Load locations list
        loadLocations: function() {
            var self = this;
            
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eye_book_get_locations',
                    nonce: eyeBookAdmin.nonce,
                    page: self.currentPage,
                    per_page: self.itemsPerPage,
                    sort_by: self.sortBy,
                    sort_order: self.sortOrder,
                    filters: self.filters
                },
                beforeSend: function() {
                    $('#locations-list').html('<tr><td colspan="9" class="eye-book-loading">Loading locations...</td></tr>');
                },
                success: function(response) {
                    if (response.success) {
                        self.renderLocations(response.data.locations);
                        self.updatePagination(response.data.pagination);
                        self.totalItems = response.data.total;
                        $('#total-locations').text(response.data.total);
                    } else {
                        self.showError(response.data || 'Failed to load locations');
                    }
                },
                error: function() {
                    self.showError('An error occurred while loading locations');
                }
            });
        },

        // Render locations table
        renderLocations: function(locations) {
            var html = '';
            
            if (locations.length === 0) {
                html = '<tr><td colspan="9" class="eye-book-no-results">No locations found.</td></tr>';
            } else {
                locations.forEach(function(location) {
                    var address = '';
                    if (location.address) {
                        address = location.address;
                        if (location.city) address += '<br>' + location.city;
                        if (location.state) address += ', ' + location.state;
                        if (location.zip_code) address += ' ' + location.zip_code;
                    }
                    
                    var contact = '';
                    if (location.phone) {
                        contact = '<a href="tel:' + location.phone + '">' + location.phone + '</a>';
                    }
                    if (location.email) {
                        contact += (contact ? '<br>' : '') + '<a href="mailto:' + location.email + '">' + location.email + '</a>';
                    }
                    
                    var hours = location.hours_summary || '-';
                    var providers = '';
                    if (location.providers && location.providers.length > 0) {
                        providers = location.providers.slice(0, 2).join('<br>');
                        if (location.providers.length > 2) {
                            providers += '<br><small>+' + (location.providers.length - 2) + ' more</small>';
                        }
                    } else {
                        providers = '-';
                    }
                    
                    var statusClass = 'status-' + location.status;
                    var statusText = location.status.charAt(0).toUpperCase() + location.status.slice(1);
                    
                    html += '<tr>';
                    html += '<th scope="row" class="check-column">';
                    html += '<input type="checkbox" class="location-checkbox" value="' + location.id + '">';
                    html += '</th>';
                    html += '<td>' + location.id + '</td>';
                    html += '<td><strong>' + location.name + '</strong>';
                    if (location.location_code) {
                        html += '<br><small>Code: ' + location.location_code + '</small>';
                    }
                    if (location.location_type) {
                        html += '<br><small>' + location.location_type.charAt(0).toUpperCase() + location.location_type.slice(1).replace('_', ' ') + '</small>';
                    }
                    html += '</td>';
                    html += '<td>' + (address || '-') + '</td>';
                    html += '<td>' + (contact || '-') + '</td>';
                    html += '<td>' + hours + '</td>';
                    html += '<td>' + providers + '</td>';
                    html += '<td><span class="location-status ' + statusClass + '">' + statusText + '</span></td>';
                    html += '<td class="row-actions">';
                    html += '<a href="#" class="edit-location" data-id="' + location.id + '">Edit</a> | ';
                    html += '<a href="#" class="view-location" data-id="' + location.id + '">View</a> | ';
                    html += '<a href="#" class="delete-location" data-id="' + location.id + '" style="color: #a00;">Delete</a>';
                    html += '</td>';
                    html += '</tr>';
                });
            }

            $('#locations-list').html(html);
            this.bindRowActions();
        },

        // Bind row action events
        bindRowActions: function() {
            var self = this;

            $('.edit-location').on('click', function(e) {
                e.preventDefault();
                var locationId = $(this).data('id');
                self.editLocation(locationId);
            });

            $('.view-location').on('click', function(e) {
                e.preventDefault();
                var locationId = $(this).data('id');
                self.viewLocation(locationId);
            });

            $('.delete-location').on('click', function(e) {
                e.preventDefault();
                var locationId = $(this).data('id');
                if (confirm('Are you sure you want to delete this location?')) {
                    self.deleteLocation(locationId);
                }
            });
        },

        // Open modal for new/edit location
        openModal: function(location) {
            if (location) {
                $('#location-modal-title').text('Edit Location');
                this.populateForm(location);
            } else {
                $('#location-modal-title').text('Add New Location');
                this.clearForm();
            }
            $('#location-modal').fadeIn();
        },

        // Close modal
        closeModal: function() {
            $('#location-modal').fadeOut();
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
            $('#location-form')[0].reset();
            $('#location-id').val('');
            $('.nav-tab').removeClass('nav-tab-active');
            $('.tab-content').removeClass('active');
            $('.nav-tab:first').addClass('nav-tab-active');
            $('.tab-content:first').addClass('active');
            
            // Reset schedule toggles
            $('.day-enabled').prop('checked', false).trigger('change');
            $('.has-break').prop('checked', false).trigger('change');
            
            // Set default values
            $('#booking-enabled').prop('checked', true);
            $('#advance-booking-days').val('30');
            $('#minimum-notice-hours').val('2');
            $('#examination-rooms').val('1');
            $('#timezone').val('America/New_York');
        },

        // Populate form with location data
        populateForm: function(location) {
            $('#location-id').val(location.id);
            $('#location-name').val(location.name);
            $('#location-code').val(location.location_code);
            $('#location-type').val(location.location_type);
            $('#location-status').val(location.status);
            $('#location-description').val(location.description);
            $('#address').val(location.address);
            $('#city').val(location.city);
            $('#state').val(location.state);
            $('#zip-code').val(location.zip_code);
            $('#phone').val(location.phone);
            $('#fax').val(location.fax);
            $('#email').val(location.email);
            $('#website').val(location.website);
            $('#holiday-hours').val(location.holiday_hours);
            $('#booking-enabled').prop('checked', location.booking_enabled == 1);
            $('#advance-booking-days').val(location.advance_booking_days);
            $('#minimum-notice-hours').val(location.minimum_notice_hours);
            $('#timezone').val(location.timezone);
            $('#examination-rooms').val(location.examination_rooms);
            $('#parking-available').prop('checked', location.parking_available == 1);
            $('#wheelchair-accessible').prop('checked', location.wheelchair_accessible == 1);
            $('#insurance-accepted').val(location.insurance_accepted);
            $('#location-notes').val(location.notes);

            // Populate hours
            if (location.hours) {
                Object.keys(location.hours).forEach(function(day) {
                    var dayData = location.hours[day];
                    if (dayData.enabled) {
                        $('input[name="hours[' + day + '][enabled]"]').prop('checked', true);
                        $('input[name="hours[' + day + '][open_time]"]').val(dayData.open_time);
                        $('input[name="hours[' + day + '][close_time]"]').val(dayData.close_time);
                        
                        if (dayData.has_break) {
                            $('input[name="hours[' + day + '][has_break]"]').prop('checked', true);
                            $('input[name="hours[' + day + '][break_start]"]').val(dayData.break_start);
                            $('input[name="hours[' + day + '][break_end]"]').val(dayData.break_end);
                        }
                    }
                });
                
                // Trigger change events to update UI
                $('.day-enabled').trigger('change');
                $('.has-break').trigger('change');
            }
        },

        // Save location
        saveLocation: function() {
            var self = this;
            var formData = new FormData($('#location-form')[0]);
            formData.append('action', 'eye_book_save_location');
            formData.append('nonce', eyeBookAdmin.nonce);

            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('#location-modal-save').prop('disabled', true).text('Saving...');
                },
                success: function(response) {
                    if (response.success) {
                        self.closeModal();
                        self.loadLocations();
                        self.showSuccess('Location saved successfully');
                    } else {
                        self.showError(response.data || 'Failed to save location');
                    }
                },
                error: function() {
                    self.showError('An error occurred while saving location');
                },
                complete: function() {
                    $('#location-modal-save').prop('disabled', false).text('Save Location');
                }
            });
        },

        // Edit location
        editLocation: function(locationId) {
            var self = this;
            
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eye_book_get_location',
                    nonce: eyeBookAdmin.nonce,
                    location_id: locationId
                },
                success: function(response) {
                    if (response.success) {
                        self.openModal(response.data);
                    } else {
                        self.showError(response.data || 'Failed to load location data');
                    }
                },
                error: function() {
                    self.showError('An error occurred while loading location data');
                }
            });
        },

        // View location (redirect to location detail page)
        viewLocation: function(locationId) {
            window.location.href = 'admin.php?page=eye-book-locations&action=view&location_id=' + locationId;
        },

        // Delete location
        deleteLocation: function(locationId) {
            var self = this;
            
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eye_book_delete_location',
                    nonce: eyeBookAdmin.nonce,
                    location_id: locationId
                },
                success: function(response) {
                    if (response.success) {
                        self.loadLocations();
                        self.showSuccess('Location deleted successfully');
                    } else {
                        self.showError(response.data || 'Failed to delete location');
                    }
                },
                error: function() {
                    self.showError('An error occurred while deleting location');
                }
            });
        },

        // Apply filters
        applyFilters: function() {
            this.filters.search = $('#location-search').val();
            this.filters.status = $('#location-status-filter').val();
            this.currentPage = 1;
            this.loadLocations();
        },

        // Clear filters
        clearFilters: function() {
            $('#location-search').val('');
            $('#location-status-filter').val('');
            this.filters = { search: '', status: '' };
            this.currentPage = 1;
            this.loadLocations();
        },

        // Sort table
        sortTable: function(column) {
            if (this.sortBy === column) {
                this.sortOrder = this.sortOrder === 'ASC' ? 'DESC' : 'ASC';
            } else {
                this.sortBy = column;
                this.sortOrder = 'ASC';
            }
            this.loadLocations();
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

            $('#locations-pagination').html(html);
            this.bindPaginationEvents();
        },

        // Bind pagination events
        bindPaginationEvents: function() {
            var self = this;

            $('#locations-pagination .prev-page, #locations-pagination .next-page').on('click', function(e) {
                e.preventDefault();
                var page = parseInt($(this).data('page'));
                if (page > 0) {
                    self.currentPage = page;
                    self.loadLocations();
                }
            });

            $('#locations-pagination .current-page').on('change', function() {
                var page = parseInt($(this).val());
                var totalPages = parseInt($('.total-pages').text());
                if (page > 0 && page <= totalPages) {
                    self.currentPage = page;
                    self.loadLocations();
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
            $('.location-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                alert('Please select locations to perform the action on.');
                return;
            }

            if (action === 'delete' && !confirm('Are you sure you want to delete the selected locations?')) {
                return;
            }

            var self = this;
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eye_book_bulk_action_locations',
                    nonce: eyeBookAdmin.nonce,
                    bulk_action: action,
                    location_ids: selectedIds
                },
                success: function(response) {
                    if (response.success) {
                        self.loadLocations();
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

        // Export locations
        exportLocations: function() {
            window.location.href = eyeBookAdmin.ajax_url + '?action=eye_book_export_locations&nonce=' + eyeBookAdmin.nonce;
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