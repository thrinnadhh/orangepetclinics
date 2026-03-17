<?php
/**
 * Plugin Name: Orange Pet Clinic - Admin Panel (V6.5)
 * Description: Fully decoupled Admin Dashboard for Dual-Calendars, Calendar Blocking & Unblocking, Bulk WooCommerce Inventory Manager, Dynamic Services Pricing, Detailed Appointments, and Data Flushing. (Payment: Pay at Clinic Only)
 * Version: 6.5
 */

if (!defined('ABSPATH')) {
    exit;
}

// ─────────────────────────────────────────────────────────────
// 0. DEFAULT PRICING STRUCTURE
// ─────────────────────────────────────────────────────────────
function opc_v5_get_default_prices() {
    return array(
        'doctors' => array(
            "Dr. Malli babu" => 500,
            "Dr. Jane Smith" => 800,
            "Dr. Alan Walker" => 600
        ),
        'grooming' => array(
            "Normal Bathing" => array("Small" => 600, "Medium" => 700, "Large" => 800, "Puppy" => 400),
            "Medicated Bathing" => array("Small" => 700, "Medium" => 800, "Large" => 900, "Puppy" => 500),
            "Tick Bathing" => array("Small" => 800, "Medium" => 900, "Large" => 1000, "Puppy" => null),
            "Grooming Bathing" => array("Small" => 2000, "Medium" => 2000, "Large" => 2600, "Puppy" => 1700),
            "Private Hair Cut" => array("Small" => 250, "Medium" => 350, "Large" => 400, "Puppy" => 200),
            "Zero Hair Cut" => array("Small" => 1400, "Medium" => 1400, "Large" => 2000, "Puppy" => 1000)
        )
    );
}

function opc_v5_get_current_prices() {
    $prices = get_option('opc_service_prices', opc_v5_get_default_prices());
    if (isset($prices['doctors']['Dr. John Doe'])) {
        $prices['doctors']['Dr. Malli babu'] = $prices['doctors']['Dr. John Doe'];
        unset($prices['doctors']['Dr. John Doe']);
        update_option('opc_service_prices', $prices);
    }
    return $prices;
}

// ─────────────────────────────────────────────────────────────
// 1. FRONTEND AJAX ENDPOINTS
// ─────────────────────────────────────────────────────────────

add_action('wp_ajax_get_service_prices', 'opc_v5_get_service_prices');
add_action('wp_ajax_nopriv_get_service_prices', 'opc_v5_get_service_prices');
function opc_v5_get_service_prices() {
    wp_send_json_success(opc_v5_get_current_prices());
    wp_die();
}

