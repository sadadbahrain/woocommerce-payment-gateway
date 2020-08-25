<?php
/*
 * Plugin Name: SADDED By SADAD
 * Plugin URI: https://sadadbahrain.com
 * Description: SADDED by SADAD Bahrain for card payments
 * Author: Danial Jawaid
 * Author URI: https://www.linkedin.com/in/danial-jawaid-4b527835/
 * Version: 0.2
 *
 * License: GNU General Public License v3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

add_filter('woocommerce_payment_gateways', 'sadad_add_gateway_class');
function sadad_add_gateway_class($gateways)
{
    $gateways[] = 'WC_SADAD_Gateway'; // your class name is here
    return $gateways;
}

add_action('plugins_loaded', 'sadad_init_gateway_class');
function sadad_init_gateway_class()
{

    class WC_SADAD_Gateway extends WC_Payment_Gateway
    {

        public function __construct()
        {

            $this->id = 'sadded';
            $this->icon = 'https://www.sadadbahrain.com/img/logo2.png';
            $this->has_fields = true;
            $this->method_title = 'SADDED By SADAD Bahrain';
            $this->method_description = 'SADAD Bahrain payment gateway for Debit and Credit cards.';

            $this->supports = array(
                'products'
            );

            $this->init_form_fields();

            $this->init_settings();
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->testmode = 'yes' === $this->get_option('testmode');
            $this->private_key = $this->testmode ? $this->get_option('test_private_key') : $this->get_option('private_key');
            $this->publishable_key = $this->testmode ? $this->get_option('test_publishable_key') : $this->get_option('publishable_key');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                $this,
                'process_admin_options'
            ));

            add_action('wp_enqueue_scripts', array(
                $this,
                'payment_scripts'
            ));
			
			add_action( 'woocommerce_api_sadded_payment_success', array( $this, 'sadded_payment_success' ) );
			add_action( 'woocommerce_api_sadded_payment_failure', array( $this, 'sadded_payment_failure' ) );

        }

        public function init_form_fields()
        {

            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Enable/Disable',
                    'label' => 'Enable Gateway',
                    'type' => 'checkbox',
                    'description' => 'Pay with your credit card or debit card with SADAD Bahrain.',
                    'default' => 'no'
                ) ,
                'title' => array(
                    'title' => 'Title',
                    'type' => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default' => 'SADDED By SADAD',
                    'desc_tip' => true,
                ) ,
                'description' => array(
                    'title' => 'Description',
                    'type' => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default' => 'Pay with your credit card or debit card with SADAD Bahrain.',
                ) ,
                'testmode' => array(
                    'title' => 'Test mode',
                    'label' => 'Enable Test Mode',
                    'type' => 'checkbox',
                    'description' => 'Place the payment gateway in test mode using test API keys.',
                    'default' => 'yes',
                    'desc_tip' => true,
                ) ,
                'secure' => array(
                    'title' => 'SSL Secure',
                    'label' => 'Enable secure or unsecure communication',
                    'type' => 'checkbox',
                    'description' => 'Enables SSL verification of API calls.',
                    'default' => 'yes',
                    'desc_tip' => true,
                ) ,
                'api_key' => array(
                    'title' => 'Api Key',
                    'type' => 'text'
                ) ,
                'vendor_id' => array(
                    'title' => 'Vendor ID',
                    'type' => 'text',
                ) ,
                'branch_id' => array(
                    'title' => 'Branch ID',
                    'type' => 'text',
                ) ,
                'terminal_id' => array(
                    'title' => 'Terminal ID',
                    'type' => 'text',
                )
            );

        }

        /**
         * You will need it if you want your custom credit card form, Step 4 is about it
         */
        public function payment_fields()
        {
            if($this->description) echo wpautop(wptexturize($this->description));
        }

        /*
         * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
        */
        public function payment_scripts()
        {

        }

        /*
         * Fields validation, more in Step 5
        */
        public function validate_fields()
        {

            if (empty(sanitize_text_field($_POST['billing_first_name'])))
            {
                wc_add_notice('First name is required!', 'error');
                return false;
            }
            return true;

        }

        /*
         * We're processing the payments here, everything about it is in Step 5
        */
        public function process_payment($order_id)
        {

            global $woocommerce;
            $date = new DateTime;
            $order = wc_get_order($order_id);
            $total = $order->get_total();
            $order_data = $order->get_data(); // The Order data
            $order_id = $order_data['id'];
            $order_parent_id = $order_data['parent_id'];
            $order_status = $order_data['status'];
            $order_currency = $order_data['currency'];
            $order_version = $order_data['version'];
            $order_payment_method = $order_data['payment_method'];
            $order_payment_method_title = $order_data['payment_method_title'];
            $order_payment_method = $order_data['payment_method'];
            $order_payment_method = $order_data['payment_method'];

            $order_date_created = $order_data['date_created']->date('Y-m-d H:i:s');
            $order_date_modified = $order_data['date_modified']->date('Y-m-d H:i:s');

            // Using a timestamp ( with php getTimestamp() function as method)
            $order_timestamp_created = $order_data['date_created']->getTimestamp();
            $order_timestamp_modified = $order_data['date_modified']->getTimestamp();

            $order_discount_total = $order_data['discount_total'];
            $order_discount_tax = $order_data['discount_tax'];
            $order_shipping_total = $order_data['shipping_total'];
            $order_shipping_tax = $order_data['shipping_tax'];
            $order_total_cart_tax = $order_data['cart_tax'];
            $order_total = $order_data['total'];
            $order_total_tax = $order_data['total_tax'];
            $order_customer_id = $order_data['customer_id']; // ... and so on
            $order_total = $order_data['total'];

            ## BILLING INFORMATION:
            $order_billing_first_name = $order_data['billing']['first_name'];
            $order_billing_last_name = $order_data['billing']['last_name'];
            $order_billing_company = $order_data['billing']['company'];
            $order_billing_address_1 = $order_data['billing']['address_1'];
            $order_billing_address_2 = $order_data['billing']['address_2'];
            $order_billing_city = $order_data['billing']['city'];
            $order_billing_state = $order_data['billing']['state'];
            $order_billing_postcode = $order_data['billing']['postcode'];
            $order_billing_country = $order_data['billing']['country'];
            $order_billing_email = $order_data['billing']['email'];
            $order_billing_phone = $order_data['billing']['phone'];

            ## SHIPPING INFORMATION:
            $order_shipping_first_name = $order_data['shipping']['first_name'];
            $order_shipping_last_name = $order_data['shipping']['last_name'];
            $order_shipping_company = $order_data['shipping']['company'];
            $order_shipping_address_1 = $order_data['shipping']['address_1'];
            $order_shipping_address_2 = $order_data['shipping']['address_2'];
            $order_shipping_city = $order_data['shipping']['city'];
            $order_shipping_state = $order_data['shipping']['state'];
            $order_shipping_postcode = $order_data['shipping']['postcode'];
            $order_shipping_country = $order_data['shipping']['country'];

            $this->init_form_fields();
            $this->init_settings();
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->testmode = 'yes' === $this->get_option('testmode');
            $this->api_key = $this->get_option('api_key');
            $this->vendor_id = $this->get_option('vendor_id');
            $this->branch_id = $this->get_option('branch_id');
            $this->terminal_id = $this->get_option('terminal_id');
            $this->secure = $this->get_option('secure');
            $orderIdString = '?orderId=' . $order_id;
            $data = array(
                'api-key' => $this->api_key,
                'vendor-id' => $this->vendor_id,
                'branch-id' => $this->branch_id,
                'terminal-id' => $this->terminal_id,
                'email' => $order_billing_email,
                'customer-name' => $order_billing_first_name . " " . $order_billing_last_name,
                'amount' => strval($total) ,
                'description' => $order_id,
                'date' => $date->format('Y-m-d H:i:s') ,
                "External-reference" => $order_id,
                'notification-mode' => '300',
                'success-url' => site_url() . '/wc-api/sadded_payment_success/' . $orderIdString,
                'error-url' => site_url() . '/wc-api/sadded_payment_failure/' . $orderIdString,

            );

            $args = array(
                'body' => json_encode($data) ,
                'timeout' => '5',
                'redirection' => '5',
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(
                    'Content-Type' => 'application/json; charset=utf-8'
                ) ,
                'cookies' => array() ,
                'method' => 'POST',
                'data_format' => 'body',
            );
            $apiurl = ($this->testmode ? "https://eps-net-uat.sadadbh.com" : "https://eps-net.sadadbh.com") . "/api/v2/web-ven-sdd/epayment/create/";
            if ($this->secure == "no"){
                add_filter( 'https_ssl_verify', '__return_false' );
            }
            $response = wp_remote_post($apiurl, $args);
			$response_body = json_decode(wp_remote_retrieve_body($response), true);
            return array(
                'result' => 'success',
                'redirect' => $response_body['payment-url'],
                'messages' => str_replace("BD ", "", strval($total)) ,
            );
            exit();

        }

        public function sadded_payment_success()
        {
            // Getting POST data
            $postData = file_get_contents('php://input');
            $response = json_decode($postData);
            $orderId = sanitize_text_field($_GET['orderId']);
            $order = wc_get_order($orderId);

            if ($order)
            {
                $order->payment_complete();
                $order->reduce_order_stock();
                $order->update_status('processing');

				wp_safe_redirect($this->get_return_url($order));
            }
        }

        public function sadded_payment_failure()
        {
            // Getting POST data
            $postData = file_get_contents('php://input');
            $response = json_decode($postData);
            $orderId = sanitize_text_field($_GET['orderId']);
            $order = wc_get_order($orderId);

            if ($order)
            {
                $order->update_status('failed');
            }
			wc_add_notice(__('Payment failed.', 'gateway') , 'error');
			wp_safe_redirect(wc_get_page_permalink('checkout'));
        }
    }
}

