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

    $contact = new cgit_contact_form($form_index, $template_index, $email_to);

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
 * Contact form class
 *
 * @author Andy Reading
 * @author John Hughes
 */
class cgit_contact_form
{
    /**
     * Available forms
     * @var array
     */
    private $forms = array();

    /**
     * Available templates
     * @var array
     */
    private $template = array();

    /**
     * Current form array index
     * @var string
     */
    private $form_index = '';

    /**
     * Current template array index
     * @var string
     */
    private $template_index = '';

    /**
     * Recipient email address
     * @var string
     */
    private $email = '';

    /**
     * Fatal errors
     * @var array
     */
    private $fatal_errors = array();

    /**
     * Form fields
     * var array
     */
    private $fields = array();

    /**
     * Form error messages
     * @var array
     */
    private $errors = array();

    /**
     * Form message
     * @var string
     */
    private $message = '';

    // -------------------------------------------------------------------------

    /**
     * Set member variables and run initialisation functions
     *
     * @param mixed  $form_index     Form array index to use
     * @param mixed  $template_index Template array index to use
     * @param string $email          Recipient email address
     *
     * @author Andy Reading
     *
     * @return void
     */
    public function __construct($form_index, $template_index, $email)
    {
        // Set core member variables
        $this->form_index = $form_index;
        $this->template_index = $template_index;
        $this->email = $email;

        // Initialise
        $this->initalise();
        $this->setup_fields();
        $this->setup_templates();
    }

    // -------------------------------------------------------------------------

    /**
     * Set up admin notices for undefined log constants, and apply filters to
     * populate the form and template arrays
     *
     * @author John Hughes
     *
     * @return void
     */
    private function initalise()
    {
        // If the contact form constant has not been set, add an admin notice
        if (!defined('CGIT_CONTACT_FORM_LOG')) {
            add_action(
                'admin_notices',
                'cgit_contact_form::setup_logfile'
            );
        }

        // Build arrays of forms and templates using filters
        $this->forms = apply_filters('cgit_contact_forms', array());
        $this->templates = apply_filters('cgit_contact_templates', array());

        // Check whether form has been submitted (based on hidden field)
        $this->submitted = isset($_POST['contact_form_' . $form_index]);
    }

    // -------------------------------------------------------------------------

