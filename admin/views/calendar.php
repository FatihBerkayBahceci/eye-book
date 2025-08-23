<?php
/**
 * Calendar Admin View
 *
 * @package EyeBook
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('eye_book_manage_appointments')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'eye-book'));
}

$appointment = new Eye_Book_Appointment();
$provider = new Eye_Book_Provider();
$location = new Eye_Book_Location();

// Get filter parameters
$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_provider = $_GET['provider'] ?? '';
$selected_location = $_GET['location'] ?? '';
$view = $_GET['view'] ?? 'week';

// Get available providers and locations
$providers = $provider->get_all();
$locations = $location->get_all();

// Get appointments for the selected period
$start_date = $selected_date;
$end_date = $selected_date;

if ($view === 'week') {
    $start_date = date('Y-m-d', strtotime('monday this week', strtotime($selected_date)));
    $end_date = date('Y-m-d', strtotime('sunday this week', strtotime($selected_date)));
} elseif ($view === 'month') {
    $start_date = date('Y-m-01', strtotime($selected_date));
    $end_date = date('Y-m-t', strtotime($selected_date));
}

$appointments_data = $appointment->get_by_date_range($start_date, $end_date, array(
    'provider_id' => $selected_provider,
    'location_id' => $selected_location
));

// Group appointments by date and time
$grouped_appointments = array();
foreach ($appointments_data as $apt) {
    $date = $apt->appointment_date;
    $time = $apt->appointment_time;
    if (!isset($grouped_appointments[$date])) {
        $grouped_appointments[$date] = array();
    }
    if (!isset($grouped_appointments[$date][$time])) {
        $grouped_appointments[$date][$time] = array();
    }
    $grouped_appointments[$date][$time][] = $apt;
}
?>

<div class="wrap">
    <h1><?php _e('Calendar', 'eye-book'); ?></h1>
    
    <!-- Calendar Filters -->
    <div class="eye-book-calendar-filters">
        <form method="get" class="eye-book-filter-form">
            <input type="hidden" name="page" value="eye-book-calendar">
            
            <select name="view" onchange="this.form.submit()">
                <option value="day" <?php selected($view, 'day'); ?>><?php _e('Day', 'eye-book'); ?></option>
                <option value="week" <?php selected($view, 'week'); ?>><?php _e('Week', 'eye-book'); ?></option>
                <option value="month" <?php selected($view, 'month'); ?>><?php _e('Month', 'eye-book'); ?></option>
            </select>
            
            <input type="date" name="date" value="<?php echo esc_attr($selected_date); ?>" onchange="this.form.submit()">
            
            <select name="provider" onchange="this.form.submit()">
                <option value=""><?php _e('All Providers', 'eye-book'); ?></option>
                <?php foreach ($providers as $prov): ?>
                    <option value="<?php echo $prov->id; ?>" <?php selected($selected_provider, $prov->id); ?>>
                        <?php echo esc_html($prov->first_name . ' ' . $prov->last_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="location" onchange="this.form.submit()">
                <option value=""><?php _e('All Locations', 'eye-book'); ?></option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?php echo $loc->id; ?>" <?php selected($selected_location, $loc->id); ?>>
                        <?php echo esc_html($loc->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <a href="?page=eye-book-appointments&action=create" class="button button-primary">
                <?php _e('Add Appointment', 'eye-book'); ?>
            </a>
        </form>
    </div>
    
    <!-- Calendar Navigation -->
    <div class="eye-book-calendar-navigation">
        <?php
        $prev_date = date('Y-m-d', strtotime('-1 ' . $view, strtotime($selected_date)));
        $next_date = date('Y-m-d', strtotime('+1 ' . $view, strtotime($selected_date)));
        ?>
        <a href="?page=eye-book-calendar&view=<?php echo $view; ?>&date=<?php echo $prev_date; ?>&provider=<?php echo $selected_provider; ?>&location=<?php echo $selected_location; ?>" 
           class="button button-secondary">&laquo; <?php _e('Previous', 'eye-book'); ?></a>
        
        <span class="eye-book-current-period">
            <?php
            if ($view === 'day') {
                echo date_i18n('l, F j, Y', strtotime($selected_date));
            } elseif ($view === 'week') {
                echo date_i18n('F j', strtotime($start_date)) . ' - ' . date_i18n('F j, Y', strtotime($end_date));
            } elseif ($view === 'month') {
                echo date_i18n('F Y', strtotime($selected_date));
            }
            ?>
        </span>
        
        <a href="?page=eye-book-calendar&view=<?php echo $view; ?>&date=<?php echo $next_date; ?>&provider=<?php echo $selected_provider; ?>&location=<?php echo $selected_location; ?>" 
           class="button button-secondary"><?php _e('Next', 'eye-book'); ?> &raquo;</a>
        
        <a href="?page=eye-book-calendar&view=<?php echo $view; ?>&date=<?php echo date('Y-m-d'); ?>&provider=<?php echo $selected_provider; ?>&location=<?php echo $selected_location; ?>" 
           class="button button-secondary"><?php _e('Today', 'eye-book'); ?></a>
    </div>
    
    <!-- Calendar View -->
    <div class="eye-book-calendar-container">
        <?php if ($view === 'day'): ?>
            <?php $this->render_day_view($selected_date, $grouped_appointments); ?>
        <?php elseif ($view === 'week'): ?>
            <?php $this->render_week_view($start_date, $end_date, $grouped_appointments); ?>
        <?php elseif ($view === 'month'): ?>
            <?php $this->render_month_view($start_date, $end_date, $grouped_appointments); ?>
        <?php endif; ?>
    </div>
</div>

<style>
.eye-book-calendar-filters {
    background: #fff;
    padding: 15px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.eye-book-filter-form {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.eye-book-calendar-navigation {
    text-align: center;
    margin: 20px 0;
}

.eye-book-current-period {
    display: inline-block;
    margin: 0 20px;
    font-size: 18px;
    font-weight: bold;
}

.eye-book-calendar-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    overflow: hidden;
}

.eye-book-day-view {
    display: grid;
    grid-template-columns: 100px 1fr;
}

.eye-book-time-slot {
    border-bottom: 1px solid #eee;
    padding: 10px;
    min-height: 60px;
}

.eye-book-time-slot:last-child {
    border-bottom: none;
}

.eye-book-time-label {
    background: #f9f9f9;
    padding: 10px;
    text-align: center;
    font-weight: bold;
    border-right: 1px solid #eee;
}

.eye-book-appointment {
    background: #007cba;
    color: white;
    padding: 5px 10px;
    margin: 2px 0;
    border-radius: 3px;
    cursor: pointer;
    font-size: 12px;
}

.eye-book-appointment:hover {
    background: #005a87;
}

.eye-book-week-view {
    display: grid;
    grid-template-columns: 100px repeat(7, 1fr);
}

.eye-book-week-header {
    background: #f9f9f9;
    padding: 10px;
    text-align: center;
    font-weight: bold;
    border-bottom: 1px solid #eee;
}

.eye-book-month-view {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
}

.eye-book-month-header {
    background: #f9f9f9;
    padding: 10px;
    text-align: center;
    font-weight: bold;
    border-bottom: 1px solid #eee;
}

.eye-book-month-day {
    border: 1px solid #eee;
    min-height: 100px;
    padding: 5px;
}

.eye-book-month-day.other-month {
    background: #f9f9f9;
    color: #999;
}

.eye-book-month-day.today {
    background: #e7f3ff;
}

.eye-book-day-number {
    font-weight: bold;
    margin-bottom: 5px;
}
</style>

<?php
/**
 * Render day view
 *
 * @param string $date
 * @param array $grouped_appointments
 */
