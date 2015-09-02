<?php

/*

Plugin Name: Castlegate IT WP Contact Form
Plugin URI: http://github.com/castlegateit/cgit-wp-contact-form
Description: Flexible contact form plugin for WordPress.
Version: 1.0.0
Author: Castlegate IT
Author URI: http://www.castlegateit.co.uk/
License: MIT

*/


/**
 * Includes
 */
require_once dirname(__FILE__) . '/contact-form.php';
require_once dirname(__FILE__) . '/templates.php';
require_once dirname(__FILE__) . '/forms.php';


/**
 * WordPress contact form method for use in templates
 *
 * @param integer $form_index     Form array index
 * @param integer $template_index Template array index
 * @param boolean $email_to       Recipient email address
 *
 * @author Andy Reading
 *
 * @return void
 */
function cgit_contact_form($form_index = 0, $template_index = 0, $email_to = false) {

    $contact = new Cgit\ContactForm($form_index, $template_index, $email_to);

    if ($contact->fatal_error()) {
        return $contact->fatal_error();
    }

    return $contact->render();
}


/**
 * WordPress contact form shortcode
 *
 * @param array $attr Shortcode attributes
 *
 * @author John Hughes
 *
 * @return void
 */
function cgit_contact_form_shortcode($atts) {

    $defaults = array(
        'form'     => 0,
        'template' => 0,
        'to'       => get_option('admin_email'),
    );
    $atts = shortcode_atts($defaults, $atts);

    return cgit_contact_form($atts['form'], $atts['template'], $atts['to']);
}
add_shortcode('contact_form', 'cgit_contact_form_shortcode');


/**
 * Notify user if log file is not defined
 */
if (!defined('CGIT_CONTACT_FORM_LOG')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>Contact Form log directory not defined. '
            . 'Please define <code>CGIT_CONTACT_FORM_LOG</code> in <code>'
            . 'wp-config.php</code> using the full path to the log file '
            . 'directory. See <a href="http://github.com/castlegateit/'
            . 'cgit-wp-contact-form">documentation</a> for details.</p></div>';
    });
}