    /**
     * Load the correct from the form array and populate the field array. Create
     * a hidden form field for use in identifying the submitted form.
     *
     * @author Andy Reading
     * @author John Hughes
     *
     * @return void
     */
    public function setup_fields()
    {
        // Check that the requested form exists
        if (!isset($this->forms[$this->form_index])) {
            $this->fatal_error(
                'The form `' . $this->form_index . '` does not exist'
            );
        }
        else {
            // Get the appropriate fields
            $this->fields = $this->forms[$this->form_index];

            // Define an additional hidden field to identify the form submitted
            $this->fields[] = array(
                'type' => 'hidden',
                'name' => "contact_form_" . $form_index,
            );

            // Validate form fields
            $this->validate_defined_fields();

            // Apply filter: cgit_contact_fields
            $this->fields = apply_filters('cgit_contact_fields', $this->fields);
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Checks that the requested template exists and ensures that any missing
     * values are populated from the default template.
     *
     * @author John Hughes
     * @author Andy Reading
     *
     * @return void
     */
    private function setup_templates()
    {
        // Check that the requested template exists

        if (!isset($this->templates[$this->template_index])) {
            $this->fatal_error(
                'The template `' . $this->template_index . '` does not exist'
            );
        }
        else {
            /**
             * Fill in any gaps in user created template arrays by merging with
             * the default template
             */
            $this->templates[$this->template_index] = array_replace_recursive(
                $this->templates[0],
                $this->templates[$this->template_index]
            );

            // If template contains initial message, update message
            if ($this->templates[$this->template_index]['messages']['initial']) {

                $message_args = array(
                    'message' => $template['messages']['initial'],
                    'attr' => '',
                );

                $msg = $this->templates[$this->template_index]['form']['message'];
                $this->message = $this->vsprintf($msg, $message_args);
            }
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Validate user defined fields. Checks fields have required attributes and
     * correctly formatted options.
     *
     * @author John Hughes
     *
     * @return void
     */
    private function validate_defined_fields()
    {
        if (!is_array($this->fields)) {

            $this->fatal_error(
                "Fields must be defined as an array:\n"
                . print_r($this->fields, true)
            );

        }
        else {

            $other_fields = array(
                'button',
                'hidden',
                'html',
                'submit'
            );

            foreach ($this->fields as $field) {

                if (!is_array($field)) {

                     // Fields must be defined in an array
                    $this->fatal_error(
                        "Each field must be defined as an array:\n"
                        . print_r($field, true)
                    );

                }
                elseif (!array_key_exists('type', $field)) {

                    // Fields must have a type
                    $this->fatal_error(
                        "Each field must have a type:\n"
                        . print_r($field, true)
                    );

                }
                elseif ($field['type'] == 'checkbox') {

                    // Checkbox fields must have a label and options
                    $required = array('label', 'options');
                    $chck = array_diff($required, array_keys($field));

                    if (count($chck) != 0 ) {

                        $this->fatal_error(
                            "Checkbox fields must include label and options:\n"
                            . print_r($field, true)
                        );

                    }
                    elseif (!is_array($field['options'])) {

                        // Checkbox options must be an array
                        $this->fatal_error(
                            "Checkbox options must be defined as an array:\n"
                            . print_r($field, true)
                        );

                    }
                    else {

                        // Checkbox options must include a label and value
                        $required = array('label', 'value');

                        foreach ($field['options'] as $option) {

                            $chck = array_diff($required, array_keys($option));
                            if (count($chck)) {
                                $this->fatal_error(
                                    "Checkbox options must include a label and"
                                    . " value:\n" . print_r($option, true)
                                );
                            }
                        }
                    }

                }
                elseif ($field['type'] == 'radio') {

                    $required = array('label', 'options');
                    $chck = array_diff($required, array_keys($field));

                    if (count($chck) != 0) {

                        // Radio fields must include a label and options
                        $this->fatal_error(
                            "Radio fields must include name, label, and "
                            . "options:\n" . print_r($field, true)
                        );

                    }
                    elseif (!is_array($field['options'])) {

                        // Radio fields must have an array of options
                        $this->fatal_error(
                            "Radio options must be defined as an array:\n"
                            . print_r($field, true)
                        );

                    }

                }
                elseif ($field['type'] == 'select') {

                    $required = array('label', 'options');
                    $chck = array_diff($required, array_keys($field));

                    if (count($chck) != 0) {

                        // Select fields must have a label and options
                        $this->fatal_error(
                            "Select fields must include a label and options:\n"
                            . print_r($field, true)
                        );

                    }
                    elseif (!is_array($field['options'])) {

                        $this->fatal_error(
                            "Select options must be defined as an array:\n"
                            . print_r($field, true)
                        );

                    }

                }
                elseif (!in_array($field['type'], $other_fields)) {

                    $required = array('label');
                    $chck = array_diff($required, array_keys($field));

                    if (count($chck) != 0) {
                        $type = ucwords($field['type']);
                        $this->fatal_error(
                            $type . " fields must include a label:\n"
                            . print_r($field, true)
                        );
                    }
                }
            }
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Outputs a WordPress admin notification to be used in a WordPress filter
     *
     * @author John Hughes
     *
     * @return void
     */
    public static function setup_logfile()
    {
        echo '<div class="error"><p>Contact Form log directory not defined. '
            . 'Please define <code>CGIT_CONTACT_FORM_LOG</code> in <code>'
            . 'wp-config.php</code> using the full path to the log file '
            . 'directory. See <a href="http://github.com/castlegateit/'
            . 'cgit-wp-contact-form">documentation</a> for details.</p></div>';
    }

    // -------------------------------------------------------------------------

    /**
     * Sets or returns any fatal errors that will prevent the display or usage
     * of the contact form
     *
     * @param mixed $message
     *
     * @author Andy Reading
     *
     * @return mixed
     */
    public function fatal_error($message = null)
    {
        if ($message) {
            $this->fatal_errors[] = $message;
        }
        else {
            $output = '<p style="color: #c00">';
            $output.= 'CGIT Contact Form Error:';
            $output.= '</p>';
            $output.= '<ul style="color: #c00">';
            foreach ($this->fatal_errors as $error) {
                $output.= '    <li>' . $error . '</li>';
            }
            $output.= '</ul>';

            return count($this->fatal_errors) ? $output : '';
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Validate the user input against standard validation rules and apply
     * filters for custom validation.
     *
     * @author John Hughes
     * @author Andy Reading
     *
     * @return [type] [description]
     */
    public function validate_input()
    {
        foreach ($this->fields as $field) {

            $type = $field['type'];
            $name = isset($field['name']) ? $field['name'] : '';
            $required = isset($field['required']) ? $field['required'] : false;
            $value = $this->post($name);

            if ($required) {

                if ($value == '') {
                    $this->errors[$name] = $this->template_message('empty');
                }
                elseif ($type == 'email'
                    && !filter_var($value, FILTER_VALIDATE_EMAIL)
                ) {
                    $this->errors[$name] = $this->template_message('email');
                }
                elseif ($type == 'url'
                    && !filter_var($value, FILTER_VALIDATE_URL)
                ) {
                    $this->errors[$name] = $this->template_message('url');
                }
                elseif ($type == 'tel'
                    && !preg_match('/^[0-9+,\- ]{6,}$/', $value)
                ) {
                    $this->errors[$name] = $this->template_message('tel');
                }
                elseif ($type == 'number'
                    && !preg_match('/^[0-9,\.]+$/', $value)
                ) {
                    $this->errors[$name] = $this->template_message('number');
                }

            }

            // Apply filter: cgit_contact_validate
            $this->errors = apply_filters(
                'cgit_contact_validate',
                $this->errors,
                $field,
                $this->templates[$this->template_index]
            );
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Returns a template message
     *
     * @author Andy Reading
     *
     * @return string
     */
    private function template_message($type)
    {
        return $this->templates[$this->template_index]['messages'][$type];
    }

    // -------------------------------------------------------------------------

    /**
     * Returns a cleaned posted value
     *
     * @param string $name Field name
     *
     * @author John Hughes
     *
     * @return string
     */
    private function post($name)
    {
        $value = isset($_POST[$name]) ? $_POST[$name] : $default;

        if ($escape) {
            $value = $this->escape($value);
        }

        return $value;
    }

    // -------------------------------------------------------------------------

    /**
     * Escapes a string
     *
     * @param string $name Field name
     *
     * @author John Hughes
     *
     * @return string
     */
    private function escape()
    {
        return htmlspecialchars(stripslashes(trim($str)));
    }

    // -------------------------------------------------------------------------

    /**
     * Parses and returns a template string
     *
     * @param string $str Template string
     * @param mixed $args Arguments
     *
     * @author John Hughes
     *
     * @return string
     */
    function vsprintf($str, $args = NULL) {

        // If no arguments, return unmodified string
        if ( empty($args) ) {
            return $str;
        }

        // If array looks non-associative, use vsprintf
        if ( array_keys($args) === range( 0, count($args) - 1 ) ) {
            return vsprintf($str, $args);
        }

        // Replace placeholders with values
        foreach ($args as $key => $value) {
            $str = str_replace("%$key", $value, $str);
        }

        return $str;
    }

    // -------------------------------------------------------------------------

    /**
     * Renders the form, checks validation and triggers logging and the final
     * email when submitted and valid
     *
     * @author John Hughes
     *
     * @return string
     */
    public function render()
    {
        // Load the template
        $template = $this->templates[$this->template_index];

        // If form has been submitted, validate input
        if ($this->submitted) {

            $this->validate_input();

            // If there are validation errors, update message
            if (count($this->errors)) {

                $message_args = array(
                    'message' => $this->template_message('error'),
                    'attr' => "class='{$template['form']['error_class']}'",
                );

                $message = $this->vsprintf(
                    $this->templates[$this->template_index]['form']['message'],
                    $message_args
                );

            }
        }

        // If errors or form not submitted, print form
        if (count($this->errors) || !$this->submitted) {


            // Assemble form output
            $form = array();
            $form['attr'] = "action='$url' method='post'";
            $form['heading'] = $template['form']['heading'];
            $form['message'] = $this->message;
            $form['fields'] = '';

            $url  = $_SERVER['REQUEST_URI'];

            foreach ($this->fields as $field) {

                // Default field values
                $field['error'] = '';
                $field['value'] = isset($field['value']) ? $field['value'] : '';

                // Define array to hold field attributes
                $attr = array();

                // Add placeholder
                if (isset($field['placeholder']) ) {
                    $attr[] = "placeholder='{$field['placeholder']}'";
                }

                // Add required or optional attributes and messages
                if (isset($field['required'])
                    && $field['required']
                    && $template['form']['html5_validation']
                ) {
                    $attr[] = 'required';
                    $field['required'] = $this->template_message('required');
                } else {
                    $field['required'] = $this->template_message('optional');
                }

                // Assign attributes to field
                $field['attr'] = implode(' ', $attr);

                /**
                 * If field has name and has been submitted, check for value
                 * and error
                 */
                if (isset($field['name']) && isset($_POST[$field['name']])) {

                    $field['value'] = $this->post($field['name']);

                    if (array_key_exists($field['name'], $this->errors) ) {

                        $args = array(
                            'error' => $this->errors[$field['name']],
                            'attr' => "class='{$template['form']['error_class']}'",
                        );

                        $field['error'] = $this->vsprintf(
                            $template['form']['error'],
                            $args
                        );
                    }

                }

                // Field type: html
                if ($field['type'] == 'html' ) {

                    $form['fields'].= $field['html'];

                // Field type: select
                }
                elseif ($field['type'] == 'select' ) {

                    $select = $field;
                    $select['options'] = '';

                    foreach ($field['options'] as $option) {

                        $option['attr'] = '';
                        if ($field['value'] == $option['value']) {
                            $option['attr'] = 'selected';
                        }

                        $select['options'].= $this->vsprintf(
                            $template['fields']['option'],
                            $option
                        );
                    }

                    $form['fields'].= $this->vsprintf(
                        $template['fields']['select'],
                        $select
                    );

                }
                elseif ($field['type'] == 'radio') {

                    // Field type: radio
                    $group = $field;
                    $group['options'] = '';

                    foreach ($field['options'] as $option) {
                        $option['name'] = $group['name'];

                        $option['attr'] = '';
                        if ($field['value'] == $option['value']) {
                            $option['attr'] = 'checked';
                        }

                        $group['options'].= $this->vsprintf(
                            $template['fields']['radio'],
                            $option
                        );
                    }

                    $form['fields'].= $this->vsprintf(
                        $template['form']['group'],
                        $group
                    );

                // Field type: checkbox
                }
                elseif ($field['type'] == 'checkbox') {

                    $group = $field;
                    $group['options'] = '';

                    foreach ($field['options'] as $option) {

                        $option['attr'] = '';
                        if ($this->post($option['name']) == $option['value']) {
                            $option['attr'] = 'checked';
                        }

                        $group['options'].= $this->vsprintf(
                            $template['fields']['checkbox'],
                            $option
                        );
                    }

                    $form['fields'].= $this->vsprintf(
                        $template['form']['group'],
                        $group
                    );

                }
                else {
                    // Field type: everything else

                    // If template does not exist, use the text input template
                    $type = 'text';
                    if (array_key_exists($field['type'], $template['fields'])) {
                        $type = $field['type'];
                    }

                    $form['fields'] .= $this->vsprintf(
                        $template['fields'][$type],
                        $field
                    );

                }

            }

            $output = $this->vsprintf($template['form']['form'], $form);

            // Apply filter: cgit_contact_form
            $output = apply_filters('cgit_contact_form', $output);

            // Return complete form
            return $output;
        }
        else {

            // Assemble success message
            $message_args = array(
                'message' => $template['messages']['success'],
                'attr' => "class='{$template['form']['success_class']}'",
            );

            $message = $this->vsprintf(
                $template['form']['message'],
                $message_args
            );
            $message = apply_filters('cgit_contact_success', $message);

            // Success - send email
            $this->send_email();

            // Write to log file if the constant is correctly defined
            if (defined('CGIT_CONTACT_FORM_LOG')) {
                $this->write_log();
            }

            // Return success or failure message
            return $message;
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Send the final email
     *
     * @author John Hughes
     *
     * @return void
     */
    public function send_email()
    {
        // Check recipient email address (default admin)
        $to = html_entity_decode($this->email) ?: get_option('admin_email');
        $to = apply_filters('cgit_contact_email_to', $to, $this->form_index);

        // Apply subject filter
        $subject = apply_filters(
            'cgit_contact_email_subject',
            $template['email']['subject'],
            $form_index
        );

        // Apply header filter
        $headers = apply_filters(
            'cgit_contact_email_headers',
            null,
            $this->form_index
        );

        // Assemble message body from named and labelled fields
        $body = '';
        foreach ($this->fields as $field) {

            if (isset($field['name']) && isset($field['label'])) {
                $label = $field['label'];
                $value = $this->post($field['name']);
                $body .= "$label: $value\n\n";
            } elseif ($field['type'] == 'checkbox') {
                $label  = $field['label'];
                $items  = array();
                foreach ($field['options'] as $option) {
                    if ($item = $this->post($option['name'])) {
                        $items[] = $item;
                    }
                }
                $value = implode(', ', $items);
                $body.= "$label: $value\n\n";
            }
        }

        // Include the sender IP address in the email body
        if (isset($_SERVER['REMOTE_ADDR']))
        {
            $body .= "Sent from IP address: " . $_SERVER['REMOTE_ADDR'] . "\n\n";
        }

        // Escape the body content and apply the body filter
        $body = $this->escape($body);
        $body = apply_filters('cgit_contact_email_body', $body, $form_index);

        // Remove unwanted HTML entities
        while ($body != html_entity_decode($body)) {
            $body = html_entity_decode($body);
        }

        // Send email and update message if necessary
        if (!wp_mail($to, $subject, $body, $headers)) {

            $message_args = array(
                'message' => $this->template_message('failure'),
                'attr' => "class='{$template['form']['error_class']}'",
            );

            $message = $this->vsprintf(
                $template['form']['message'],
                $message_args
            );

            $message = apply_filters('cgit_contact_failure', $message);
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Write to the log file
     *
     * @author John Hughes
     *
     * @return void
     */
    public function write_log()
    {
        // Basic columns first
        $log  = array(
            date('Y-m-d H:i'),
            (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '')
        );

        foreach ($this->fields as $field) {

            if (isset($field['name']) && isset($field['label'])) {
                $log[] = $this->post($field['name']);
            }
            elseif ($field['type'] == 'checkbox') {
                $items  = array();
                foreach ($field['options'] as $option) {
                    if ($item = $this->post($option['name'])) {
                        $items[] = $item;
                    }
                }
                $log[] = implode(', ', $items);
            }
        }

        $dir = CGIT_CONTACT_FORM_LOG;

        if ( is_file($dir) ) {
            $dir = dirname($dir);
        }

        if ( ! file_exists($dir) ) {
            mkdir($dir, 0777, true);
        }

        $file = fopen($dir . "/contact_form_" . $this->form_index . ".csv", 'a');
        fputcsv($file, $log);
    }

    // -------------------------------------------------------------------------

}