add_action('wp_ajax_pet_booking', 'opc_v5_custom_booking_handler');
add_action('wp_ajax_nopriv_pet_booking', 'opc_v5_custom_booking_handler');
function opc_v5_custom_booking_handler() {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) throw new Exception('No data received');

        $admin = "orangepetclinics@gmail.com";
        $user = sanitize_email($data['email'] ?? '');
        $phone = sanitize_text_field($data['phone'] ?? '');
        $type = sanitize_text_field($data['type'] ?? '');
        $category = sanitize_text_field($data['category'] ?? '');
        $breed = sanitize_text_field($data['breed'] ?? '');
        $doctor = sanitize_text_field($data['doctor'] ?? '');

        if (empty($user) || empty($phone)) {
            wp_send_json_error('Missing email or phone');
            return;
        }

        $subject = "New Appointment Booking";
        $name = sanitize_text_field($data['name'] ?? '');
        $date = sanitize_text_field($data['date'] ?? '');
        $time = sanitize_text_field($data['time'] ?? '');
        $payment_method = 'clinic'; // Forced to Pay at Clinic
        $amount = floatval($data['amount'] ?? 0);

        $existing_bookings = get_option('opc_all_bookings', array());
        if (!is_array($existing_bookings)) $existing_bookings = array();

        foreach ($existing_bookings as $eb) {
            if (is_array($eb) && ($eb['date'] ?? '') === $date && ($eb['time'] ?? '') === $time && strtolower(trim($eb['email'] ?? '')) === strtolower(trim($user))) {
                if (($eb['status'] ?? 'active') !== 'cancelled') {
                    throw new Exception('You already have an active appointment scheduled for this date and time.');
                }
            }
        }

        $message = '
        <div style="background:#f5f7fb;padding:30px;font-family:Arial,sans-serif">
          <div style="max-width:600px;margin:auto;background:#ffffff;border:1px solid #e5e5e5;border-radius:8px">
            <div style="background:#ff7a00;padding:20px;text-align:center;color:#ffffff;font-size:22px;font-weight:bold">Orange Pet Clinic</div>
            <div style="padding:25px">
              <h3>New Appointment Booking</h3>
              <p><strong>Name:</strong> ' . esc_html($name) . '</p>
              <p><strong>Phone:</strong> ' . esc_html($phone) . '</p>
              <p><strong>Date:</strong> ' . esc_html($date) . '</p>
              <p><strong>Time:</strong> ' . esc_html($time) . '</p>
              <p><strong>Payment Method:</strong> Pay at Clinic</p>
            </div>
          </div>
        </div>';

        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($admin, $subject, $message, $headers);
        wp_mail($user, "Appointment Confirmed", $message, $headers);

        if (!empty($date) && !empty($time) && !empty($phone)) {
            $booked_option_key = ($type === 'doctor') ? 'opc_booked_slots_doctor' : 'opc_booked_slots_grooming';
            $slots = get_option($booked_option_key, array());
            if (!isset($slots[$date])) $slots[$date] = array();
            if (!in_array($time, $slots[$date])) {
                $slots[$date][] = $time;
                update_option($booked_option_key, $slots);
            }

            $bookings = get_option('opc_all_bookings', array());
            $booking_id = uniqid('bkg_');
            $booking_data = array(
                'id' => $booking_id,
                'date' => $date,
                'time' => $time,
                'phone' => $phone,
                'email' => $user,
                'status' => 'active',
                'name' => $name,
                'type' => $type,
                'category' => $category,
                'breed' => $breed,
                'doctor' => $doctor,
                'payment_method' => $payment_method,
                'amount' => $amount,
                'payment_status' => 'unpaid' 
            );
            $bookings[$booking_id] = $booking_data;
            update_option('opc_all_bookings', $bookings);

            $history = get_option('opc_booking_history', array());
            $booking_data['timestamp'] = current_time('timestamp');
            $history[] = $booking_data;
            update_option('opc_booking_history', $history);
        }

        wp_send_json_success(array("message" => "Booking saved! Check-in at clinic."));
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}

add_action('wp_ajax_get_booked_slots', 'opc_v5_get_booked_slots_renamed');
add_action('wp_ajax_nopriv_get_booked_slots', 'opc_v5_get_booked_slots_renamed');
function opc_v5_get_booked_slots_renamed() {
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'grooming';
    if (empty($date)) wp_send_json_error('No date provided');

    $booked_option_key = ($type === 'doctor') ? 'opc_booked_slots_doctor' : 'opc_booked_slots_grooming';
    $all_booked_slots = get_option($booked_option_key, array());
    $legacy_booked_slots = get_option('opc_booked_slots', array());

    $booked_times = array_merge($all_booked_slots[$date] ?? array(), $legacy_booked_slots[$date] ?? array());

    if (in_array("ALL_DAY", $booked_times)) {
        wp_send_json_success(array("10:00 AM", "11:00 AM", "12:00 PM", "1:00 PM", "2:00 PM", "3:00 PM", "4:00 PM", "5:00 PM", "6:00 PM", "7:00 PM", "8:00 PM"));
    } else {
        wp_send_json_success($booked_times);
    }
    wp_die();
}

