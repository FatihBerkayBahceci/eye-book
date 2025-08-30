/**
 * Eye Book Patients Management JavaScript
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

    window.EyeBook.Patients = {
        // Properties
        currentPage: 1,
        itemsPerPage: 20,
        totalItems: 0,
        sortBy: 'last_name',
        sortOrder: 'ASC',
        filters: {
            search: '',
            insurance: '',
            age: ''
        },

        // Initialize
        init: function() {
            this.bindEvents();
            this.loadPatients();
            this.setupModal();
        },

        // Bind event handlers
        bindEvents: function() {
            var self = this;

            // Add new patient button
            $('#add-new-patient').on('click', function(e) {
                e.preventDefault();
                self.openModal();
            });

            // Modal events
            $('#patient-modal-close, #patient-modal-cancel').on('click', function() {
                self.closeModal();
            });

            $('#patient-modal-save').on('click', function() {
                self.savePatient();
            });

            // Tab navigation
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                var tabId = $(this).attr('href');
                self.switchTab(tabId);
            });

            // Filter events
            $('#patient-filter-apply').on('click', function() {
                self.applyFilters();
            });

            $('#patient-filter-clear').on('click', function() {
                self.clearFilters();
            });

            // Search with debounce
            var searchTimer;
            $('#patient-search').on('input', function() {
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
                $('.patient-checkbox').prop('checked', this.checked);
            });

            // Export/Import
            $('#export-patients').on('click', function() {
                self.exportPatients();
            });

            $('#import-patients').on('click', function() {
                self.importPatients();
            });
        },

        // Setup modal
        setupModal: function() {
            // Initialize date pickers
            if ($.fn.datepicker) {
                $('#date-of-birth').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true,
                    yearRange: '-120:+0',
                    maxDate: 0
                });
            }

            // Phone number formatting
            $('#phone, #mobile-phone, #work-phone, #emergency-contact-phone').on('input', function() {
                var value = this.value.replace(/\D/g, '');
                var formattedValue = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
                if (value.length >= 10) {
                    this.value = formattedValue;
                }
            });

            // SSN formatting
            $('#social-security').on('input', function() {
                var value = this.value.replace(/\D/g, '');
                var formattedValue = value.replace(/(\d{3})(\d{2})(\d{4})/, '$1-$2-$3');
                if (value.length >= 9) {
                    this.value = formattedValue;
                }
            });
        },

        // Load patients list
        loadPatients: function() {
            var self = this;
            
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eye_book_get_patients',
                    nonce: eyeBookAdmin.nonce,
                    page: self.currentPage,
                    per_page: self.itemsPerPage,
                    sort_by: self.sortBy,
                    sort_order: self.sortOrder,
                    filters: self.filters
                },
                beforeSend: function() {
                    $('#patients-list').html('<tr><td colspan="8" class="eye-book-loading">Loading patients...</td></tr>');
                },
                success: function(response) {
                    if (response.success) {
                        self.renderPatients(response.data.patients);
                        self.updatePagination(response.data.pagination);
                        self.totalItems = response.data.total;
                        $('#total-patients').text(response.data.total);
                    } else {
                        self.showError(response.data || 'Failed to load patients');
                    }
                },
                error: function() {
                    self.showError('An error occurred while loading patients');
                }
            });
        },

        // Render patients table
        renderPatients: function(patients) {
            var html = '';
            
            if (patients.length === 0) {
                html = '<tr><td colspan="8" class="eye-book-no-results">No patients found.</td></tr>';
            } else {
                patients.forEach(function(patient) {
                    var age = patient.age ? patient.age + ' years' : '-';
                    var lastVisit = patient.last_visit ? new Date(patient.last_visit).toLocaleDateString() : '-';
                    var nextAppointment = patient.next_appointment ? new Date(patient.next_appointment).toLocaleDateString() : '-';
                    
                    html += '<tr>';
                    html += '<th scope="row" class="check-column">';
                    html += '<input type="checkbox" class="patient-checkbox" value="' + patient.id + '">';
                    html += '</th>';
                    html += '<td>' + patient.id + '</td>';
                    html += '<td><strong>' + patient.first_name + ' ' + patient.last_name + '</strong>';
                    if (patient.status !== 'active') {
                        html += ' <span class="patient-status status-' + patient.status + '">(' + patient.status + ')</span>';
                    }
                    html += '</td>';
                    html += '<td>';
                    if (patient.email) {
                        html += '<a href="mailto:' + patient.email + '">' + patient.email + '</a><br>';
                    }
                    if (patient.phone) {
                        html += '<a href="tel:' + patient.phone + '">' + patient.phone + '</a>';
                    }
                    html += '</td>';
                    html += '<td>' + (patient.date_of_birth ? new Date(patient.date_of_birth).toLocaleDateString() : '-') + '<br><small>' + age + '</small></td>';
                    html += '<td>' + (patient.insurance_provider || '-') + '</td>';
                    html += '<td>' + lastVisit + '</td>';
                    html += '<td>' + nextAppointment + '</td>';
                    html += '<td class="row-actions">';
                    html += '<a href="#" class="edit-patient" data-id="' + patient.id + '">Edit</a> | ';
                    html += '<a href="#" class="view-patient" data-id="' + patient.id + '">View</a> | ';
                    html += '<a href="#" class="delete-patient" data-id="' + patient.id + '" style="color: #a00;">Delete</a>';
                    html += '</td>';
                    html += '</tr>';
                });
            }

            $('#patients-list').html(html);
            this.bindRowActions();
        },

        // Bind row action events
        bindRowActions: function() {
            var self = this;

            $('.edit-patient').on('click', function(e) {
                e.preventDefault();
                var patientId = $(this).data('id');
                self.editPatient(patientId);
            });

            $('.view-patient').on('click', function(e) {
                e.preventDefault();
                var patientId = $(this).data('id');
                self.viewPatient(patientId);
            });

            $('.delete-patient').on('click', function(e) {
                e.preventDefault();
                var patientId = $(this).data('id');
                if (confirm('Are you sure you want to delete this patient?')) {
                    self.deletePatient(patientId);
                }
            });
        },

        // Open modal for new/edit patient
        openModal: function(patient) {
            if (patient) {
                $('#patient-modal-title').text('Edit Patient');
                this.populateForm(patient);
            } else {
                $('#patient-modal-title').text('Add New Patient');
                this.clearForm();
            }
            $('#patient-modal').fadeIn();
        },

        // Close modal
        closeModal: function() {
            $('#patient-modal').fadeOut();
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
            $('#patient-form')[0].reset();
            $('#patient-id').val('');
            $('.nav-tab').removeClass('nav-tab-active');
            $('.tab-content').removeClass('active');
            $('.nav-tab:first').addClass('nav-tab-active');
            $('.tab-content:first').addClass('active');
        },

        // Populate form with patient data
        populateForm: function(patient) {
            $('#patient-id').val(patient.id);
            $('#first-name').val(patient.first_name);
            $('#last-name').val(patient.last_name);
            $('#date-of-birth').val(patient.date_of_birth);
            $('#gender').val(patient.gender);
            $('#social-security').val(patient.social_security_number);
            $('#patient-status').val(patient.status);
            $('#email').val(patient.email);
            $('#phone').val(patient.phone);
            $('#mobile-phone').val(patient.mobile_phone);
            $('#work-phone').val(patient.work_phone);
            $('#address').val(patient.address);
            $('#city').val(patient.city);
            $('#state').val(patient.state);
            $('#zip-code').val(patient.zip_code);
            $('#insurance-provider').val(patient.insurance_provider);
            $('#insurance-member-id').val(patient.insurance_member_id);
            $('#insurance-group').val(patient.insurance_group_number);
            $('#copay-amount').val(patient.copay_amount);
            $('#insurance-notes').val(patient.insurance_notes);
            $('#medical-history').val(patient.medical_history);
            $('#current-medications').val(patient.current_medications);
            $('#allergies').val(patient.allergies);
            $('#eye-care-history').val(patient.eye_care_history);
            $('#emergency-contact-name').val(patient.emergency_contact_name);
            $('#emergency-contact-relationship').val(patient.emergency_contact_relationship);
            $('#emergency-contact-phone').val(patient.emergency_contact_phone);
            $('#emergency-contact-email').val(patient.emergency_contact_email);
        },

        // Save patient
        savePatient: function() {
            var self = this;
            var formData = new FormData($('#patient-form')[0]);
            formData.append('action', 'eye_book_save_patient');
            formData.append('nonce', eyeBookAdmin.nonce);

            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('#patient-modal-save').prop('disabled', true).text('Saving...');
                },
                success: function(response) {
                    if (response.success) {
                        self.closeModal();
                        self.loadPatients();
                        self.showSuccess('Patient saved successfully');
                    } else {
                        self.showError(response.data || 'Failed to save patient');
                    }
                },
                error: function() {
                    self.showError('An error occurred while saving patient');
                },
                complete: function() {
                    $('#patient-modal-save').prop('disabled', false).text('Save Patient');
                }
            });
        },

        // Edit patient
        editPatient: function(patientId) {
            var self = this;
            
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eye_book_get_patient',
                    nonce: eyeBookAdmin.nonce,
                    patient_id: patientId
                },
                success: function(response) {
                    if (response.success) {
                        self.openModal(response.data);
                    } else {
                        self.showError(response.data || 'Failed to load patient data');
                    }
                },
                error: function() {
                    self.showError('An error occurred while loading patient data');
                }
            });
        },

        // View patient (redirect to patient detail page)
        viewPatient: function(patientId) {
            window.location.href = 'admin.php?page=eye-book-patients&action=view&patient_id=' + patientId;
        },

        // Delete patient
        deletePatient: function(patientId) {
            var self = this;
            
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eye_book_delete_patient',
                    nonce: eyeBookAdmin.nonce,
                    patient_id: patientId
                },
                success: function(response) {
                    if (response.success) {
                        self.loadPatients();
                        self.showSuccess('Patient deleted successfully');
                    } else {
                        self.showError(response.data || 'Failed to delete patient');
                    }
                },
                error: function() {
                    self.showError('An error occurred while deleting patient');
                }
            });
        },

        // Apply filters
        applyFilters: function() {
            this.filters.search = $('#patient-search').val();
            this.filters.insurance = $('#patient-insurance-filter').val();
            this.filters.age = $('#patient-age-filter').val();
            this.currentPage = 1;
            this.loadPatients();
        },

        // Clear filters
        clearFilters: function() {
            $('#patient-search').val('');
            $('#patient-insurance-filter').val('');
            $('#patient-age-filter').val('');
            this.filters = { search: '', insurance: '', age: '' };
            this.currentPage = 1;
            this.loadPatients();
        },

        // Sort table
        sortTable: function(column) {
            if (this.sortBy === column) {
                this.sortOrder = this.sortOrder === 'ASC' ? 'DESC' : 'ASC';
            } else {
                this.sortBy = column;
                this.sortOrder = 'ASC';
            }
            this.loadPatients();
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

            $('#patients-pagination').html(html);
            this.bindPaginationEvents();
        },

        // Bind pagination events
        bindPaginationEvents: function() {
            var self = this;

            $('#patients-pagination .prev-page, #patients-pagination .next-page').on('click', function(e) {
                e.preventDefault();
                var page = parseInt($(this).data('page'));
                if (page > 0) {
                    self.currentPage = page;
                    self.loadPatients();
                }
            });

            $('#patients-pagination .current-page').on('change', function() {
                var page = parseInt($(this).val());
                var totalPages = parseInt($('.total-pages').text());
                if (page > 0 && page <= totalPages) {
                    self.currentPage = page;
                    self.loadPatients();
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
            $('.patient-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                alert('Please select patients to perform the action on.');
                return;
            }

            if (action === 'delete' && !confirm('Are you sure you want to delete the selected patients?')) {
                return;
            }

            var self = this;
            $.ajax({
                url: eyeBookAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'eye_book_bulk_action_patients',
                    nonce: eyeBookAdmin.nonce,
                    bulk_action: action,
                    patient_ids: selectedIds
                },
                success: function(response) {
                    if (response.success) {
                        self.loadPatients();
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

        // Export patients
        exportPatients: function() {
            window.location.href = eyeBookAdmin.ajax_url + '?action=eye_book_export_patients&nonce=' + eyeBookAdmin.nonce;
        },

        // Import patients
        importPatients: function() {
            // TODO: Implement import functionality
            alert('Import functionality will be implemented in a future update.');
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