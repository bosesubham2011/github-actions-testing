<?php
/**
 * Plugin Name: PipsPower Custom Checkout Extension
 * Description: Adds confirmation message, backend verification, and SMS updates to WooCommerce checkout while keeping Flatsome design.
 * Author: Pips Power
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ✅ Add delayed confirmation message on checkout page
add_action('woocommerce_review_order_before_submit', function() {
    ?>
    <div id="pips-delay-message" style="display:none; background:#fff3cd; border:1px solid #ffc107; padding:15px; border-radius:6px; margin-top:20px;">
        <h4>⏳ Payment Verification Update</h4>
        <p>Thank you for your payment! If confirmation takes longer than usual, rest assured our team is on it.</p>
        <div style="background:#e7f3ff; border-left:4px solid #2196F3; padding:10px; margin-top:10px;">
            <strong>Need Help?</strong><br>
            📞 +91 9876543210<br>
            💬 WhatsApp: +91 9876543210<br>
            📧 support@pipspower.com<br>
            <em>Escalations are handled within 15 minutes during business hours.</em>
        </div>
    </div>
    <script>
        // Simulate delayed message (for testing: 10 sec; replace with 7200000 for 2 hr)
        setTimeout(() => {
            document.getElementById('pips-delay-message').style.display = 'block';
        }, 10000);
    </script>
    <?php
});

// ✅ Hook after order creation to send SMS confirmation
add_action('woocommerce_thankyou', function($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    $name = $order->get_billing_first_name();
    $phone = preg_replace('/\D/', '', $order->get_billing_phone());
    $ref = $order->get_order_number();

    $sms_message = "Dear $name, your order #$ref is received. We'll verify payment within 2 hours. Contact: +91 9876543210.";

    // Example: Fast2SMS API (free tier available)
    $sms_api_url = 'https://www.fast2sms.com/dev/bulkV2';
    $sms_api_key = 'YOUR_FAST2SMS_API_KEY'; // Replace with your real key

    $args = array(
        'body' => array(
            'sender_id' => 'PIPSPW',
            'message' => $sms_message,
            'language' => 'english',
            'route' => 'v3',
            'numbers' => $phone
        ),
        'headers' => array(
            'authorization' => $sms_api_key
        )
    );

    wp_remote_post($sms_api_url, $args);
});

// ✅ Add extra “Payment ID / Reference Number” field to checkout form
add_action('woocommerce_after_order_notes', function($checkout) {
    echo '<div id="payment_reference_field"><h3>Payment Reference ID (UTR / RRN)</h3>';
    woocommerce_form_field('payment_reference', array(
        'type'        => 'text',
        'class'       => array('payment-reference-field form-row-wide'),
        'label'       => 'Enter your bank or UPI transaction reference number',
        'placeholder' => 'e.g., 123456789012',
        'required'    => true,
    ), $checkout->get_value('payment_reference'));
    echo '</div>';
});

// ✅ Save reference field to order meta
add_action('woocommerce_checkout_update_order_meta', function($order_id) {
    if (!empty($_POST['payment_reference'])) {
        update_post_meta($order_id, 'payment_reference', sanitize_text_field($_POST['payment_reference']));
    }
});

// ✅ Display reference in admin order page
add_action('woocommerce_admin_order_data_after_billing_address', function($order){
    $ref = get_post_meta($order->get_id(), 'payment_reference', true);
    if ($ref) {
        echo '<p><strong>Payment Reference:</strong> ' . esc_html($ref) . '</p>';
    }
});