add_action('wp_ajax_fetch_booking_by_phone', 'opc_v5_fetch_booking_renamed');
add_action('wp_ajax_nopriv_fetch_booking_by_phone', 'opc_v5_fetch_booking_renamed');
function opc_v5_fetch_booking_renamed() {
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $all_bookings = get_option('opc_all_bookings', array());
    $found = null;
    foreach ($all_bookings as $key => $b) {
        $b_phone = $b['phone'] ?? $key;
        if ($b_phone === $phone && in_array($b['status'] ?? 'active', ['active', 'rescheduled'])) {
            if (!$found || strtotime($b['date'] . ' ' . $b['time']) > strtotime($found['date'] . ' ' . $found['time'])) {
                $found = $b;
            }
        }
    }
    if ($found) wp_send_json_success($found);
    else wp_send_json_error('No booking found');
    wp_die();
}

add_action('wp_ajax_reschedule_booking', 'opc_v5_reschedule_booking_renamed');
add_action('wp_ajax_nopriv_reschedule_booking', 'opc_v5_reschedule_booking_renamed');
function opc_v5_reschedule_booking_renamed() {
    $data = json_decode(file_get_contents('php://input'), true);
    $phone = sanitize_text_field($data['phone'] ?? '');
    $new_date = sanitize_text_field($data['new_date'] ?? '');
    $new_time = sanitize_text_field($data['new_time'] ?? '');

    if (empty($phone) || empty($new_date) || empty($new_time)) {
        wp_send_json_error('Missing data for rescheduling.');
        wp_die();
    }

    $all_bookings = get_option('opc_all_bookings', array());
    $booking_id_to_edit = null;
    foreach ($all_bookings as $key => $b) {
        $b_phone = $b['phone'] ?? $key;
        if ($b_phone === $phone && in_array($b['status'] ?? 'active', ['active', 'rescheduled'])) {
            $booking_id_to_edit = $key;
            break;
        }
    }
    if (!$booking_id_to_edit) { wp_send_json_error('Booking not found.'); wp_die(); }

    $booking = $all_bookings[$booking_id_to_edit];
    $diff_hours = (strtotime("{$booking['date']} " . date("H:i", strtotime($booking['time']))) - current_time('timestamp')) / 3600;
    if ($diff_hours < 1.5) { wp_send_json_error('Too late to reschedule.'); wp_die(); }

    $booked_option_key = (($booking['type'] ?? 'grooming') === 'doctor') ? 'opc_booked_slots_doctor' : 'opc_booked_slots_grooming';
    $all_booked_slots = get_option($booked_option_key, array());
    if (isset($all_booked_slots[$booking['date']])) $all_booked_slots[$booking['date']] = array_diff($all_booked_slots[$booking['date']], [$booking['time']]);
    if (!isset($all_booked_slots[$new_date])) $all_booked_slots[$new_date] = array();
    $all_booked_slots[$new_date][] = $new_time;
    update_option($booked_option_key, $all_booked_slots);

    $all_bookings[$booking_id_to_edit]['date'] = $new_date;
    $all_bookings[$booking_id_to_edit]['time'] = $new_time;
    $all_bookings[$booking_id_to_edit]['status'] = 'rescheduled';
    update_option('opc_all_bookings', $all_bookings);

    $history = get_option('opc_booking_history', array());
    foreach ($history as $key => $h) {
        if (($h['phone'] ?? '') === $phone && ($h['status'] ?? '') !== 'rescheduled') {
            $history[$key]['status'] = 'rescheduled';
            $history[$key]['date'] = $new_date;
            $history[$key]['time'] = $new_time;
        }
    }
    update_option('opc_booking_history', $history);
    wp_send_json_success("Successfully rescheduled.");
    wp_die();
}

// ─────────────────────────────────────────────────────────────
// 2. ADMIN DASHBOARD
// ─────────────────────────────────────────────────────────────
add_action('admin_menu', 'opc_v5_register_admin_page');
function opc_v5_register_admin_page() {
    add_menu_page('Orange Pet Admin', 'Orange Pet Admin', 'manage_options', 'opc-admin-dashboard', 'opc_v5_render_admin_dashboard_safe', 'dashicons-calendar-alt', 30);
}