function render_day_view($date, $grouped_appointments) {
    $day_appointments = $grouped_appointments[$date] ?? array();
    ?>
    <div class="eye-book-day-view">
        <div class="eye-book-time-label"><?php _e('Time', 'eye-book'); ?></div>
        <div class="eye-book-time-label"><?php _e('Appointments', 'eye-book'); ?></div>
        
        <?php
        $start_hour = 8; // 8 AM
        $end_hour = 18; // 6 PM
        
        for ($hour = $start_hour; $hour <= $end_hour; $hour++) {
            $time_slot = sprintf('%02d:00', $hour);
            $appointments = $day_appointments[$time_slot] ?? array();
            ?>
            <div class="eye-book-time-slot">
                <strong><?php echo date_i18n('g:i A', strtotime($time_slot)); ?></strong>
            </div>
            <div class="eye-book-time-slot">
                <?php foreach ($appointments as $apt): ?>
                    <div class="eye-book-appointment" 
                         onclick="window.location.href='?page=eye-book-appointments&action=edit&id=<?php echo $apt->id; ?>'">
                        <strong><?php echo esc_html($apt->patient_first_name . ' ' . $apt->patient_last_name); ?></strong><br>
                        <?php echo esc_html($apt->appointment_type_name); ?><br>
                        <small><?php echo esc_html($apt->provider_name); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php
        }
        ?>
    </div>
    <?php
}

/**
 * Render week view
 *
 * @param string $start_date
 * @param string $end_date
 * @param array $grouped_appointments
 */
