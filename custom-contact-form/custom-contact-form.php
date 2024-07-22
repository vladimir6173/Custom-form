<?php
/**
 * Plugin Name: Custom Contact Form
 * Description: A custom contact form plugin with email validation, HubSpot integration, and logging.
 * Version: 1.0
 * Author: Vladimir
 */

if (!defined('ABSPATH')) {
    exit;
}

function custom_contact_form_shortcode() {
    ob_start();
    custom_contact_form_template();
    return ob_get_clean();
}
add_shortcode('custom_contact_form', 'custom_contact_form_shortcode');

function custom_contact_form_template() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['custom_contact_form'])) {
        handle_form_submission();
    }
    ?>
    <form action="" method="post">
        <label for="first-name">First Name</label>
        <input type="text" id="first-name" name="first-name" required>
        <br>
        <label for="last-name">Last Name</label>
        <input type="text" id="last-name" name="last-name" required>
        <br>
        <label for="subject">Subject</label>
        <input type="text" id="subject" name="subject" required>
        <br>
        <label for="message">Message</label>
        <textarea id="message" name="message" required></textarea>
        <br>
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required>
        <br>
        <input type="hidden" name="custom_contact_form" value="1">
        <input type="submit" value="Send">
    </form>
    <?php
}

function handle_form_submission() {
    $first_name = sanitize_text_field($_POST['first-name']);
    $last_name = sanitize_text_field($_POST['last-name']);
    $subject = sanitize_text_field($_POST['subject']);
    $message = sanitize_textarea_field($_POST['message']);
    $email = sanitize_email($_POST['email']);

    if (!is_valid_email($email)) {
        echo '<p class="error">Invalid email address.</p>';
        return;
    }

    echo '<p class="success">Email sent successfully!!!</p>';
    add_contact_to_hubspot($first_name, $last_name, $email, $subject, $message);
    log_message($first_name, $last_name, $subject, $message, $email);
}

function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function add_contact_to_hubspot($first_name, $last_name, $email, $subject, $message) {
    $access_token = 'token';
    $endpoint = 'https://api.hubapi.com/contacts/v1/contact/';

    $data = array(
        'properties' => array(
            array('property' => 'email', 'value' => $email),
            array('property' => 'firstname', 'value' => $first_name),
            array('property' => 'lastname', 'value' => $last_name),
            array('property' => 'subject', 'value' => $subject),
            array('property' => 'message', 'value' => $message)
        )
    );

    $args = array(
        'body' => json_encode($data),
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $access_token
        )
    );

    $response = wp_remote_post($endpoint, $args);

    if (is_wp_error($response)) {
        error_log('HubSpot API request failed: ' . $response->get_error_message());
    } else {
        error_log('HubSpot API request successful.');
    }
}

function log_message($first_name, $last_name, $subject, $message, $email) {
    $log = fopen(plugin_dir_path(__FILE__) . 'contact-form-log.txt', 'a');
    $log_entry = sprintf(
        "[%s] First Name: %s, Last Name: %s, Subject: %s, Message: %s, Email: %s\n",
        date('Y-m-d H:i:s'), $first_name, $last_name, $subject, $message, $email
    );
    fwrite($log, $log_entry);
    fclose($log);
}
?>