function opc_v5_render_admin_dashboard_safe() {
    if (!current_user_can('manage_options')) wp_die('No access');
    $message = '';
    $wc_active = class_exists('WooCommerce');
    $wc_products = array();

    // Block Calendar
    if ($_POST && isset($_POST['opc_action']) && $_POST['opc_action'] === 'block_calendar') {
        if (!empty($_POST['block_date'])) {
            $date = sanitize_text_field($_POST['block_date']);
            $block_type = sanitize_text_field($_POST['block_type'] ?? 'both');
            $keys = array();
            if ($block_type === 'doctor' || $block_type === 'both') $keys[] = 'opc_booked_slots_doctor';
            if ($block_type === 'grooming' || $block_type === 'both') $keys[] = 'opc_booked_slots_grooming';
            if ($block_type === 'both') $keys[] = 'opc_booked_slots';
            foreach ($keys as $k) {
                $slots = get_option($k, array());
                if (!isset($slots[$date])) $slots[$date] = array();
                if (!empty($_POST['block_all_day'])) $slots[$date][] = "ALL_DAY";
                elseif (!empty($_POST['block_time'])) {
                    $t = sanitize_text_field($_POST['block_time']);
                    if (!in_array($t, $slots[$date])) $slots[$date][] = $t;
                }
                update_option($k, $slots);
            }
            $message = '<div class="notice notice-success"><p>✅ Calendar blocked!</p></div>';
        }
    }

    // Unblock Calendar
    if ($_POST && isset($_POST['opc_action']) && $_POST['opc_action'] === 'unblock_calendar') {
        if (!empty($_POST['unblock_date'])) {
            $date = sanitize_text_field($_POST['unblock_date']);
            $block_type = sanitize_text_field($_POST['unblock_type'] ?? 'both');
            $keys = array();
            if ($block_type === 'doctor' || $block_type === 'both') $keys[] = 'opc_booked_slots_doctor';
            if ($block_type === 'grooming' || $block_type === 'both') $keys[] = 'opc_booked_slots_grooming';
            if ($block_type === 'both') $keys[] = 'opc_booked_slots';
            $t_un = !empty($_POST['unblock_all_day']) ? "ALL_DAY" : sanitize_text_field($_POST['unblock_time'] ?? '');
            if ($t_un !== '') {
                foreach ($keys as $k) {
                    $slots = get_option($k, array());
                    if (isset($slots[$date])) {
                        $slots[$date] = array_diff($slots[$date], [$t_un]);
                        if (empty($slots[$date])) unset($slots[$date]);
                        update_option($k, $slots);
                    }
                }
                $message = '<div class="notice notice-success"><p>✅ Reverted calendar block!</p></div>';
            }
        }
    }

    // Bulk Inventory
    if ($wc_active && $_POST && isset($_POST['opc_action']) && $_POST['opc_action'] === 'update_inventory_bulk') {
        if (isset($_POST['bulk_inventory']) && is_array($_POST['bulk_inventory'])) {
            $up_c = 0;
            foreach ($_POST['bulk_inventory'] as $pid => $data) {
                $p = wc_get_product(intval($pid));
                if ($p) {
                    $ch = false;
                    if (isset($data['qty']) && $data['qty'] !== '') {
                        $p->set_stock_quantity(intval($data['qty']));
                        $p->set_stock_status(intval($data['qty']) > 0 ? 'instock' : 'outofstock');
                        $ch = true;
                    }
                    if (isset($data['price']) && $data['price'] !== '') {
                        $p->set_regular_price(wc_format_decimal($data['price']));
                        $ch = true;
                    }
                    if ($ch) { $p->save(); $up_c++; }
                }
            }
            $message = '<div class="notice notice-success"><p>✅ Updated ' . $up_c . ' records!</p></div>';
        }
    }

    // Admin Operations: Reschedule, Complete, Cancel
    if ($_POST && isset($_POST['opc_action'])) {
        $act = $_POST['opc_action'];
        $all_bookings = get_option('opc_all_bookings', array());
        $history = get_option('opc_booking_history', array());

        if ($act === 'admin_reschedule') {
            $id = sanitize_text_field($_POST['resch_id'] ?? '');
            if (isset($all_bookings[$id])) {
                $b = &$all_bookings[$id];
                $old_d = $b['date']; $old_t = $b['time'];
                $new_d = sanitize_text_field($_POST['resch_date']); $new_t = sanitize_text_field($_POST['resch_time']);
                $k = (($b['type'] ?? 'grooming') === 'doctor') ? 'opc_booked_slots_doctor' : 'opc_booked_slots_grooming';
                $slots = get_option($k, array());
                if(isset($slots[$old_d])) $slots[$old_d] = array_diff($slots[$old_d], [$old_t]);
                $slots[$new_d][] = $new_t;
                update_option($k, $slots);
                $b['date'] = $new_d; $b['time'] = $new_t; $b['status'] = 'rescheduled';
                update_option('opc_all_bookings', $all_bookings);
                foreach($history as &$h) if(($h['id'] ?? '') === $id) { $h['status'] = 'rescheduled'; $h['date'] = $new_d; $h['time'] = $new_t; }
                update_option('opc_booking_history', $history);
                $message = '<div class="notice notice-success"><p>✅ Rescheduled!</p></div>';
            }
        } elseif ($act === 'admin_complete') {
            $id = sanitize_text_field($_POST['complete_id'] ?? '');
            if (isset($all_bookings[$id])) {
                $all_bookings[$id]['status'] = 'completed';
                $all_bookings[$id]['payment_status'] = 'completed';
                update_option('opc_all_bookings', $all_bookings);
                foreach($history as &$h) if(($h['id'] ?? '') === $id) { $h['status'] = 'completed'; $h['payment_status'] = 'completed'; }
                update_option('opc_booking_history', $history);
                $message = '<div class="notice notice-success"><p>✅ Completed!</p></div>';
            }
        } elseif ($act === 'admin_cancel') {
            $id = sanitize_text_field($_POST['cancel_id'] ?? '');
            if (isset($all_bookings[$id])) {
                $all_bookings[$id]['status'] = 'cancelled';
                update_option('opc_all_bookings', $all_bookings);
                foreach($history as &$h) if(($h['id'] ?? '') === $id) $h['status'] = 'cancelled';
                update_option('opc_booking_history', $history);
                $message = '<div class="notice notice-warning"><p>🚫 Cancelled.</p></div>';
            }
        } elseif ($act === 'update_pricing') {
            $prices = opc_v5_get_current_prices();
            if (isset($_POST['doctors'])) foreach($_POST['doctors'] as $k => $v) $prices['doctors'][base64_decode($k)] = intval($v);
            if (isset($_POST['grooming'])) foreach($_POST['grooming'] as $ck => $szs) foreach($szs as $sk => $v) $prices['grooming'][base64_decode($ck)][$sk] = ($v===''||$v<0) ? null : intval($v);
            update_option('opc_service_prices', $prices);
            $message = '<div class="notice notice-success"><p>✅ Prices updated!</p></div>';
        } elseif ($act === 'flush_bookings') {
            delete_option('opc_all_bookings'); delete_option('opc_booking_history'); delete_option('opc_booked_slots');
            delete_option('opc_booked_slots_doctor'); delete_option('opc_booked_slots_grooming');
            $message = '<div class="notice notice-success"><p>🚨 Data flushed!</p></div>';
        }
    }

    if ($wc_active) {
        $wc_products = wc_get_products(array('limit' => -1, 'status' => 'publish', 'type' => array('simple', 'variation'), 'orderby' => 'name', 'order' => 'ASC'));
    }

    $times = array("10:00 AM", "11:00 AM", "12:00 PM", "1:00 PM", "2:00 PM", "3:00 PM", "4:00 PM", "5:00 PM", "6:00 PM", "7:00 PM", "8:00 PM");
    $all_bookings = get_option('opc_all_bookings', array());
    $today_str = wp_date('Y-m-d');
    $filter_date = isset($_POST['view_date']) ? sanitize_text_field($_POST['view_date']) : '';

    $groups = array('today'=>array(), 'active'=>array(), 'rescheduled'=>array(), 'completed'=>array(), 'cancelled'=>array());
    foreach ($all_bookings as $b) {
        $s = $b['status'] ?? 'active';
        $d = $b['date'] ?? '';
        if ((!$filter_date && $d===$today_str) || ($filter_date && $d===$filter_date)) if($s!=='cancelled') $groups['today'][] = $b;
        if ($s==='active') $groups['active'][] = $b;
        elseif ($s==='rescheduled') $groups['rescheduled'][] = $b;
        elseif ($s==='completed') $groups['completed'][] = $b;
        elseif ($s==='cancelled') $groups['cancelled'][] = $b;
    }

    $prices = opc_v5_get_current_prices();
    ?>
    <div class="wrap">
        <h1>Orange Pet Clinic Admin (V6.5)</h1>
        <?php echo $message; ?>
        <div style="display:flex; gap:20px; flex-wrap:wrap;">
            <!-- Block/Unblock -->
            <div style="flex:1; background:white; padding:20px; border-radius:8px;">
                <h3>📅 Block Calendar</h3>
                <form method="POST"><input type="hidden" name="opc_action" value="block_calendar">
                    <input type="date" name="block_date" required style="width:100%; margin-bottom:10px;">
                    <select name="block_type" style="width:100%; margin-bottom:10px;"><option value="both">Both</option><option value="doctor">Doctor</option><option value="grooming">Grooming</option></select>
                    <label><input type="checkbox" name="block_all_day"> All Day</label><br>
                    <select name="block_time" style="width:100%; margin-top:10px;"><option value="">Specific Time...</option><?php foreach($times as $t) echo "<option value='$t'>$t</option>"; ?></select>
                    <button type="submit" class="button button-primary" style="width:100%; margin-top:10px;">Block</button>
                </form>
            </div>
            <!-- Inventory -->
            <div style="flex:2; background:white; padding:20px; border-radius:8px;">
                <h3>📦 Inventory</h3>
                <form method="POST"><input type="hidden" name="opc_action" value="update_inventory_bulk">
                    <div style="max-height:200px; overflow:auto;">
                        <table class="wp-list-table widefat striped">
                            <?php foreach($wc_products as $p): ?>
                                <tr><td><?php echo $p->get_name(); ?></td>
                                <td><input type="number" name="bulk_inventory[<?php echo $p->get_id(); ?>][qty]" value="<?php echo $p->get_stock_quantity(); ?>" style="size:4;"></td></tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <button type="submit" class="button">Save Inventory</button>
                </form>
            </div>
        </div>

        <div style="margin-top:20px; background:white; padding:20px; border-radius:8px;">
            <h3>💰 Service Pricing</h3>
            <form method="POST"><input type="hidden" name="opc_action" value="update_pricing">
                <?php foreach($prices['doctors'] as $d => $f): ?>
                    <label><?php echo $d; ?></label> <input type="number" name="doctors[<?php echo base64_encode($d); ?>]" value="<?php echo $f; ?>">
                <?php endforeach; ?>
                <button type="submit" class="button">Update Fees</button>
            </form>
        </div>

        <h2 style="margin-top:30px;">Appointments</h2>
        <form method="POST"><input type="date" name="view_date" value="<?php echo $filter_date ?: $today_str; ?>"><button type="submit" class="button">Filter</button></form>
        
        <?php foreach(array('today'=>'Today', 'active'=>'Active', 'rescheduled'=>'Rescheduled', 'completed'=>'Completed', 'cancelled'=>'Cancelled') as $gk => $gt): ?>
            <h3><?php echo $gt; ?> Appointments</h3>
            <table class="wp-list-table widefat striped">
                <thead><tr><th>Customer</th><th>Service</th><th>Date/Time</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach($groups[$gk] as $b): ?>
                        <tr>
                            <td><?php echo esc_html($b['name']); ?><br><small><?php echo esc_html($b['phone']); ?></small></td>
                            <td><?php echo esc_html(($b['type']=='doctor' ? $b['doctor'] : $b['category'])); ?></td>
                            <td><?php echo $b['date']; ?> @ <?php echo $b['time']; ?></td>
                            <td>
                                <?php if($gk!=='completed' && $gk!=='cancelled'): ?>
                                    <form method="POST" style="display:inline;"><input type="hidden" name="opc_action" value="admin_complete"><input type="hidden" name="complete_id" value="<?php echo $b['id']; ?>"><button type="submit" class="button">Done</button></form>
                                    <form method="POST" style="display:inline;"><input type="hidden" name="opc_action" value="admin_cancel"><input type="hidden" name="cancel_id" value="<?php echo $b['id']; ?>"><button type="submit" class="button">Cancel</button></form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>

        <div style="margin-top:40px; padding:20px; background:#fee; border-radius:8px;">
            <h3>🚨 Danger Zone</h3>
            <form method="POST"><input type="hidden" name="opc_action" value="flush_bookings"><button type="submit" class="button">Flush All Data</button></form>
        </div>
    </div>
    <?php
}