function render_week_view($start_date, $end_date, $grouped_appointments) {
    ?>
    <div class="eye-book-week-view">
        <div class="eye-book-week-header"><?php _e('Time', 'eye-book'); ?></div>
        <?php
        $current_date = $start_date;
        while (strtotime($current_date) <= strtotime($end_date)) {
            $day_name = date_i18n('D', strtotime($current_date));
            $day_number = date_i18n('j', strtotime($current_date));
            $is_today = $current_date === date('Y-m-d');
            $class = $is_today ? 'eye-book-week-header today' : 'eye-book-week-header';
            ?>
            <div class="<?php echo $class; ?>">
                <?php echo $day_name; ?><br>
                <small><?php echo $day_number; ?></small>
            </div>
            <?php
            $current_date = date('Y-m-d', strtotime('+1 day', strtotime($current_date)));
        }
        ?>
        
        <?php
        $start_hour = 8;
        $end_hour = 18;
        
        for ($hour = $start_hour; $hour <= $end_hour; $hour++) {
            $time_slot = sprintf('%02d:00', $hour);
            ?>
            <div class="eye-book-time-slot">
                <strong><?php echo date_i18n('g:i A', strtotime($time_slot)); ?></strong>
            </div>
            
            <?php
            $current_date = $start_date;
            while (strtotime($current_date) <= strtotime($end_date)) {
                $appointments = $grouped_appointments[$current_date][$time_slot] ?? array();
                ?>
                <div class="eye-book-time-slot">
                    <?php foreach ($appointments as $apt): ?>
                        <div class="eye-book-appointment" 
                             onclick="window.location.href='?page=eye-book-appointments&action=edit&id=<?php echo $apt->id; ?>'">
                            <strong><?php echo esc_html($apt->patient_first_name . ' ' . $apt->patient_last_name); ?></strong><br>
                            <small><?php echo esc_html($apt->appointment_type_name); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php
                $current_date = date('Y-m-d', strtotime('+1 day', strtotime($current_date)));
            }
            ?>
            <?php
        }
        ?>
    </div>
    <?php
}

/**
 * Render month view
 *
 * @param string $start_date
 * @param string $end_date
 * @param array $grouped_appointments
 */
function render_month_view($start_date, $end_date, $grouped_appointments) {
    $first_day = date('w', strtotime($start_date)); // 0 = Sunday
    $days_in_month = date('t', strtotime($start_date));
    
    // Add previous month days
    $prev_month_days = array();
    if ($first_day > 0) {
        $prev_month_end = date('Y-m-d', strtotime('-1 day', strtotime($start_date)));
        for ($i = $first_day - 1; $i >= 0; $i--) {
            $prev_month_days[] = date('Y-m-d', strtotime("-{$i} days", strtotime($prev_month_end)));
        }
    }
    
    // Add next month days
    $next_month_days = array();
    $total_cells = 42; // 6 rows * 7 days
    $remaining_cells = $total_cells - count($prev_month_days) - $days_in_month;
    if ($remaining_cells > 0) {
        $next_month_start = date('Y-m-d', strtotime('+1 day', strtotime($end_date)));
        for ($i = 0; $i < $remaining_cells; $i++) {
            $next_month_days[] = date('Y-m-d', strtotime("+{$i} days", strtotime($next_month_start)));
        }
    }
    ?>
    
    <div class="eye-book-month-view">
        <div class="eye-book-month-header"><?php _e('Sun', 'eye-book'); ?></div>
        <div class="eye-book-month-header"><?php _e('Mon', 'eye-book'); ?></div>
        <div class="eye-book-month-header"><?php _e('Tue', 'eye-book'); ?></div>
        <div class="eye-book-month-header"><?php _e('Wed', 'eye-book'); ?></div>
        <div class="eye-book-month-header"><?php _e('Thu', 'eye-book'); ?></div>
        <div class="eye-book-month-header"><?php _e('Fri', 'eye-book'); ?></div>
        <div class="eye-book-month-header"><?php _e('Sat', 'eye-book'); ?></div>
        
        <?php
        // Previous month days
        foreach ($prev_month_days as $date) {
            $day_number = date('j', strtotime($date));
            ?>
            <div class="eye-book-month-day other-month">
                <div class="eye-book-day-number"><?php echo $day_number; ?></div>
            </div>
            <?php
        }
        
        // Current month days
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = date('Y-m-d', strtotime("+{$day} days", strtotime($start_date)));
            $is_today = $date === date('Y-m-d');
            $class = $is_today ? 'eye-book-month-day today' : 'eye-book-month-day';
            $appointments = $grouped_appointments[$date] ?? array();
            ?>
            <div class="<?php echo $class; ?>">
                <div class="eye-book-day-number"><?php echo $day; ?></div>
                <?php
                $count = 0;
                foreach ($appointments as $time_slot => $time_appointments) {
                    foreach ($time_appointments as $apt) {
                        if ($count < 3) { // Show max 3 appointments per day
                            ?>
                            <div class="eye-book-appointment" 
                                 onclick="window.location.href='?page=eye-book-appointments&action=edit&id=<?php echo $apt->id; ?>'">
                                <small><?php echo esc_html($apt->patient_first_name . ' ' . $apt->patient_last_name); ?></small>
                            </div>
                            <?php
                            $count++;
                        }
                    }
                }
                if (count($appointments) > 3) {
                    echo '<small>+' . (count($appointments) - 3) . ' more</small>';
                }
                ?>
            </div>
            <?php
        }
        
        // Next month days
        foreach ($next_month_days as $date) {
            $day_number = date('j', strtotime($date));
            ?>
            <div class="eye-book-month-day other-month">
                <div class="eye-book-day-number"><?php echo $day_number; ?></div>
            </div>
            <?php
        }
        ?>
    </div>
    <?php
}
?>
