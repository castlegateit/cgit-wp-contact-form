<?php

/*

Plugin Name: Castlegate IT WP Contact Form
Plugin URI: http://github.com/castlegateit/cgit-wp-contact-form
Description: Flexible contact form plugin for WordPress.
Version: 0.1.1
Author: Castlegate IT
Author URI: http://www.castlegateit.co.uk/
License: MIT

*/

/**
 * Includes
 */
require_once dirname( __FILE__ ) . '/functions.php';
require_once dirname( __FILE__ ) . '/templates.php';
require_once dirname( __FILE__ ) . '/forms.php';

/**
 * Check for log file definition
 */
if ( ! defined('CGIT_CONTACT_FORM_LOG') ) {
    add_action('admin_notices', 'cgit_contact_notice_log');
}

/**
 * Display contact form and process form submissions
 */
function cgit_contact_form ($form_id = 0, $template_id = 0, $email_to = FALSE) {

    // Build arrays of forms and templates using filters
    $forms     = apply_filters( 'cgit_contact_forms', array() );
    $templates = apply_filters( 'cgit_contact_templates', array() );

    // Define an additional hidden field to identify the form submitted
    $field_id  = array(
        'type' => 'hidden',
        'name' => "contact_form_$form_id",
    );

    // Select fields and templates
    $fields    = $forms[$form_id];
    $fields[]  = $field_id;

    // Validate form fields
    if ( $fields_error = cgit_contact_field_errors($fields) ) {
        return $fields_error;
    }

    // Apply filter: cgit_contact_fields
    $fields = apply_filters('cgit_contact_fields', $fields);

    // Fill in any gaps in the template array with default values
    $template  = array_replace_recursive( $templates[0], $templates[$template_id] );

    // Check whether form has been submitted (based on hidden identifier field)
    $submitted = isset( $_POST[$field_id['name']] );

    // Assign defaults
    $error     = array();
    $message   = '';

    // If template contains initial message, update message
    if ($template['messages']['initial']) {

        $message_args = array(
            'message' => $template['messages']['initial'],
            'attr' => '',
        );

        $message = cgit_vsprintf( $template['form']['message'], $message_args );

    }

    // If form has been submitted, validate input
    if ($submitted) {

        foreach ($fields as $field) {

            $type     = $field['type'];
            $name     = isset($field['name']) ? $field['name'] : '';
            $required = isset($field['required']) ? $field['required'] : FALSE;
            $value    = cgit_contact_post($name);

            if ($required) {

                if ($value == '') {
                    $error[$name] = $template['messages']['empty'];
                } elseif ( $type == 'email' && ! filter_var($value, FILTER_VALIDATE_EMAIL) ) {
                    $error[$name] = $template['messages']['email'];
                } elseif ( $type == 'url' && ! filter_var($value, FILTER_VALIDATE_URL) ) {
                    $error[$name] = $template['messages']['url'];
                } elseif ( $type == 'tel' && ! preg_match('/^[0-9+,\- ]{6,}$/', $value) ) {
                    $error[$name] = $template['messages']['tel'];
                } elseif ( $type == 'number' && ! preg_match('/^[0-9,\.]+$/', $value) ) {
                    $error[$name] = $template['messages']['number'];
                }

            }

            // Apply filter: cgit_contact_validate
            $error = apply_filters('cgit_contact_validate', $error, $field, $template);

        }

    }

    // If errors, update message
    if ( count($error) ) {

        $message_args = array(
            'message' => $template['messages']['error'],
            'attr' => "class='{$template['form']['error_class']}'",
        );

        $message = cgit_vsprintf( $template['form']['message'], $message_args );

    }

    // If errors or form not submitted, print form
    if ( count($error) || ! $submitted ) {

        $url  = $_SERVER['REQUEST_URI'];
        $form = array();

        // Assemble array for main form template
        $form['attr']    = $template['form']['attr'];
        $form['heading'] = $template['form']['heading'];
        $form['message'] = $message;
        $form['fields']  = '';

        foreach ($fields as $field) {

            // Default field values
            $field['error'] = '';
            $field['value'] = isset($field['value']) ? $field['value'] : '';

            // Define array to hold field attributes
            $attr = array();

            // Add placeholder
            if ( isset($field['placeholder']) ) {
                $attr[] = "placeholder='{$field['placeholder']}'";
            }

            // Add required or optional attributes and messages
            if ( isset($field['required']) && $field['required'] ) {
                $attr[] = 'required';
                $field['required'] = $template['messages']['required'];
            } else {
                $field['required'] = $template['messages']['optional'];
            }

            // Assign attributes to field
            $field['attr'] = implode(' ', $attr);

            // If field has name and has been submitted, check for value and error
            if ( isset($field['name']) && isset($_POST["contact_form_$form_id"])) {

                $field['value'] = cgit_contact_post($field['name']);

                if ( array_key_exists($field['name'], $error) ) {

                    $args = array(
                        'message' => $error[$field['name']],
                        'attr'    => "class='{$template['form']['error_class']}'",
                    );

                    $field['error'] = cgit_vsprintf( $template['form']['error'], $args );

                }

            }

            // Field type: html
            if ( $field['type'] == 'html' ) {

                $form['fields'] .= $field['html'];

            // Field type: select
            } elseif ( $field['type'] == 'select' ) {

                $select = $field;
                $select['options'] = '';

                foreach ($field['options'] as $option) {
                    $option['attr']     = $field['value'] == $option['value'] ? 'selected' : '';
                    $select['options'] .= cgit_vsprintf( $template['fields']['option'], $option );
                }

                $form['fields'] .= cgit_vsprintf( $template['fields']['select'], $select );

            // Field type: radio
            } elseif ( $field['type'] == 'radio' ) {

                $group = $field;
                $group['options'] = '';

                foreach ($field['options'] as $option) {
                    $option['name']    = $group['name'];
                    $option['attr']    = $field['value'] == $option['value'] ? 'checked' : '';
                    $group['options'] .= cgit_vsprintf( $template['fields']['radio'], $option );
                }

                $form['fields'] .= cgit_vsprintf( $template['form']['group'], $group );

            // Field type: checkbox
            } elseif ( $field['type'] == 'checkbox' ) {

                $group = $field;
                $group['options'] = '';

                foreach ($field['options'] as $option) {
                    $option['attr']    = cgit_contact_post($option['name']) == $option['value'] ? 'checked' : '';
                    $group['options'] .= cgit_vsprintf( $template['fields']['checkbox'], $option );
                }

                $form['fields'] .= cgit_vsprintf( $template['form']['group'], $group );

            // Field type: everything else
            } else {

                // If template does not exist, use the text input template
                $type = array_key_exists($field['type'], $template['fields']) ? $field['type'] : 'text';
                $form['fields'] .= cgit_vsprintf( $template['fields'][$type], $field );

            }

        }

        // Assemble form output
        $output = cgit_vsprintf( $template['form']['form'], $form );

        // Apply filter: cgit_contact_form
        $output = apply_filters('cgit_contact_form', $output);

        // Return complete form
        return $output;

    // Form has been submitted and passed validation, so send message
    } else {

        // Assemble success message
        $message_args = array(
            'message' => $template['messages']['success'],
            'attr' => "class='{$template['form']['success_class']}'",
        );

        $message = cgit_vsprintf( $template['form']['message'], $message_args );
        $message = apply_filters('cgit_contact_success', $message);

        // Check recipient email address (default admin)
        $to = html_entity_decode($email_to) ?: get_option('admin_email');
        $to = apply_filters('cgit_contact_email_to', $to, $form_id);

        // Add subject and headers
        $subject = apply_filters('cgit_contact_email_subject', $template['email']['subject'], $form_id);
        $headers = apply_filters('cgit_contact_email_headers', NULL, $form_id);

        // Assemble message body from named and labelled fields (and log file row)
        $body = '';
        $log  = array(
            date('Y-m-d H:i'),
            ( isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '' )
        );

        foreach ($fields as $field) {
            if ( isset($field['name']) && isset($field['label']) ) {
                $label  = $field['label'];
                $value  = cgit_contact_post($field['name']);
                $body  .= "$label: $value\n\n";
                $log[]  = $value;
            } elseif ($field['type'] == 'checkbox') {
                $label  = $field['label'];
                $items  = array();
                foreach ($field['options'] as $option) {
                    if ($item = cgit_contact_post($option['name'])) {
                        $items[] = $item;
                    }
                }
                $value  = implode(', ', $items);
                $body  .= "$label: $value\n\n";
                $log[]  = $value;
            }
        }

        if (isset($_SERVER['REMOTE_ADDR']))
        {
            $body .= "Sent from IP address: " . $_SERVER['REMOTE_ADDR'] . "\n\n";
        }


        $body = cgit_contact_escape($body);
        $body = apply_filters('cgit_contact_email_body', $body, $form_id);

        // Remove unwanted HTML entities
        while ($body != html_entity_decode($body)) {
            $body = html_entity_decode($body);
        }

        // Send email and update message if necessary
        if ( ! wp_mail($to, $subject, $body, $headers) ) {

            $message_args = array(
                'message' => $template['messages']['failure'],
                'attr' => "class='{$template['form']['error_class']}'",
            );

            $message = cgit_vsprintf( $template['form']['message'], $message_args );
            $message = apply_filters('cgit_contact_failure', $message);

        }

        // Write to log file
        if ( defined('CGIT_CONTACT_FORM_LOG') ) {

            $dir = CGIT_CONTACT_FORM_LOG;

            if ( is_file($dir) ) {
                $dir = dirname($dir);
            }

            if ( ! file_exists($dir) ) {
                mkdir($dir, 0777, TRUE);
            }

            $file = fopen("$dir/contact_form_$form_id.csv", 'a');

            fputcsv($file, $log);

        }

        // Return success or failure message
        return $message;

    }

}

/**
 * Contact form shortcode
 */
function cgit_contact_form_shortcode ($atts) {

    $defaults = array(
        'form'     => 0,
        'template' => 0,
        'to'       => get_option('admin_email'),
    );

    $atts = shortcode_atts($defaults, $atts);

    return cgit_contact_form($atts['form'], $atts['template'], $atts['to']);

}

add_shortcode('contact_form', 'cgit_contact_form_shortcode');
