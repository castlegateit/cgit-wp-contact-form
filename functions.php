<?php

/**
 * Add associative vsprintf
 *
 * Works like vsprintf(), but uses named placeholders that match the keys in an
 * associative array. Placeholders consist of a percent character followed by a
 * string, for example "%example". If an array does not appear to be
 * associative, the native vsprintf() function is used instead.
 */
if ( ! function_exists('cgit_vsprintf') ) {

    function cgit_vsprintf ($str, $args = NULL) {

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

}

/**
 * Escape form input
 */
function cgit_contact_escape ($str) {
    return htmlspecialchars( stripslashes( trim($str) ) );
}

/**
 * Get clean data from POST
 */
function cgit_contact_post ($name, $default = '', $escape = TRUE) {

    $value = isset($_POST[$name]) ? $_POST[$name] : $default;

    if ($escape) {
        $value = cgit_contact_escape($value);
    }

    return $value;

}

/**
 * Check for contact form log directory
 */
function cgit_contact_notice_log () {
    echo '<div class="error"><p>Contact Form log directory not defined. Please define <code>CGIT_CONTACT_FORM_LOG</code> in <code>wp-config.php</code> using the full path to the log file directory. See <a href="http://github.com/castlegateit/cgit-wp-contact-form">documentation</a> for details.</p></div>';
}

/**
 * Check log directory exists
 */
function cgit_contact_notice_log_exists() {
    echo '<div class="error"><p>The log directory <code>CGIT_CONTACT_FORM_LOG</code> does not exist. Please create the directory <code>' . CGIT_CONTACT_FORM_LOG . '</code> with write permissions.</p></div>'


/**
 * Validate format of form fields
 */
function cgit_contact_field_errors ($fields) {

    $error    = array();
    $template = '<pre style="color: #c33;">Contact Form Error: %s</pre>';

    if ( ! is_array($fields) ) {

        $error[] = "Fields must be defined as an array:\n" . print_r($fields, TRUE);

    } else {

        foreach ($fields as $field) {

            if ( ! is_array($field) ) {

                $error[] = "Each field must be defined as an array:\n" . print_r($field, TRUE);

            } elseif ( ! array_key_exists('type', $field) ) {

                $error[] = "Each field must have a type:\n" . print_r($field, TRUE);

            } elseif ( $field['type'] == 'checkbox' ) {

                $required = array('label', 'options');

                if ( count( array_diff( $required, array_keys($field) ) ) != 0 ) {
                    $error[] = "Checkbox fields must include label and options:\n" . print_r($field, TRUE);
                } elseif ( ! is_array($field['options']) ) {
                    $error[] = "Checkbox options must be defined as an array:\n" . print_r($field, TRUE);
                } else {
                    $required = array('name', 'id', 'label', 'value');
                    foreach ($field['options'] as $option) {
                        if ( count( array_diff( $required, array_keys($option) ) ) ) {
                            $error[] = "Checkbox options must include name, id, label, and value:\n" . print_r($option, TRUE);
                        }
                    }
                }

            } elseif ( $field['type'] == 'radio' ) {

                $required = array('name', 'label', 'options');

                if ( count( array_diff( $required, array_keys($field) ) ) != 0 ) {
                    $error[] = "Radio fields must include name, label, and options:\n" . print_r($field, TRUE);
                } elseif ( ! is_array($field['options']) ) {
                    $error[] = "Radio options must be defined as an array:\n" . print_r($field, TRUE);
                } else {
                    $required = array('id', 'label', 'value');
                    foreach ($field['options'] as $option) {
                        if ( count( array_diff( $required, array_keys($option) ) ) ) {
                            $error[] = "\n\Radio options must include id, label, and value:\n" . print_r($option, TRUE);
                        }
                    }
                }

            } elseif ( $field['type'] == 'select' ) {

                $required = array('name', 'id', 'label', 'options');

                if ( count( array_diff( $required, array_keys($field) ) ) != 0 ) {
                    $error[] = "Select fields must include name, id, label, and options:\n" . print_r($field, TRUE);
                } elseif ( ! is_array($field['options']) ) {
                    $error[] = "Select options must be defined as an array:\n" . print_r($field, TRUE);
                } else {
                    $required = array('label', 'value');
                    foreach ($field['options'] as $option) {
                        if ( count( array_diff( $required, array_keys($option) ) ) ) {
                            $error[] = "\n\Select options must include label and value:\n" . print_r($option, TRUE);
                        }
                    }
                }

            } elseif ( ! in_array( $field['type'], array('button', 'hidden', 'html', 'submit') ) ) {

                $required = array('name', 'id', 'label');

                if ( count( array_diff( $required, array_keys($field) ) ) != 0 ) {
                    $type   = ucwords($field['type']);
                    $error[] = "$type fields must include name, id, and label:\n" . print_r($field, TRUE);
                }

            }

        }

    }

    if ( count($error) ) {
        return sprintf( $template, trim( implode("\n", $error) ) );
    }

    return FALSE;

}