// ─────────────────────────────────────────────────────────────
// 3. WOOCOMMERCE MY ACCOUNT - MY SERVICES TAB
// ─────────────────────────────────────────────────────────────
add_action('init', 'opc_add_services_endpoint');
function opc_add_services_endpoint() { add_rewrite_endpoint('my-services', EP_ROOT | EP_PAGES); }
add_filter('query_vars', function($v){ $v[]='my-services'; return $v; }, 0);
add_filter('woocommerce_account_menu_items', function($i){ $n=array(); foreach($i as $k=>$v){ $n[$k]=$v; if($k==='orders') $n['my-services']='My Services'; } return $n; });
add_action('woocommerce_account_my-services_endpoint', 'opc_services_endpoint_content');
function opc_services_endpoint_content() {
    $u = wp_get_current_user(); if(!$u->exists()) return;
    if($_POST && isset($_POST['opc_frontend_action']) && $_POST['opc_frontend_action']==='cancel_appointment') {
        $id = sanitize_text_field($_POST['cancel_id']);
        $all = get_option('opc_all_bookings', array()); if(isset($all[$id])) { $all[$id]['status']='cancelled'; update_option('opc_all_bookings', $all); }
        $his = get_option('opc_booking_history', array()); foreach($his as &$h) if(($h['id']??'')===$id) $h['status']='cancelled'; update_option('opc_booking_history', $his);
        echo '<div class="woocommerce-message">Cancelled.</div>';
    }
    $em = $u->user_email; $ph = preg_replace('/[^0-9]/','',get_user_meta($u->ID,'billing_phone',true));
    $his = get_option('opc_booking_history', array()); $leg = get_option('opc_all_bookings', array());
    $usr = array();
    $chk = function($b) use ($em, $ph, &$usr) {
        if((($b['email']??'')===$em || preg_replace('/[^0-9]/','',$b['phone']??'')===$ph) && strtotime($b['date']??'') > strtotime('-6 months')) $usr[$b['id']??uniqid()] = $b;
    };
    if(is_array($his)) foreach($his as $b) $chk($b);
    if(is_array($leg)) foreach($leg as $b) $chk($b);
    echo '<h3>My Appointments</h3>';
    if(empty($usr)) echo 'No bookings.';
    else {
        echo '<table class="shop_table"><thead><tr><th>Service</th><th>Date</th><th>Status</th><th>Action</th></tr></thead><tbody>';
        foreach($usr as $b) {
            echo "<tr><td>".($b['type']=='doctor'?'Doctor':'Grooming')."</td><td>{$b['date']} @ {$b['time']}</td><td>{$b['status']}</td><td>";
            if(in_array($b['status'],['active','rescheduled'])) echo "<form method='POST'><input type='hidden' name='opc_frontend_action' value='cancel_appointment'><input type='hidden' name='cancel_id' value='{$b['id']}'><button type='submit' class='button'>Cancel</button></form>";
            echo "</td></tr>";
        }
        echo "</tbody></table>";
    }
}
add_action('init', function () { if(!get_option('opc_services_endpoint_flushed_v65')){ flush_rewrite_rules(); update_option('opc_services_endpoint_flushed_v65', 'yes'); } });
