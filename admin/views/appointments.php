<?php
/**
 * Eye-Book Appointments Management
 *
 * @package EyeBook
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle actions
$action = $_GET['action'] ?? 'list';
$message = '';

if ($_POST && isset($_POST['action'])) {
    if (wp_verify_nonce($_POST['eye_book_appointment_nonce'], 'eye_book_appointment_action')) {
        switch ($_POST['action']) {
            case 'create':
                // TODO: Create appointment logic
                $message = 'Appointment created successfully!';
                break;
            case 'update':
                // TODO: Update appointment logic
                $message = 'Appointment updated successfully!';
                break;
            case 'delete':
                // TODO: Delete appointment logic
                $message = 'Appointment deleted successfully!';
                break;
        }
    }
}

// Get appointments data
$appointments = array(); // TODO: Get from database
?>

<div class="eye-book-admin">
    <!-- Header -->
    <div class="eye-book-header">
        <h1>
            <span class="icon">📅</span>
            Appointments Management
        </h1>
        <p class="subtitle">Schedule, manage, and track all patient appointments efficiently.</p>
    </div>

    <!-- Navigation -->
    <nav class="eye-book-nav">
        <ul>
            <li><a href="?page=eye-book-dashboard">Dashboard</a></li>
            <li><a href="?page=eye-book-appointments" class="active">Appointments</a></li>
            <li><a href="?page=eye-book-patients">Patients</a></li>
            <li><a href="?page=eye-book-providers">Providers</a></li>
            <li><a href="?page=eye-book-locations">Locations</a></li>
            <li><a href="?page=eye-book-calendar">Calendar</a></li>
            <li><a href="?page=eye-book-reports">Reports</a></li>
            <li><a href="?page=eye-book-settings">Settings</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="eye-book-content">
        <?php if ($message): ?>
            <div class="alert alert-success">
                <span class="icon">✅</span>
                <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <!-- Appointments List View -->
            <div class="page-header">
                <h2>
                    <span class="icon">📋</span>
                    All Appointments
                </h2>
                <p class="description">View and manage all scheduled appointments across your practice.</p>
            </div>

            <!-- Search & Filters -->
            <div class="search-filters">
                <div class="filters-row">
                    <div class="form-group">
                        <label for="search">Search Appointments</label>
                        <div class="search-box">
                            <input type="text" id="search" name="search" placeholder="Search by patient name, provider, or notes...">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="in-progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="no-show">No Show</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="provider">Provider</label>
                        <select id="provider" name="provider">
                            <option value="">All Providers</option>
                            <!-- TODO: Populate with actual providers -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_range">Date Range</label>
                        <input type="date" id="date_range" name="date_range">
                    </div>
                    
                    <div class="form-group">
                        <button type="button" class="btn btn-primary">🔍 Search</button>
                        <button type="button" class="btn btn-secondary">🔄 Reset</button>
                    </div>
                </div>
            </div>

            <!-- Appointments Table -->
            <div class="card">
                <div class="card-header">
                    <h3>📅 Appointments List</h3>
                    <a href="?page=eye-book-appointments&action=new" class="btn btn-primary">➕ New Appointment</a>
                </div>
                <div class="card-body">
                    <?php if (empty($appointments)): ?>
                        <div class="text-center p-4">
                            <p class="text-secondary">No appointments found. Create your first appointment to get started.</p>
                            <a href="?page=eye-book-appointments&action=new" class="btn btn-primary mt-2">Create Appointment</a>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="eye-book-table">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Patient</th>
                                        <th>Provider</th>
                                        <th>Type</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc_html($appointment['date']); ?></strong><br>
                                                <small class="text-secondary"><?php echo esc_html($appointment['time']); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo esc_html($appointment['patient_name']); ?></strong><br>
                                                <small class="text-secondary"><?php echo esc_html($appointment['patient_phone']); ?></small>
                                            </td>
                                            <td><?php echo esc_html($appointment['provider_name']); ?></td>
                                            <td><?php echo esc_html($appointment['type']); ?></td>
                                            <td><?php echo esc_html($appointment['location']); ?></td>
                                            <td>
                                                <span class="status <?php echo esc_attr($appointment['status']); ?>">
                                                    <?php echo esc_html(ucfirst($appointment['status'])); ?>
                                                </span>
                                            </td>
                                            <td class="actions">
                                                <a href="?page=eye-book-appointments&action=view&id=<?php echo esc_attr($appointment['id']); ?>" class="btn btn-sm btn-secondary">👁️</a>
                                                <a href="?page=eye-book-appointments&action=edit&id=<?php echo esc_attr($appointment['id']); ?>" class="btn btn-sm btn-primary">✏️</a>
                                                <a href="?page=eye-book-appointments&action=delete&id=<?php echo esc_attr($appointment['id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this appointment?')">🗑️</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($action === 'new' || $action === 'edit'): ?>
            <!-- Appointment Form -->
            <?php
            $is_edit = $action === 'edit';
            $appointment = array(); // TODO: Get appointment data if editing
            
            if ($is_edit) {
                $title = 'Edit Appointment';
                $button_text = 'Update Appointment';
            } else {
                $title = 'New Appointment';
                $button_text = 'Create Appointment';
            }
            ?>

            <div class="page-header">
                <h2>
                    <span class="icon"><?php echo $is_edit ? '✏️' : '➕'; ?></span>
                    <?php echo esc_html($title); ?>
                </h2>
                <p class="description"><?php echo $is_edit ? 'Update appointment details and settings.' : 'Schedule a new appointment for a patient.'; ?></p>
            </div>

            <form method="post" action="" class="eye-book-form">
                <?php wp_nonce_field('eye_book_appointment_action', 'eye_book_appointment_nonce'); ?>
                <input type="hidden" name="action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">
                
                <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr($appointment['id'] ?? ''); ?>">
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="patient_id">Patient <span class="required">*</span></label>
                        <select id="patient_id" name="patient_id" required>
                            <option value="">Select Patient</option>
                            <!-- TODO: Populate with actual patients -->
                        </select>
                        <p class="description">Choose the patient for this appointment.</p>
                    </div>

                    <div class="form-group">
                        <label for="provider_id">Provider <span class="required">*</span></label>
                        <select id="provider_id" name="provider_id" required>
                            <option value="">Select Provider</option>
                            <!-- TODO: Populate with actual providers -->
                        </select>
                        <p class="description">Choose the healthcare provider.</p>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="appointment_date">Date <span class="required">*</span></label>
                        <input type="date" id="appointment_date" name="appointment_date" required 
                               value="<?php echo $is_edit ? esc_attr($appointment['date'] ?? '') : ''; ?>">
                        <p class="description">Select the appointment date.</p>
                    </div>

                    <div class="form-group">
                        <label for="appointment_time">Time <span class="required">*</span></label>
                        <input type="time" id="appointment_time" name="appointment_time" required 
                               value="<?php echo $is_edit ? esc_attr($appointment['time'] ?? '') : ''; ?>">
                        <p class="description">Select the appointment time.</p>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="appointment_type">Appointment Type <span class="required">*</span></label>
                        <select id="appointment_type" name="appointment_type" required>
                            <option value="">Select Type</option>
                            <option value="routine_exam">Routine Eye Exam</option>
                            <option value="consultation">Consultation</option>
                            <option value="follow_up">Follow-up</option>
                            <option value="emergency">Emergency</option>
                            <option value="surgery">Surgery</option>
                        </select>
                        <p class="description">Choose the type of appointment.</p>
                    </div>

                    <div class="form-group">
                        <label for="location_id">Location</label>
                        <select id="location_id" name="location_id">
                            <option value="">Select Location</option>
                            <!-- TODO: Populate with actual locations -->
                        </select>
                        <p class="description">Choose the practice location.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="4" placeholder="Additional notes about the appointment..."><?php echo $is_edit ? esc_textarea($appointment['notes'] ?? '') : ''; ?></textarea>
                    <p class="description">Optional notes or special instructions.</p>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="duration">Duration (minutes)</label>
                        <input type="number" id="duration" name="duration" min="15" max="480" step="15" 
                               value="<?php echo $is_edit ? esc_attr($appointment['duration'] ?? '60') : '60'; ?>">
                        <p class="description">Appointment duration in minutes.</p>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="scheduled" <?php echo ($is_edit && ($appointment['status'] ?? '') === 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                            <option value="confirmed" <?php echo ($is_edit && ($appointment['status'] ?? '') === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="in-progress" <?php echo ($is_edit && ($appointment['status'] ?? '') === 'in-progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo ($is_edit && ($appointment['status'] ?? '') === 'completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo ($is_edit && ($appointment['status'] ?? '') === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="no-show" <?php echo ($is_edit && ($appointment['status'] ?? '') === 'no-show') ? 'selected' : ''; ?>>No Show</option>
                        </select>
                        <p class="description">Current appointment status.</p>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" name="submit" class="btn btn-primary"><?php echo esc_html($button_text); ?></button>
                    <a href="?page=eye-book-appointments" class="btn btn-secondary">Cancel</a>
                </div>
            </form>

        <?php elseif ($action === 'view'): ?>
            <!-- Appointment View -->
            <?php
            $appointment_id = $_GET['id'] ?? 0;
            $appointment = array(); // TODO: Get appointment data
            ?>

            <div class="page-header">
                <h2>
                    <span class="icon">👁️</span>
                    Appointment Details
                </h2>
                <p class="description">View complete appointment information and history.</p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>📅 Appointment #<?php echo esc_html($appointment_id); ?></h3>
                    <div class="d-flex gap-2">
                        <a href="?page=eye-book-appointments&action=edit&id=<?php echo esc_attr($appointment_id); ?>" class="btn btn-primary">✏️ Edit</a>
                        <a href="?page=eye-book-appointments" class="btn btn-secondary">← Back to List</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($appointment)): ?>
                        <div class="text-center p-4">
                            <p class="text-secondary">Appointment not found or access denied.</p>
                        </div>
                    <?php else: ?>
                        <div class="d-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--spacing-lg);">
                            <div>
                                <h4>Patient Information</h4>
                                <p><strong>Name:</strong> <?php echo esc_html($appointment['patient_name'] ?? ''); ?></p>
                                <p><strong>Phone:</strong> <?php echo esc_html($appointment['patient_phone'] ?? ''); ?></p>
                                <p><strong>Email:</strong> <?php echo esc_html($appointment['patient_email'] ?? ''); ?></p>
                            </div>
                            
                            <div>
                                <h4>Appointment Details</h4>
                                <p><strong>Date:</strong> <?php echo esc_html($appointment['date'] ?? ''); ?></p>
                                <p><strong>Time:</strong> <?php echo esc_html($appointment['time'] ?? ''); ?></p>
                                <p><strong>Duration:</strong> <?php echo esc_html($appointment['duration'] ?? ''); ?> minutes</p>
                            </div>
                            
                            <div>
                                <h4>Provider & Location</h4>
                                <p><strong>Provider:</strong> <?php echo esc_html($appointment['provider_name'] ?? ''); ?></p>
                                <p><strong>Location:</strong> <?php echo esc_html($appointment['location'] ?? ''); ?></p>
                                <p><strong>Type:</strong> <?php echo esc_html($appointment['type'] ?? ''); ?></p>
                            </div>
                            
                            <div>
                                <h4>Status & Notes</h4>
                                <p><strong>Status:</strong> 
                                    <span class="status <?php echo esc_attr($appointment['status'] ?? ''); ?>">
                                        <?php echo esc_html(ucfirst($appointment['status'] ?? '')); ?>
                                    </span>
                                </p>
                                <p><strong>Notes:</strong> <?php echo esc_html($appointment['notes'] ?? 'No notes'); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.card, .page-header');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in');
    });
    
    // Form validation
    const form = document.querySelector('.eye-book-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = 'var(--danger-color)';
                    isValid = false;
                } else {
                    field.style.borderColor = 'var(--border-color)';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    }
});
</script>