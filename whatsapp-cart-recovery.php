<?php
/*
Plugin Name: AI WhatsApp Cart Recovery PRO
Description: Recover abandoned carts via WhatsApp with AI upsells
Version: 1.0
Author: Ketan Chandore
*/

// 1. ADMIN INTERFACE
add_action('admin_menu', 'wacr_pro_menu');
function wacr_pro_menu() {
    add_menu_page(
        'WhatsApp Recovery PRO',
        'WA Recovery',
        'manage_options',
        'wacr-pro',
        'wacr_pro_admin_page',
        'dashicons-whatsapp'
    );
}

function wacr_pro_admin_page() {
    ?>
    <div class="wrap" style="font-family: Arial, sans-serif;">
        <h1 style="color: #25D366;"><span class="dashicons dashicons-whatsapp"></span> WhatsApp Cart Recovery PRO</h1>
        
        <div style="background: white; padding: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2>Settings</h2>
            <form method="post" action="options.php">
                <?php settings_fields('wacr-pro-settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th>Twilio SID</th>
                        <td><input type="text" name="wacr_twilio_sid" value="<?php echo esc_attr(get_option('wacr_twilio_sid')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>Twilio Token</th>
                        <td><input type="password" name="wacr_twilio_token" value="<?php echo esc_attr(get_option('wacr_twilio_token')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>WhatsApp Number</th>
                        <td><input type="text" name="wacr_whatsapp_number" value="<?php echo esc_attr(get_option('wacr_whatsapp_number')); ?>" placeholder="e.g. +14155238886" class="regular-text"></td>
                    </tr>
                </table>
                
                <h3>Message Template</h3>
                <textarea name="wacr_message_template" rows="5" style="width: 100%;">Hi {customer_name}! You left items in your cart: {cart_items}. Complete your order: {checkout_url}</textarea>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="Save Settings">
                </p>
            </form>
        </div>
        
        <div style="margin-top: 20px; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2>Test WhatsApp Notification</h2>
            <form method="post">
                <input type="text" name="test_phone" placeholder="WhatsApp number with country code" class="regular-text">
                <input type="submit" name="send_test" class="button" value="Send Test">
            </form>
        </div>
    </div>
    <?php
}

// 2. CORE FUNCTIONALITY
add_action('woocommerce_order_status_changed', 'wacr_pro_send_notification', 10, 3);
function wacr_pro_send_notification($order_id, $old_status, $new_status) {
    if ($new_status == 'pending') {
        $order = wc_get_order($order_id);
        $phone = $order->get_billing_phone();
        
        // Prepare message
        $message = get_option('wacr_message_template');
        $message = str_replace(
            array('{customer_name}', '{cart_items}', '{checkout_url}'),
            array(
                $order->get_billing_first_name(),
                wacr_pro_get_cart_items($order),
                $order->get_checkout_payment_url()
            ),
            $message
        );
        
        // Send via Twilio
        $twilio_sid = get_option('wacr_twilio_sid');
        $twilio_token = get_option('wacr_twilio_token');
        $whatsapp_number = get_option('wacr_whatsapp_number');
        
        if ($twilio_sid && $twilio_token && $whatsapp_number) {
            $client = new Twilio\Rest\Client($twilio_sid, $twilio_token);
            $client->messages->create(
                "whatsapp:$phone",
                array(
                    'from' => "whatsapp:$whatsapp_number",
                    'body' => $message
                )
            );
        }
    }
}

// Helper function
function wacr_pro_get_cart_items($order) {
    $items = array();
    foreach ($order->get_items() as $item) {
        $items[] = $item->get_name() . ' (' . wc_price($item->get_total()) . ')';
    }
    return implode("\n", $items);
}