<?php
/*
Plugin Name: WPFunnels Custom Hooks
Description: Custom functionality for WPFunnels including customer_data table creation, email handling and data deletion.
Version: 1.0
Author: asq
*/

// Custom Table Creation when Plugin Activates
function create_customer_data_table() {
    global $wpdb;
    $tableName = $wpdb->prefix . 'customer_data';

    $charsetCollate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $tableName (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        order_id bigint(20) NOT NULL,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charsetCollate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'create_customer_data_table' );

// Function where Customer Data After Checkout is stored
function store_customer_details( $order_id ) {
    $order = wc_get_order( $order_id );
    $customerName = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    $customerEmail = $order->get_billing_email();

    global $wpdb;
    $tableName = $wpdb->prefix . 'customer_data';

    $wpdb->insert(
        $tableName,
        array(
            'name' => $customerName,
            'email' => $customerEmail,
            'order_id' => $order_id,
            'timestamp' => current_time( 'mysql' )
        ),
        array(
            '%s', '%s', '%d', '%s'
        )
    );

    // Check 100 Customers  and send email
    check_and_send_emails();
}
add_action( 'woocommerce_thankyou', 'store_customer_details' );

// Function to Check for 100 Customers and Send Emails
function check_and_send_emails() {
    global $wpdb;
    $tableName = $wpdb->prefix . 'customer_data';

    $customers = $wpdb->get_results( "SELECT * FROM $tableName LIMIT 100", ARRAY_A );

    if ( count( $customers ) === 100 ) {
        foreach ( $customers as $customer ) {
            send_thank_you_email( $customer['email'], $customer['name'], $customer['order_id'] );
        }

        // Delete all the customers after sending emails
        $wpdb->query( "DELETE FROM $tableName LIMIT 100" );
    }
}

// Function for Thank You Email
function send_thank_you_email( $email, $name, $order_id ) {
    $subject = 'Thank you for your purchase!';
    $body = "Dear $name,\n\nThank you for your recent purchase from Fitness Instructor! We’re excited to have you as a customer and we’re confident you’ll love your new fitness guide.\n\nBest regards,\nFitness Instructor";
    $headers = array('Content-Type: text/html; charset=UTF-8');

    wp_mail( $email, $subject, $body, $headers );
}
