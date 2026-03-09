<?php
/**
 * Plugin Name: Orange Pet Clinic - Admin Panel (V6.4)
 * Description: Fully decoupled Admin Dashboard for Dual-Calendars, Calendar Blocking & Unblocking, Bulk WooCommerce Inventory Manager, Dynamic Services Pricing, Detailed Appointments, Data Flushing, and WooCommerce My Account Services Tab.
 * Version: 6.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// ─────────────────────────────────────────────────────────────
// 0. DEFAULT PRICING STRUCTURE
// ─────────────────────────────────────────────────────────────
function opc_v5_get_default_prices()
{
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

function opc_v5_get_current_prices()
{
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
function opc_v5_get_service_prices()
{
    wp_send_json_success(opc_v5_get_current_prices());
    wp_die();
}

add_action('wp_ajax_pet_booking', 'opc_v5_custom_booking_handler');
add_action('wp_ajax_nopriv_pet_booking', 'opc_v5_custom_booking_handler');
function opc_v5_custom_booking_handler()
{
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data)
            throw new Exception('No data received');

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
        $payment_method = sanitize_text_field($data['payment'] ?? 'clinic');
        $amount = floatval($data['amount'] ?? 0);

        // Prevent Duplicate Bookings
        $existing_bookings = get_option('opc_all_bookings', array());
        if (!is_array($existing_bookings)) {
            $existing_bookings = array();
        }

        if (!empty($existing_bookings)) {
            foreach ($existing_bookings as $eb) {
                if (is_array($eb) && ($eb['date'] ?? '') === $date && ($eb['time'] ?? '') === $time && strtolower(trim($eb['email'] ?? '')) === strtolower(trim($user))) {
                    if (($eb['status'] ?? 'active') !== 'cancelled') {
                        throw new Exception('You already have an active appointment scheduled for this date and time.');
                    }
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
            </div>
          </div>
        </div>';

        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($admin, $subject, $message, $headers);
        wp_mail($user, "Appointment Confirmed", $message, $headers);

        // Save booking & block independent calendar
        if (!empty($date) && !empty($time) && !empty($phone)) {
            $booked_option_key = ($type === 'doctor') ? 'opc_booked_slots_doctor' : 'opc_booked_slots_grooming';
            $slots = get_option($booked_option_key, array());
            if (!isset($slots[$date]))
                $slots[$date] = array();

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
                'payment_status' => 'pending' // Starts pending, online updates via JS sync
            );
            $bookings[$booking_id] = $booking_data;
            update_option('opc_all_bookings', $bookings);

            // Save to permanent history for the user's dashboard
            $history = get_option('opc_booking_history', array());
            $booking_data['timestamp'] = current_time('timestamp');
            $history[] = $booking_data;
            update_option('opc_booking_history', $history);
        }

        wp_send_json_success(array(
            "message" => "Booking saved!",
            "booking_id" => $booking_id,
            "razorpay_key" => get_option('opc_razorpay_key_id', 'rzp_live_SNY9C0XOxmEpGi')
        ));
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}

add_action('wp_ajax_update_payment_id', 'opc_v5_update_payment_id');
add_action('wp_ajax_nopriv_update_payment_id', 'opc_v5_update_payment_id');
function opc_v5_update_payment_id()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $booking_id = sanitize_text_field($data['booking_id'] ?? '');
    $payment_id = sanitize_text_field($data['payment_id'] ?? '');

    if (empty($booking_id) || empty($payment_id)) {
        wp_send_json_error('Missing booking ID or payment ID.');
        wp_die();
    }

    $all_bookings = get_option('opc_all_bookings', array());
    if (isset($all_bookings[$booking_id])) {
        $all_bookings[$booking_id]['razorpay_payment_id'] = $payment_id;
        $all_bookings[$booking_id]['payment_status'] = 'completed';
        update_option('opc_all_bookings', $all_bookings);
    }

    $history = get_option('opc_booking_history', array());
    foreach ($history as $key => &$h) {
        if (isset($h['id']) && $h['id'] === $booking_id) {
            $h['razorpay_payment_id'] = $payment_id;
            $h['payment_status'] = 'completed';
            break;
        }
    }
    update_option('opc_booking_history', $history);

    wp_send_json_success("Payment ID updated");
    wp_die();
}

add_action('wp_ajax_get_booked_slots', 'opc_v5_get_booked_slots_renamed');
add_action('wp_ajax_nopriv_get_booked_slots', 'opc_v5_get_booked_slots_renamed');
function opc_v5_get_booked_slots_renamed()
{
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'grooming';

    if (empty($date))
        wp_send_json_error('No date provided');

    // Get specific calendar
    $booked_option_key = ($type === 'doctor') ? 'opc_booked_slots_doctor' : 'opc_booked_slots_grooming';
    $all_booked_slots = get_option($booked_option_key, array());

    // Merge with old legacy calendar (for blocked all_day events before we made the split)
    $legacy_booked_slots = get_option('opc_booked_slots', array());

    $booked_times = array_merge(
        $all_booked_slots[$date] ?? array(),
        $legacy_booked_slots[$date] ?? array()
    );

    if (in_array("ALL_DAY", $booked_times)) {
        wp_send_json_success(array("10:00 AM", "11:00 AM", "12:00 PM", "1:00 PM", "2:00 PM", "3:00 PM", "4:00 PM", "5:00 PM", "6:00 PM", "7:00 PM", "8:00 PM"));
    } else {
        wp_send_json_success($booked_times);
    }
    wp_die();
}

add_action('wp_ajax_fetch_booking_by_phone', 'opc_v5_fetch_booking_renamed');
add_action('wp_ajax_nopriv_fetch_booking_by_phone', 'opc_v5_fetch_booking_renamed');
function opc_v5_fetch_booking_renamed()
{
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

    if ($found) {
        wp_send_json_success($found);
    } else {
        wp_send_json_error('No booking found');
    }
    wp_die();
}

add_action('wp_ajax_reschedule_booking', 'opc_v5_reschedule_booking_renamed');
add_action('wp_ajax_nopriv_reschedule_booking', 'opc_v5_reschedule_booking_renamed');
function opc_v5_reschedule_booking_renamed()
{
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

    if (!$booking_id_to_edit) {
        wp_send_json_error('Booking not found.');
        wp_die();
    }

    $booking = $all_bookings[$booking_id_to_edit];
    $old_time_24 = date("H:i", strtotime($booking['time']));
    $diff_hours = (strtotime("{$booking['date']} $old_time_24") - current_time('timestamp')) / 3600;

    if ($diff_hours < 1.5) {
        wp_send_json_error('Changes are not permitted within 1.5 hours of your appointment.');
        wp_die();
    }

    $type = $booking['type'] ?? 'grooming';
    $booked_option_key = ($type === 'doctor') ? 'opc_booked_slots_doctor' : 'opc_booked_slots_grooming';

    $all_booked_slots = get_option($booked_option_key, array());
    if (isset($all_booked_slots[$booking['date']])) {
        $all_booked_slots[$booking['date']] = array_diff($all_booked_slots[$booking['date']], [$booking['time']]);
    }

    if (!isset($all_booked_slots[$new_date]))
        $all_booked_slots[$new_date] = array();
    $all_booked_slots[$new_date][] = $new_time;
    update_option($booked_option_key, $all_booked_slots);

    $all_bookings[$booking_id_to_edit]['date'] = $new_date;
    $all_bookings[$booking_id_to_edit]['time'] = $new_time;
    $all_bookings[$booking_id_to_edit]['status'] = 'rescheduled';
    update_option('opc_all_bookings', $all_bookings);

    // Update in history as well
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
function opc_v5_register_admin_page()
{
    add_menu_page('Orange Pet Admin', 'Orange Pet Admin', 'manage_options', 'opc-admin-dashboard', 'opc_v5_render_admin_dashboard_safe', 'dashicons-calendar-alt', 30);
}

function opc_v5_render_admin_dashboard_safe()
{
    if (!current_user_can('manage_options'))
        wp_die('No access');

    $message = '';
    $wc_active = class_exists('WooCommerce');
    $wc_products = array();

    // Handle Calendar Block
    if ($_POST && isset($_POST['opc_action']) && $_POST['opc_action'] === 'block_calendar') {
        if (!empty($_POST['block_date'])) {
            $date = sanitize_text_field($_POST['block_date']);
            $block_type = sanitize_text_field($_POST['block_type'] ?? 'both');

            $keys = array();
            if ($block_type === 'doctor' || $block_type === 'both')
                $keys[] = 'opc_booked_slots_doctor';
            if ($block_type === 'grooming' || $block_type === 'both')
                $keys[] = 'opc_booked_slots_grooming';

            // Also update legacy variable for ALL_DAY legacy backward comp
            if ($block_type === 'both')
                $keys[] = 'opc_booked_slots';

            foreach ($keys as $booked_option_key) {
                $slots = get_option($booked_option_key, array());
                if (!isset($slots[$date]))
                    $slots[$date] = array();

                if (!empty($_POST['block_all_day'])) {
                    $slots[$date][] = "ALL_DAY";
                } elseif (!empty($_POST['block_time'])) {
                    $time = sanitize_text_field($_POST['block_time']);
                    if (!in_array($time, $slots[$date])) {
                        $slots[$date][] = $time;
                    }
                }
                update_option($booked_option_key, $slots);
            }
            $message = '<div class="notice notice-success"><p>✅ Calendar blocked for ' . esc_html($date) . '!</p></div>';
        }
    }

    // Handle Calendar Unblock
    if ($_POST && isset($_POST['opc_action']) && $_POST['opc_action'] === 'unblock_calendar') {
        if (!empty($_POST['unblock_date'])) {
            $date = sanitize_text_field($_POST['unblock_date']);
            $block_type = sanitize_text_field($_POST['unblock_type'] ?? 'both');

            $keys = array();
            if ($block_type === 'doctor' || $block_type === 'both')
                $keys[] = 'opc_booked_slots_doctor';
            if ($block_type === 'grooming' || $block_type === 'both')
                $keys[] = 'opc_booked_slots_grooming';
            if ($block_type === 'both')
                $keys[] = 'opc_booked_slots'; // legacy

            $time_to_unblock = '';
            if (!empty($_POST['unblock_all_day'])) {
                $time_to_unblock = "ALL_DAY";
            } elseif (!empty($_POST['unblock_time'])) {
                $time_to_unblock = sanitize_text_field($_POST['unblock_time']);
            }

            if ($time_to_unblock !== '') {
                foreach ($keys as $booked_option_key) {
                    $slots = get_option($booked_option_key, array());
                    if (isset($slots[$date])) {
                        $slots[$date] = array_diff($slots[$date], [$time_to_unblock]);
                        $slots[$date] = array_values($slots[$date]);
                        if (empty($slots[$date])) {
                            unset($slots[$date]);
                        }
                        update_option($booked_option_key, $slots);
                    }
                }
                $message = '<div class="notice notice-success"><p>✅ Reverted calendar block for ' . esc_html($date) . '!</p></div>';
            }
        }
    }

    // Handle Bulk Inventory Update (Save All)
    if ($wc_active && $_POST && isset($_POST['opc_action']) && $_POST['opc_action'] === 'update_inventory_bulk') {
        if (isset($_POST['bulk_inventory']) && is_array($_POST['bulk_inventory'])) {
            $update_count = 0;
            foreach ($_POST['bulk_inventory'] as $pid => $data) {
                $product = wc_get_product(intval($pid));
                if ($product) {
                    $changed = false;
                    if (isset($data['qty']) && $data['qty'] !== '') {
                        $qty = intval($data['qty']);
                        $product->set_manage_stock(true);
                        $product->set_stock_quantity($qty);
                        $product->set_stock_status($qty > 0 ? 'instock' : 'outofstock');
                        $changed = true;
                    }
                    if (isset($data['price']) && $data['price'] !== '') {
                        $price = filter_var($data['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                        if ($price !== false) {
                            $product->set_regular_price($price);
                            $product->set_price($price);
                            $changed = true;
                        }
                    }
                    if ($changed) {
                        $product->save();
                        $update_count++;
                    }
                }
            }
            if ($update_count > 0) {
                $message = '<div class="notice notice-success"><p>✅ Successfully updated ' . $update_count . ' products!</p></div>';
            } else {
                $message = '<div class="notice notice-info"><p>ℹ️ No products were changed.</p></div>';
            }
        }
    }

    // Handle Admin Reschedule
    if ($_POST && isset($_POST['opc_action']) && $_POST['opc_action'] === 'admin_reschedule') {
        $resch_id = sanitize_text_field($_POST['resch_id'] ?? '');
        $new_date = sanitize_text_field($_POST['resch_date'] ?? '');
        $new_time = sanitize_text_field($_POST['resch_time'] ?? '');

        if (!empty($resch_id) && !empty($new_date) && !empty($new_time)) {
            $all_bookings = get_option('opc_all_bookings', array());
            if (isset($all_bookings[$resch_id])) {
                $old_date = $all_bookings[$resch_id]['date'];
                $old_time = $all_bookings[$resch_id]['time'];
                $phone = $all_bookings[$resch_id]['phone'];

                $type = $all_bookings[$resch_id]['type'] ?? 'grooming';
                $booked_option_key = ($type === 'doctor') ? 'opc_booked_slots_doctor' : 'opc_booked_slots_grooming';

                // Swap Slots
                $slots = get_option($booked_option_key, array());
                if (isset($slots[$old_date])) {
                    $slots[$old_date] = array_diff($slots[$old_date], [$old_time]);
                }
                if (!isset($slots[$new_date]))
                    $slots[$new_date] = array();
                if (!in_array($new_time, $slots[$new_date])) {
                    $slots[$new_date][] = $new_time;
                }
                update_option($booked_option_key, $slots);

                // Update array
                $all_bookings[$resch_id]['date'] = $new_date;
                $all_bookings[$resch_id]['time'] = $new_time;
                $all_bookings[$resch_id]['status'] = 'rescheduled';
                update_option('opc_all_bookings', $all_bookings);

                // Update in history as well
                $history = get_option('opc_booking_history', array());
                foreach ($history as $key => $h) {
                    if (($h['phone'] ?? '') === $phone && ($h['status'] ?? '') !== 'rescheduled') {
                        $history[$key]['status'] = 'rescheduled';
                        $history[$key]['date'] = $new_date;
                        $history[$key]['time'] = $new_time;
                    }
                }
                update_option('opc_booking_history', $history);

                $message = '<div class="notice notice-success"><p>✅ Successfully rescheduled appointment!</p></div>';
            }
        }
    }

    // Handle Admin Complete Appointment
    if ($_POST && isset($_POST['opc_action']) && $_POST['opc_action'] === 'admin_complete') {
        $complete_id = sanitize_text_field($_POST['complete_id'] ?? '');

        if (!empty($complete_id)) {
            $all_bookings = get_option('opc_all_bookings', array());
            if (isset($all_bookings[$complete_id])) {
                $phone = $all_bookings[$complete_id]['phone'] ?? $complete_id;

                // Update all_bookings array
                $all_bookings[$complete_id]['status'] = 'completed';
                $all_bookings[$complete_id]['payment_status'] = 'completed';
                update_option('opc_all_bookings', $all_bookings);

                // Update in history array as well
                $history = get_option('opc_booking_history', array());
                foreach ($history as $key => &$h) {
                    if ((isset($h['id']) && $h['id'] === $complete_id) || (($h['phone'] ?? '') === $phone && $phone !== $complete_id)) {
                        $h['status'] = 'completed';
                        $h['payment_status'] = 'completed';
                    }
                }
                update_option('opc_booking_history', $history);

                $message = '<div class="notice notice-success"><p>✅ Successfully marked appointment as Completed!</p></div>';
            }
        }
    }

    // Handle Admin Cancel Appointment
    if ($_POST && isset($_POST['opc_action']) && $_POST['opc_action'] === 'admin_cancel') {
        $cancel_id = sanitize_text_field($_POST['cancel_id'] ?? '');

        if (!empty($cancel_id)) {
            $all_bookings = get_option('opc_all_bookings', array());
            if (isset($all_bookings[$cancel_id])) {
                $phone = $all_bookings[$cancel_id]['phone'] ?? $cancel_id;

                // Update all_bookings array
                $all_bookings[$cancel_id]['status'] = 'cancelled';
                update_option('opc_all_bookings', $all_bookings);

                // Update in history array as well
                $history = get_option('opc_booking_history', array());
                foreach ($history as $key => &$h) {
                    if ((isset($h['id']) && $h['id'] === $cancel_id) || (($h['phone'] ?? '') === $phone && $phone !== $cancel_id)) {
                        $h['status'] = 'cancelled';
                    }
                }
                update_option('opc_booking_history', $history);

                $message = '<div class="notice notice-warning"><p>🚫 Successfully cancelled appointment.</p></div>';
            }
        }
    }

    // Handle Admin Refund Appointment
    if ($_POST && isset($_POST['opc_action']) && $_POST['opc_action'] === 'admin_refund') {
        $refund_id = sanitize_text_field($_POST['refund_id'] ?? '');

        if (!empty($refund_id)) {
            $all_bookings = get_option('opc_all_bookings', array());
            if (isset($all_bookings[$refund_id])) {
                $phone = $all_bookings[$refund_id]['phone'] ?? $refund_id;

                // Refund logic with razorpay 
                $payment_id = $all_bookings[$refund_id]['razorpay_payment_id'] ?? '';
                $amount = $all_bookings[$refund_id]['amount'] ?? 0;

                $rp_key = get_option('opc_razorpay_key_id', '');
                $rp_secret = get_option('opc_razorpay_key_secret', '');

                if (!empty($payment_id) && !empty($rp_key) && !empty($rp_secret)) {
                    // Make API call
                    $url = "https://api.razorpay.com/v1/payments/{$payment_id}/refund";
                    $args = array(
                        'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode($rp_key . ':' . $rp_secret),
                            'Content-Type' => 'application/json'
                        ),
                        'body' => json_encode(array(
                            'speed' => 'optimum' // Attempts Instant Refund, falls back to Normal
                        ))
                    );
                    $response = wp_remote_post($url, $args);
                    if (is_wp_error($response)) {
                        $message = '<div class="notice notice-error"><p>❌ Refund API Error: ' . esc_html($response->get_error_message()) . '</p></div>';
                        goto end_refund;
                    }
                    $body = wp_remote_retrieve_body($response);
                    $data = json_decode($body, true);
                    if (isset($data['error'])) {
                        $message = '<div class="notice notice-error"><p>❌ Gateway Error: ' . esc_html($data['error']['description']) . '</p></div>';
                        goto end_refund;
                    }
                }

                $current_user = wp_get_current_user();
                $admin_name = !empty($current_user->user_email) ? $current_user->user_email : 'Admin';
                $refund_id_text = esc_html($data['id'] ?? 'Unknown');

                // Update all_bookings array
                $all_bookings[$refund_id]['payment_status'] = 'refunded';
                $all_bookings[$refund_id]['razorpay_refund_id'] = $refund_id_text;
                $all_bookings[$refund_id]['refunded_by'] = $admin_name;
                update_option('opc_all_bookings', $all_bookings);

                // Update in history array as well
                $history = get_option('opc_booking_history', array());
                foreach ($history as $key => &$h) {
                    if ((isset($h['id']) && $h['id'] === $refund_id) || (($h['phone'] ?? '') === $phone && $phone !== $refund_id)) {
                        $h['payment_status'] = 'refunded';
                        $h['razorpay_refund_id'] = $refund_id_text;
                        $h['refunded_by'] = $admin_name;
                    }
                }
                update_option('opc_booking_history', $history);

                $message = '<div class="notice notice-success"><p>✅ Successfully registered Refund via Razorpay API! <b>(Refund ID: ' . $refund_id_text . ')</b><br><small>Note: Normal refunds take 5-7 business days to reflect in the customer\'s bank account.</small></p></div>';
                end_refund:
                ;
            }
        }
    }

    // Handle Dynamic Pricing Update
    if ($_POST && isset($_POST['opc_action']) && $_POST['opc_action'] === 'update_pricing') {
        $prices = opc_v5_get_current_prices();
        if (isset($_POST['doctors'])) {
            foreach ($_POST['doctors'] as $key => $val) {
                $prices['doctors'][base64_decode($key)] = intval($val);
            }
        }
        if (isset($_POST['grooming'])) {
            foreach ($_POST['grooming'] as $cat_key => $sizes) {
                foreach ($sizes as $size_key => $val) {
                    $prices['grooming'][base64_decode($cat_key)][$size_key] = ($val === '' || $val < 0) ? null : intval($val);
                }
            }
        }
        update_option('opc_service_prices', $prices);
        $message = '<div class="notice notice-success"><p>✅ Service prices updated successfully!</p></div>';
    }

    // Handle Flush Data
    if ($_POST && isset($_POST['opc_action']) && $_POST['opc_action'] === 'flush_bookings') {
        delete_option('opc_all_bookings');
        delete_option('opc_booking_history');
        delete_option('opc_booked_slots');
        delete_option('opc_booked_slots_doctor');
        delete_option('opc_booked_slots_grooming');
        $message = '<div class="notice notice-success" style="background:#fee; border-color:#fcc;"><p>🚨 All bookings and blocked calendar slots have been permanently deleted!</p></div>';
    }
    // Handle Razorpay API Keys Update
    if ($_POST && isset($_POST['opc_action']) && $_POST['opc_action'] === 'save_api_keys') {
        update_option('opc_razorpay_key_id', sanitize_text_field($_POST['rp_key'] ?? ''));
        update_option('opc_razorpay_key_secret', sanitize_text_field($_POST['rp_secret'] ?? ''));
        $message = '<div class="notice notice-success"><p>✅ Razorpay API Credentials Saved!</p></div>';
    }

    // Get ALL WC Products Safely
    if ($wc_active) {
        try {
            $wc_products = wc_get_products(array(
                'limit' => -1, // Get all products
                'status' => 'publish',
                'stock_status' => array('instock', 'outofstock')
            ));
        } catch (Exception $e) {
            $wc_products = array();
        }
    }

    $times = array("10:00 AM", "11:00 AM", "12:00 PM", "1:00 PM", "2:00 PM", "3:00 PM", "4:00 PM", "5:00 PM", "6:00 PM", "7:00 PM", "8:00 PM");

    $all_bookings = get_option('opc_all_bookings', array());
    $active_bookings = array();
    $rescheduled_bookings = array();
    $today_bookings = array();
    $cancelled_bookings = array();
    $completed_bookings = array();

    $today_str = wp_date('Y-m-d');

    // Filter by requested date if provided by admin
    $filter_date = isset($_POST['view_date']) ? sanitize_text_field($_POST['view_date']) : '';

    if (!empty($all_bookings) && is_array($all_bookings)) {
        foreach ($all_bookings as $phone => $b) {
            $status = $b['status'] ?? 'active';
            $b_date = $b['date'] ?? '';

            if (!empty($filter_date)) {
                if ($b_date === $filter_date && $status !== 'cancelled') {
                    $today_bookings[$phone] = $b;
                }
            } else {
                if ($b_date === $today_str && $status !== 'cancelled') {
                    $today_bookings[$phone] = $b;
                }
            }

            if ($status === 'rescheduled') {
                $rescheduled_bookings[$phone] = $b;
            } elseif ($status === 'cancelled') {
                $cancelled_bookings[$phone] = $b;
            } elseif ($status === 'completed') {
                $completed_bookings[$phone] = $b;
            } elseif ($status === 'active') {
                $active_bookings[$phone] = $b;
            }
        }
    }

    // Helper: sort a bookings array by date + time ascending
    $sort_by_datetime = function (&$arr) {
        uasort($arr, function ($a, $b) {
            $dt_a = strtotime(($a['date'] ?? '1970-01-01') . ' ' . ($a['time'] ?? '00:00 AM'));
            $dt_b = strtotime(($b['date'] ?? '1970-01-01') . ' ' . ($b['time'] ?? '00:00 AM'));
            if ($dt_a === $dt_b)
                return 0;
            return ($dt_a < $dt_b) ? -1 : 1;
        });
    };

    $sort_by_datetime($active_bookings);
    $sort_by_datetime($rescheduled_bookings);
    $sort_by_datetime($today_bookings);
    $sort_by_datetime($cancelled_bookings);
    $sort_by_datetime($completed_bookings);

    $prices = opc_v5_get_current_prices();
    ?>

    <div class="wrap" style="max-width: 1200px; margin-top:20px;">
        <h1 style="font-size: 28px; margin-bottom: 20px; color:#ea580c; display:flex; align-items:center;">
            <span class="dashicons dashicons-pets"
                style="font-size:32px; height:32px; width:32px; margin-right:8px;"></span>
            Orange Pet Clinic Command Center
        </h1>

        <?php echo $message; ?>

        <div style="display:flex; gap:20px; flex-wrap:wrap; align-items:flex-start;">

            <!-- CALENDAR BLOCKER -->
            <div
                style="flex:1; min-width:300px; background:white; padding:25px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1);">
                <h2>📅 Block Calendar</h2>
                <form method="POST">
                    <input type="hidden" name="opc_action" value="block_calendar">
                    <p><label>Date: </label><input type="date" name="block_date" required style="width:100%; padding:8px;"
                            min="<?php echo date('Y-m-d'); ?>"></p>
                    <p><label>Service to Block:</label>
                        <select name="block_type" style="width:100%; padding:8px;">
                            <option value="both">Both (Doctor & Grooming)</option>
                            <option value="doctor">Doctor Consultations Only</option>
                            <option value="grooming">Grooming Only</option>
                        </select>
                    </p>
                    <p><label style="display:flex; gap:8px; padding:10px; background:#fff3cd; border-radius:4px;"><input
                                type="checkbox" name="block_all_day"> Block Entire Day</label></p>
                    <p id="timepicker"><label>Or Specific Time:</label>
                        <select name="block_time" style="width:100%; padding:8px;">
                            <option value="">Choose time...</option>
                            <?php foreach ($times as $t)
                                echo "<option value='$t'>$t</option>"; ?>
                        </select>
                    </p>
                    <button type="submit" class="button button-primary"
                        style="width:100%; height:40px; background:#ea580c; border-color:#ea580c;">Save Blocked
                        Slots</button>
                </form>
            </div>

            <!-- CALENDAR UNBLOCKER -->
            <div
                style="flex:1; min-width:300px; background:white; padding:25px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1);">
                <h2>🔓 Unblock Calendar</h2>
                <form method="POST">
                    <input type="hidden" name="opc_action" value="unblock_calendar">
                    <p><label>Date: </label><input type="date" name="unblock_date" required style="width:100%; padding:8px;"
                            min="<?php echo date('Y-m-d'); ?>"></p>
                    <p><label>Service to Unblock:</label>
                        <select name="unblock_type" style="width:100%; padding:8px;">
                            <option value="both">Both (Doctor & Grooming)</option>
                            <option value="doctor">Doctor Consultations Only</option>
                            <option value="grooming">Grooming Only</option>
                        </select>
                    </p>
                    <p><label style="display:flex; gap:8px; padding:10px; background:#d1fae5; border-radius:4px;"><input
                                type="checkbox" name="unblock_all_day"> Unblock Entire Day</label></p>
                    <p id="untimepicker"><label>Or Specific Time:</label>
                        <select name="unblock_time" style="width:100%; padding:8px;">
                            <option value="">Choose time...</option>
                            <?php foreach ($times as $t)
                                echo "<option value='$t'>$t</option>"; ?>
                        </select>
                    </p>
                    <button type="submit" class="button button-primary"
                        style="width:100%; height:40px; background:#059669; border-color:#059669;">Remove Block</button>
                </form>
            </div>

            <!-- INVENTORY MANAGER -->
            <div
                style="flex:2; min-width:400px; background:white; padding:25px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 10px;">
                    <h2 style="margin:0;">📦 Inventory Manager</h2>
                    <input type="text" id="inventorySearch" placeholder="🔍 Search products..."
                        style="padding:8px; width:220px; border:1px solid #ccc; border-radius:4px;"
                        onkeydown="return event.key !== 'Enter';">
                </div>

                <?php if (!$wc_active): ?>
                    <div style="padding:20px; background:#fee; border:1px solid #fcc; border-radius:4px;"><strong>❌ WooCommerce
                            not active</strong><br>Please activate WooCommerce plugin first.</div>
                <?php else: ?>
                    <?php if (empty($wc_products)): ?>
                        <div style="padding:20px; background:#fff3cd; border:1px solid #ffeaa7;"><strong>ℹ️ No products
                                found</strong><br>Create some products in WooCommerce > Products.</div>
                    <?php else: ?>
                        <form method="POST" id="bulkInventoryForm">
                            <input type="hidden" name="opc_action" value="update_inventory_bulk">

                            <div style="max-height:400px; overflow:auto; border:1px solid #e5e5e5; border-radius:4px;">
                                <table class="wp-list-table widefat striped" id="inventoryTable">
                                    <thead style="position:sticky; top:0; background:#f0f0f1; z-index:10;">
                                        <tr>
                                            <th style="width:40%">Product</th>
                                            <th style="width:30%">Stock Qty</th>
                                            <th style="width:30%">Price (₹)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($wc_products as $product):
                                            $pid = $product->get_id();
                                            $stock = $product->get_stock_quantity() ?: 0;
                                            $price = $product->get_regular_price() !== '' ? $product->get_regular_price() : $product->get_price();
                                            ?>
                                            <tr class="inv-row" data-name="<?php echo strtolower(esc_attr($product->get_name())); ?>"
                                                style="transition: background-color 0.2s;">
                                                <td class="inv-name" style="vertical-align:middle;">
                                                    <strong><?php echo esc_html($product->get_name()); ?></strong><br><span
                                                        style="color:#666; font-size:11px;">Current: <?php echo $stock; ?> stock |
                                                        ₹<?php echo esc_html($price); ?></span>
                                                </td>
                                                <td style="vertical-align:middle;">
                                                    <input type="number" class="inv-field inv-qty"
                                                        name="bulk_inventory[<?php echo $pid; ?>][qty]" value="<?php echo $stock; ?>"
                                                        data-orig="<?php echo $stock; ?>" min="0" style="width:80px; padding:4px;">
                                                </td>
                                                <td style="vertical-align:middle;">
                                                    <input type="number" step="0.01" class="inv-field inv-price"
                                                        name="bulk_inventory[<?php echo $pid; ?>][price]"
                                                        value="<?php echo esc_attr($price); ?>"
                                                        data-orig="<?php echo esc_attr($price); ?>" min="0"
                                                        style="width:90px; padding:4px;">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div style="margin-top:15px; display:flex; justify-content:flex-end;">
                                <button type="button" class="button button-primary" id="previewChangesBtn"
                                    style="background:#ea580c; border-color:#d04900; height:40px; font-weight:bold; font-size:14px; display:none; transition:opacity 0.2s;">Preview
                                    Changes & Save All</button>
                            </div>

                            <!-- PREVIEW MODAL -->
                            <div id="previewModal"
                                style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:999999; justify-content:center; align-items:center;">
                                <div
                                    style="background:white; padding:30px; border-radius:12px; width:550px; max-width:90%; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                                    <h2 style="margin-top:0; color:#1e293b; display:flex; align-items:center;"><span
                                            class="dashicons dashicons-visibility" style="margin-right:8px; color:#3b82f6;"></span>
                                        Review Your Changes</h2>
                                    <p style="color:#64748b; font-size:13px;">Please verify the updates below before saving to the
                                        database.</p>

                                    <div id="previewContent"
                                        style="max-height:300px; overflow-y:auto; margin:20px 0; background:#f8fafc; border:1px solid #e2e8f0; padding:15px; border-radius:6px;">
                                        <!-- Javascript injects preview list here -->
                                    </div>

                                    <div style="display:flex; justify-content:flex-end; gap:12px;">
                                        <button type="button" class="button"
                                            onclick="document.getElementById('previewModal').style.display='none';"
                                            style="height:38px;">Wait, Go Back</button>
                                        <button type="button" id="confirmSaveBtn" class="button button-primary"
                                            style="background:#16a34a; border-color:#15803d; height:38px; display:flex; align-items:center;">
                                            <span class="dashicons dashicons-yes-alt" style="margin-right:4px;"></span> Confirm &
                                            Save All
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- DYNAMIC PRICING MANAGER -->
            <div
                style="flex:100%; min-width:100%; background:white; padding:25px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); margin-top:10px;">
                <h2>💰 Pricing & Fees Manager</h2>
                <form method="POST">
                    <input type="hidden" name="opc_action" value="update_pricing">
                    <h3 style="margin-top:20px; border-bottom:1px solid #ccc; padding-bottom:5px;">Doctor Fees</h3>
                    <div
                        style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:15px; margin-bottom: 20px;">
                        <?php foreach ($prices['doctors'] as $doc => $fee): ?>
                            <div><label style="display:block; font-weight:bold;"><?php echo esc_html($doc); ?></label><input
                                    type="number" name="doctors[<?php echo base64_encode($doc); ?>]"
                                    value="<?php echo esc_attr($fee); ?>" style="width:100%;"></div>
                        <?php endforeach; ?>
                    </div>
                    <h3 style="margin-top:20px; border-bottom:1px solid #ccc; padding-bottom:5px;">Grooming Services</h3>
                    <table class="wp-list-table widefat striped">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Small</th>
                                <th>Medium</th>
                                <th>Large</th>
                                <th>Puppy</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prices['grooming'] as $cat => $sizes): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($cat); ?></strong></td>
                                    <?php foreach (array('Small', 'Medium', 'Large', 'Puppy') as $s):
                                        $val = isset($sizes[$s]) && $sizes[$s] !== null ? $sizes[$s] : ''; ?>
                                        <td><input type="number"
                                                name="grooming[<?php echo base64_encode($cat); ?>][<?php echo $s; ?>]"
                                                value="<?php echo esc_attr($val); ?>" placeholder="N/A" style="width:80px;"></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="submit" class="button button-primary"
                        style="margin-top:15px; height:40px; background:#16a34a; border-color:#16a34a; width: 200px;">Save
                        Pricing</button>
                </form>
            </div>

            <?php
            function render_bookings_table($title, $desc, $bookings, $times, $show_actions = true)
            {
                ?>
                <div
                    style="flex:100%; min-width:100%; background:white; padding:25px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); margin-top:20px;">
                    <h2><?php echo esc_html($title); ?></h2>
                    <p style="color:#666;"><?php echo esc_html($desc); ?></p>
                    <?php if (empty($bookings)): ?>
                        <div style="padding:20px; background:#f8f9fa; border:1px solid #ddd;">No appointments found.</div>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table class="wp-list-table widefat striped">
                                <thead>
                                    <tr>
                                        <th style="width:20%">Customer</th>
                                        <th style="width:30%">Service</th>
                                        <th style="width:20%">Date & Time</th>
                                        <?php if ($show_actions): ?>
                                            <th style="width:30%">Actions</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $key => $b): ?>
                                        <tr>
                                            <td style="vertical-align:middle;">
                                                <strong><?php echo esc_html($b['name'] ?? 'Unknown'); ?></strong><br><span
                                                    style="color:#666; font-size:12px;"><?php echo esc_html($b['phone'] ?? $key); ?></span>
                                            </td>
                                            <td style="vertical-align:middle;">
                                                <?php if (isset($b['type']) && $b['type'] === 'doctor'): ?>
                                                    <span
                                                        style="display:inline-block; padding:2px 6px; background:#e0f2fe; color:#0369a1; border-radius:4px; font-size:12px; margin-bottom:4px;">Doctor
                                                        Cons.</span><br><strong><?php echo esc_html($b['doctor'] ?? ''); ?></strong>
                                                <?php elseif (isset($b['type']) && $b['type'] === 'grooming'): ?>
                                                    <span
                                                        style="display:inline-block; padding:2px 6px; background:#fce7f3; color:#be185d; border-radius:4px; font-size:12px; margin-bottom:4px;">Grooming</span><br><strong><?php echo esc_html($b['category'] ?? ''); ?></strong><br><span
                                                        style="font-size:12px; color:#666;">Size:
                                                        <?php echo esc_html($b['breed'] ?? ''); ?></span>
                                                <?php else: ?>
                                                    <span style="color:#999;">Legacy Booking</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="vertical-align:middle; font-weight:bold;">
                                                <?php echo esc_html($b['date']); ?><br><span
                                                    style="color:#ea580c;"><?php echo esc_html($b['time']); ?></span>
                                                <?php if (!empty($b['payment_method'])): ?>
                                                    <br><div
                                                        style="font-size:11px; background:#f3f4f6; color:#4b5563; padding:5px; border-radius:3px; display:inline-block; margin-top:4px;">
                                                        <strong>Payment:</strong> <?php echo esc_html(ucfirst($b['payment_method'])); ?>
                                                        (<?php echo esc_html(ucfirst($b['payment_status'] ?? 'pending')); ?>)
                                                        <?php if (!empty($b['razorpay_payment_id'])): ?>
                                                            <br><span style="color:#6b7280;">Txn: <?php echo esc_html($b['razorpay_payment_id']); ?></span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($b['razorpay_refund_id'])): ?>
                                                            <br><span style="color:#059669;">Refund ID: <?php echo esc_html($b['razorpay_refund_id']); ?></span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($b['refunded_by'])): ?>
                                                            <br><span style="color:#6b7280;">Refunded by: <?php echo esc_html($b['refunded_by']); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($show_actions): ?>
                                                <td>
                                                    <div style="display:flex; flex-direction:column; gap:8px;">
                                                        <?php if (($b['status'] ?? 'active') !== 'cancelled'): ?>
                                                            <form method="POST"
                                                                style="display:flex; flex-direction:column; gap:8px; align-items:flex-start; margin:0;">
                                                                <input type="hidden" name="opc_action" value="admin_reschedule"><input
                                                                    type="hidden" name="resch_id"
                                                                    value="<?php echo esc_attr($b['id'] ?? $key); ?>">
                                                                <div style="display:flex; gap:5px;"><input type="date" name="resch_date"
                                                                        required style="padding:4px; width:130px;"
                                                                        min="<?php echo date('Y-m-d'); ?>"><select name="resch_time" required
                                                                        style="padding:4px; width:100px;">
                                                                        <option value="">Time...</option>
                                                                        <?php foreach ($times as $t)
                                                                            echo "<option value='$t'>$t</option>"; ?>
                                                                    </select></div>
                                                                <button type="submit" class="button button-primary"
                                                                    style="width: 100%;">Reschedule
                                                                    Slot</button>
                                                            </form>
                                                            <?php if (($b['status'] ?? 'active') !== 'completed'): ?>
                                                                <form method="POST" style="margin:0;"
                                                                    onsubmit="return confirm('Mark this appointment as Completed?');">
                                                                    <input type="hidden" name="opc_action" value="admin_complete">
                                                                    <input type="hidden" name="complete_id"
                                                                        value="<?php echo esc_attr($b['id'] ?? $key); ?>">
                                                                    <button type="submit" class="button"
                                                                        style="width: 100%; background:#d1fae5; color:#065f46; border-color:#34d399;">✅
                                                                        Mark Completed</button>
                                                                </form>
                                                            <?php endif; ?>
                                                        <?php endif; ?>

                                                        <?php if (($b['status'] ?? 'active') === 'cancelled' && ($b['payment_method'] ?? '') === 'online' && ($b['payment_status'] ?? '') !== 'refunded'): ?>
                                                            <form method="POST" style="margin:0;"
                                                                onsubmit="return confirm('Initiate a real Razorpay Refund for this appointment?');">
                                                                <input type="hidden" name="opc_action" value="admin_refund">
                                                                <input type="hidden" name="refund_id"
                                                                    value="<?php echo esc_attr($b['id'] ?? $key); ?>">
                                                                <button type="submit" class="button"
                                                                    style="width: 100%; background:#fee2e2; color:#991b1b; border-color:#f87171;">💸
                                                                    Initiate Razorpay Refund</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
            }
            ?>

            <!-- TABLES -->
            <div
                style="flex:100%; min-width:100%; margin-top:20px; display:flex; justify-content:space-between; align-items:center;">
                <h2 style="margin:0;">📅 Appointments View</h2>
                <form method="POST" style="display:flex; gap:10px;">
                    <input type="date" name="view_date" value="<?php echo esc_attr($filter_date ?: $today_str); ?>"
                        style="padding:8px; border-radius:4px; border:1px solid #ccc;">
                    <button type="submit" class="button button-primary"
                        style="background:#ea580c; border-color:#ea580c;">Filter Date</button>
                    <?php if (!empty($filter_date)): ?>
                        <a href="?page=opc-admin-dashboard" class="button">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php
            $view_title = empty($filter_date) ? "🎯 Today's Appointments" : "📅 Appointments for " . esc_html($filter_date);
            $view_desc = "Showing appointments scheduled for the selected date.";
            render_bookings_table($view_title, $view_desc, $today_bookings, $times);
            ?>

            <?php render_bookings_table("✅ Active Appointments", "These are the regular appointments requested by customers.", $active_bookings, $times); ?>
            <?php render_bookings_table("🔄 Rescheduled Appointments", "These appointments have already been rescheduled.", $rescheduled_bookings, $times); ?>
            <?php render_bookings_table("🌟 Completed Appointments", "These appointments have been marked as completed.", $completed_bookings, $times, false); ?>
            <?php render_bookings_table("❌ Cancelled Appointments", "These appointments were cancelled.", $cancelled_bookings, $times, true); ?>

            <!-- API CREDENTIALS AREA -->
            <div
                style="flex:100%; min-width:100%; background:white; padding:25px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); margin-top:20px;">
                <h2 style="color:#0369a1;">🔑 API Credentials Configuration</h2>
                <p style="color:#666; font-size: 13px;">Save your Razorpay Keys here so that you can issue automated Refunds
                    directly from this dashboard.</p>
                <form method="POST" style="max-width: 600px;">
                    <input type="hidden" name="opc_action" value="save_api_keys">

                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-weight:bold; margin-bottom:5px;">Razorpay Key ID</label>
                        <input type="text" name="rp_key"
                            value="<?php echo esc_attr(get_option('opc_razorpay_key_id', '')); ?>"
                            style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;"
                            placeholder="rzp_live_...">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-weight:bold; margin-bottom:5px;">Razorpay Key Secret</label>
                        <input type="password" name="rp_secret"
                            value="<?php echo esc_attr(get_option('opc_razorpay_key_secret', '')); ?>"
                            style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;"
                            placeholder="Paste secret here...">
                    </div>

                    <button type="submit" class="button button-primary" style="height:35px;">Save API Keys</button>
                </form>
            </div>

            <!-- DANGER ZONE -->
            <div
                style="flex:100%; min-width:100%; background:#fff5f5; padding:25px; border-radius:8px; border:2px solid #feb2b2; margin-top:20px;">
                <h2 style="color:#e53e3e;">🚨 Danger Zone</h2>
                <form method="POST"
                    onsubmit="return confirm('Are you absolutely sure? Everything will fade into darkness!');">
                    <input type="hidden" name="opc_action" value="flush_bookings"><button type="submit" class="button"
                        style="background:#e53e3e; color:white; border-color:#c53030; height:40px;">🗑️ Flush All Bookings &
                        Start Fresh</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.querySelector('input[name="block_all_day"]').addEventListener('change', function () { document.getElementById('timepicker').style.opacity = this.checked ? '0.5' : '1'; });
        document.querySelector('input[name="unblock_all_day"]').addEventListener('change', function () { document.getElementById('untimepicker').style.opacity = this.checked ? '0.5' : '1'; });

        // --- INVENTORY MANAGER SEARCH, HIGHLIGHT & PREVIEW ---
        document.addEventListener('DOMContentLoaded', function () {
            const invSearch = document.getElementById('inventorySearch');
            const invTable = document.getElementById('inventoryTable');
            const previewBtn = document.getElementById('previewChangesBtn');
            const previewModal = document.getElementById('previewModal');
            const previewContent = document.getElementById('previewContent');
            const confirmSaveBtn = document.getElementById('confirmSaveBtn');
            const bulkForm = document.getElementById('bulkInventoryForm');

            if (invSearch && invTable) {
                // 1. Live Search
                invSearch.addEventListener('input', function (e) {
                    const term = e.target.value.toLowerCase().trim();
                    const rows = invTable.querySelectorAll('.inv-row');
                    rows.forEach(row => {
                        const name = row.getAttribute('data-name');
                        if (name.includes(term)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });

                // 2. Highlight Changes & Show Main Save Button
                const inputs = invTable.querySelectorAll('.inv-field');
                inputs.forEach(input => {
                    input.addEventListener('input', function () {
                        const row = this.closest('tr');
                        const rowInputs = row.querySelectorAll('.inv-field');
                        let isChanged = false;

                        rowInputs.forEach(inp => {
                            // Compare using standard string comparison. Make sure '' matches original if needed
                            let val = inp.value;
                            if (val !== inp.getAttribute('data-orig')) {
                                isChanged = true;
                            }
                        });

                        if (isChanged) {
                            row.style.backgroundColor = '#fed7aa'; // Nice orange highlight
                            row.classList.add('is-modified');
                        } else {
                            row.style.backgroundColor = '';
                            row.classList.remove('is-modified');
                        }

                        // Check if any row in entire table has modifications
                        const anyChangedRows = invTable.querySelector('.is-modified');
                        if (anyChangedRows) {
                            previewBtn.style.display = 'block';
                        } else {
                            previewBtn.style.display = 'none';
                            if (previewModal.style.display === 'flex') {
                                previewModal.style.display = 'none';
                            }
                        }
                    });
                });

                // 3. Open Preview Modal
                previewBtn.addEventListener('click', function () {
                    let html = '<ul style="margin:0; padding-left:20px; line-height:1.6; font-size:14px;">';
                    const modifiedRows = invTable.querySelectorAll('.is-modified');

                    if (modifiedRows.length === 0) return;

                    modifiedRows.forEach(row => {
                        const name = row.querySelector('.inv-name strong').textContent;
                        const qtyInput = row.querySelector('.inv-qty');
                        const priceInput = row.querySelector('.inv-price');

                        const origQty = qtyInput.getAttribute('data-orig');
                        const newQty = qtyInput.value;
                        const origPrice = priceInput.getAttribute('data-orig');
                        const newPrice = priceInput.value;

                        let changes = [];
                        if (origQty !== newQty) {
                            changes.push(`<span style="color:#64748b;">Qty:</span> <span style="text-decoration:line-through; color:#ef4444;">${origQty}</span> ➔ <strong style="color:#10b981;">${newQty || '0'}</strong>`);
                        }
                        if (origPrice !== newPrice) {
                            changes.push(`<span style="color:#64748b;">Price:</span> <span style="text-decoration:line-through; color:#ef4444;">₹${origPrice}</span> ➔ <strong style="color:#10b981;">₹${newPrice || '0'}</strong>`);
                        }

                        if (changes.length > 0) {
                            html += `<li style="margin-bottom:8px;"><strong>${name}</strong><br>` + changes.join(' <span style="margin:0 8px; color:#cbd5e1;">|</span> ') + `</li>`;
                        }
                    });

                    html += '</ul>';
                    previewContent.innerHTML = html;
                    previewModal.style.display = 'flex';
                });

                // 4. Submit form exactly once
                confirmSaveBtn.addEventListener('click', function () {
                    confirmSaveBtn.innerHTML = '<span class="dashicons dashicons-update" style="margin-right:4px;"></span> Saving...';
                    confirmSaveBtn.style.opacity = '0.7';
                    confirmSaveBtn.style.pointerEvents = 'none';

                    // We only want to submit the input fields that actually changed so we don't send useless fields back
                    const unchangedInputs = invTable.querySelectorAll('.inv-row:not(.is-modified) .inv-field');
                    unchangedInputs.forEach(inp => inp.disabled = true);

                    bulkForm.submit();
                });
            }
        });
    </script>
    <?php
}

// ─────────────────────────────────────────────────────────────
// 3. WOOCOMMERCE MY ACCOUNT - MY SERVICES TAB
// ─────────────────────────────────────────────────────────────

// 1. Register new endpoint
add_action('init', 'opc_add_services_endpoint');
function opc_add_services_endpoint()
{
    add_rewrite_endpoint('my-services', EP_ROOT | EP_PAGES);
}

// 2. Add query var
add_filter('query_vars', 'opc_services_query_vars', 0);
function opc_services_query_vars($vars)
{
    $vars[] = 'my-services';
    return $vars;
}

// 3. Insert into My Account menu
add_filter('woocommerce_account_menu_items', 'opc_add_services_link_my_account');
function opc_add_services_link_my_account($items)
{
    // Insert it after 'orders'
    $new_items = array();
    foreach ($items as $key => $val) {
        $new_items[$key] = $val;
        if ($key === 'orders') {
            $new_items['my-services'] = 'My Services';
        }
    }
    // If orders tab isn't found for some reason, just append to bottom
    if (!isset($new_items['my-services'])) {
        $new_items['my-services'] = 'My Services';
    }
    return $new_items;
}

// 4. Render content for the endpoint
add_action('woocommerce_account_my-services_endpoint', 'opc_services_endpoint_content');
function opc_services_endpoint_content()
{
    $current_user = wp_get_current_user();
    if (!$current_user->exists())
        return;

    // Handle Frontend Cancellation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['opc_frontend_action']) && $_POST['opc_frontend_action'] === 'cancel_appointment') {
        $cancel_id = sanitize_text_field($_POST['cancel_id'] ?? '');
        if (!empty($cancel_id)) {
            $updated = false;

            // Cancel in legacy/active bookings
            $all_bookings = get_option('opc_all_bookings', array());
            if (isset($all_bookings[$cancel_id])) {
                $all_bookings[$cancel_id]['status'] = 'cancelled';
                update_option('opc_all_bookings', $all_bookings);
                $updated = true;
            } else {
                // Fallback for older arrays that used phone keys
                foreach ($all_bookings as $key => &$b) {
                    if (($b['id'] ?? $key) === $cancel_id) {
                        $b['status'] = 'cancelled';
                        update_option('opc_all_bookings', $all_bookings);
                        $updated = true;
                        break;
                    }
                }
            }

            // Cancel in history
            $history = get_option('opc_booking_history', array());
            if (!empty($history)) {
                foreach ($history as &$h) {
                    if (($h['id'] ?? '') === $cancel_id) {
                        $h['status'] = 'cancelled';
                        update_option('opc_booking_history', $history);
                        $updated = true;
                        break;
                    }
                }
            }

            if ($updated) {
                echo '<div class="woocommerce-message" style="background:#d1fae5; color:#065f46; border-color:#34d399;">Appointment cancelled successfully.</div>';
            }
        }
    }

    // Fetch user details to link their historical appointments
    $user_email = $current_user->user_email;
    $wc_phone = get_user_meta($current_user->ID, 'billing_phone', true);
    // Sanitize phone to only numbers for robust matching
    if (!empty($wc_phone)) {
        $wc_phone = preg_replace('/[^0-9]/', '', $wc_phone);
    }

    $history = get_option('opc_booking_history', array());
    $legacy = get_option('opc_all_bookings', array());

    // Combine legacy and history, resolving duplicates by ID
    $all_user_records = array();
    $six_months_ago = strtotime('-6 months');

    $check_booking = function ($b) use ($user_email, $wc_phone, $six_months_ago, &$all_user_records) {
        $b_email = strtolower(trim($b['email'] ?? ''));
        $b_phone = preg_replace('/[^0-9]/', '', $b['phone'] ?? '');
        $b_date = strtotime($b['date']);

        $match_email = !empty($user_email) && $b_email === strtolower($user_email);
        $match_phone = !empty($wc_phone) && $b_phone === $wc_phone;

        if (($match_email || $match_phone) && $b_date >= $six_months_ago) {
            $id = $b['id'] ?? uniqid();
            $all_user_records[$id] = $b;
        }
    };

    if (is_array($history)) {
        foreach ($history as $b) {
            $check_booking($b);
        }
    }
    if (is_array($legacy)) {
        foreach ($legacy as $b) {
            $check_booking($b);
        }
    }

    $user_bookings = array_values($all_user_records);

    // Sort by date descending
    usort($user_bookings, function ($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    echo '<h3 style="margin-bottom:15px; color:#ea580c; display:flex; align-items:center;"><span class="dashicons dashicons-pets" style="margin-right:8px; line-height:unset;"></span> My Service Appointments (Last 6 Months)</h3>';

    if (empty($user_bookings)) {
        echo '<div class="woocommerce-info">You have not booked any services with us in the last 6 months.</div>';
    } else {
        echo '<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">';
        echo '<thead><tr>';
        echo '<th class="woocommerce-orders-table__header">Service Type</th>';
        echo '<th class="woocommerce-orders-table__header">Details</th>';
        echo '<th class="woocommerce-orders-table__header">Date & Time</th>';
        echo '<th class="woocommerce-orders-table__header">Status</th>';
        echo '</tr></thead><tbody>';

        foreach ($user_bookings as $booking) {
            echo '<tr class="woocommerce-orders-table__row">';

            // Service Type
            echo '<td class="woocommerce-orders-table__cell" data-title="Service Type">';
            if (isset($booking['type']) && $booking['type'] === 'doctor') {
                echo '<span style="display:inline-block; background:#e0f2fe; color:#0369a1; padding:3px 8px; border-radius:4px; font-size:12px; font-weight:bold;">Doctor</span>';
            } else {
                echo '<span style="display:inline-block; background:#fce7f3; color:#be185d; padding:3px 8px; border-radius:4px; font-size:12px; font-weight:bold;">Grooming</span>';
            }
            echo '</td>';

            // Details
            echo '<td class="woocommerce-orders-table__cell" data-title="Details">';
            if (isset($booking['type']) && $booking['type'] === 'doctor') {
                echo '<strong>' . esc_html($booking['doctor'] ?? 'Consultation') . '</strong>';
            } else {
                echo '<strong>' . esc_html($booking['category'] ?? 'Grooming Service') . '</strong><br>';
                if (!empty($booking['breed'])) {
                    echo '<small style="color:#666;">Size/Breed: ' . esc_html($booking['breed']) . '</small>';
                }
            }
            echo '</td>';

            // Date & Time
            echo '<td class="woocommerce-orders-table__cell" data-title="Date & Time">';
            echo '<strong>' . date_i18n(get_option('date_format'), strtotime($booking['date'])) . '</strong><br>';
            echo '<span style="color:#ea580c;">' . esc_html($booking['time']) . '</span>';
            echo '</td>';

            // Status
            echo '<td class="woocommerce-orders-table__cell" data-title="Status">';
            $status = $booking['status'] ?? 'active';
            $payment_status = $booking['payment_status'] ?? 'pending';

            if ($status === 'rescheduled') {
                echo '<mark class="order-status status-on-hold" style="background:#fff3cd; color:#856404; padding:3px 8px; border-radius:4px; font-size:12px;"><span>Rescheduled</span></mark>';
            } elseif ($status === 'cancelled') {
                if ($payment_status === 'refunded') {
                    echo '<mark class="order-status status-cancelled" style="background:#e0e7ff; color:#3730a3; padding:3px 8px; border-radius:4px; font-size:12px;"><span>Refunded</span></mark>';
                } else {
                    echo '<mark class="order-status status-cancelled" style="background:#fef2f2; color:#991b1b; padding:3px 8px; border-radius:4px; font-size:12px;"><span>Cancelled</span></mark>';
                }
            } elseif ($status === 'completed') {
                echo '<mark class="order-status status-completed" style="background:#f0fdf4; color:#166534; padding:3px 8px; border-radius:4px; font-size:12px;"><span>Completed</span></mark>';
            } else {
                echo '<mark class="order-status status-processing" style="background:#d1fae5; color:#065f46; padding:3px 8px; border-radius:4px; font-size:12px;"><span>Active</span></mark>';
            }
            echo '</td>';

            // Actions
            echo '<td class="woocommerce-orders-table__cell" data-title="Actions">';
            if (in_array($status, ['active', 'rescheduled'])) {
                $booking_id = esc_attr($booking['id'] ?? '');
                if (!empty($booking_id)) {
                    echo '<form method="POST" onsubmit="return confirm(\'Are you sure you want to cancel this appointment?\');" style="margin:0;">';
                    echo '<input type="hidden" name="opc_frontend_action" value="cancel_appointment">';
                    echo '<input type="hidden" name="cancel_id" value="' . $booking_id . '">';
                    echo '<button type="submit" class="button" style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; padding:5px 10px; font-size:12px; border-radius:4px; cursor:pointer;">Cancel</button>';
                    echo '</form>';
                } else {
                    echo '<span style="color:#999;font-size:12px;">N/A (Legacy)</span>';
                }
            } else {
                echo '<span style="color:#999;font-size:12px;">-</span>';
            }
            echo '</td>';

            echo '</tr>';
        }
        echo '</tbody></table>';
    }
}

// Automatically flush rewrite rules once when this new version is active
add_action('init', function () {
    if (!get_option('opc_services_endpoint_flushed_v64')) {
        flush_rewrite_rules();
        update_option('opc_services_endpoint_flushed_v64', 'yes');
    }
});
