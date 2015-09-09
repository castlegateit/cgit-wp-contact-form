# Castlegate IT WP Contact Form #

Castlegate IT WP Contact Form is a flexible contact form plugin for WordPress. It provides functions and shortcodes for inserting contact forms in posts, pages, and templates. It also allows you to defined your own forms, fields, and templates. It requires PHP 5.3.

## Basic usage ##

The plugin comes with a default form and template. The function `cgit_contact_form()` will return a complete, working form that will send to the WordPress admin email address. You can also use the shortcode `[contact_form]` to insert a contact form in the content of any post or page.

The function has three optional arguments. These allow you to choose the form and template and to set the email address this form will send to:

    cgit_contact_form($form_id, $template_id, $email_to);

You can also use these arguments with the shortcode:

    [contact_form form="example" template="example" to="example@example.com"]

The process of adding forms and templates is described below.


## Adding forms and fields ##

You can use the filter `cgit_contact_forms` to add your own forms to the main array of forms. Each form is an array of fields and each field is an array of settings. You should set a unique array key for each form. For example:

    function example_add_form ($forms) {
        $forms['example_form'] = array( /* fields */ );
        return $forms;
    }

    add_filter('cgit_contact_forms', 'example_add_form');

This will add a form called `example_form`. The plugin comes with two forms. The default form, with ID `0`, is a basic contact form and is used when you do not specify a form ID. There is also a `debug` form, which includes examples of all the field types for testing purposes. These can be found in `forms.php`.

## Adding templates ##

The plugin comes with a complete set of templates for forms, fields, and messages. This default template has an ID of `0` and is used where you do not specify a template ID. You can add templates with the `cgit_contact_templates` filter:

    function example_add_template ($templates) {
        $templates['example_template'] = array( /* templates */ );
        return $templates;
    }

    add_filter('cgit_contact_templates', 'example_add_template');

This will add a template called `example_template`. You do not need to define every possible template group and item; the values from the default template will be used where they cannot be found in your custom template. The default template can be found in `templates.php`.

## Sending files ##

The plugin supports sending files as email attachments. It will do this by checking for a "file" field in the form being posted, and then getting file names from $_FILES and grabbing those files from WP_CONTENT_DIR . WP_UPLOADS_DIR.

This makes it necessary to declare WP_UPLOADS_DIR as a subdirectory of WP_CONTENT_DIR. At the time of WP_CONFIG being read, WP_CONTENT_DIR is undefined and cannot be mapped.

As an example:
define('WP_UPLOADS_DIR', '/uploads/my_file_directory/');

## Log files ##

The log file directory is set using the constant `CGIT_CONTACT_FORM_LOG`. This should be a complete path to a directory, ideally below the document root. **This is not set by default.** It is up to you to define this constant in `wp-config.php`. If it is not defined, you will see a warning message at the top of the WordPress admin panel. Note that separate forms will write to separate log files in this directory.

## Field reference ##

When defining a new form, each field is added as an associative array. Basic HTML `input` fields (e.g. `text`, `email`, `url`, `tel`, and `number`) and `textarea` fields, use the same syntax:

    array(
        'type'        => 'text',
        'name'        => 'example',
        'id'          => 'example',
        'label'       => 'Example',
        'placeholder' => 'Example field', // optional
        'required'    => TRUE,            // optional
        'value'       => 'Default value', // optional
    ),

Checkbox, radio, and select fields have a related syntax, using a nested array for each option:

    array(
        'type'    => 'radio',
        'name'    => 'example', // radio and select only
        'id'      => 'example',
        'label'   => 'Example',
        'options' => array(
            array(
                'name' => 'example',  // checkbox only
                'id' => 'example',    // checkbox and radio only
                'label' => 'Example',
                'value' => 'example',
            ),
        ),
    ),

Hidden inputs, buttons, and submit buttons are simpler:

    array(
        'type'  => 'hidden',
        'name'  => 'example',
        'value' => 1,
    ),

    array(
        'type'  => 'button',
        'label' => 'Example',
    ),

    array(
        'type'  => 'submit',
        'label' => 'Send Message',
    ),

You can also add arbitrary HTML. This is not really a form field and will not be processed or sent by the form. However, it does allow additional explanatory text and other elements to be added within the form itself.

    array(
        'type' => 'html',
        'html' => '<!-- example -->',
    ),

The plugin will attempt to validate your fields before generating any output, so it should be difficult to generate an invalid form. Always remember to add a submit field at the end!

## Template reference ##

Templates are divided into four groups: `form`, `fields`, `messages`, and `email`. Each of these is an associative array of template snippets, with placeholders for code that is generated by the plugin. Each placeholder is prefixed with `%` (e.g. `%label`). Here is an example custom template:

    $templates['example_template'] = array(

        'form' => array(
            'heading' => '<h2>Example Contact Form</h2>',
            'attr'    => 'action="example.php" method="get"',
        ),

        'fields' => array(
            'checkbox' => '<label> <input type="checkbox" name="%name" value="%value" /> %label </label>',
            'radio'    => '<label> <input type="radio" name="%name" value="%value" /> %label </label>',
        ),

        'messages' => array(
            'required' => '',
            'optional' => '(optional)',
        ),

    );

Any placeholders that are omitted will not appear in the form. Remember to include the `%required` and `%error` placeholders where relevant. See `templates.php` for a complete example template. If you omit any snippets in your custom template, these default values will be used.

## Filter reference ##

In addition to `cgit_contact_forms` and `cgit_contact_templates`, which are used to define forms and templates, the form data and output can be edited using various [filters](http://codex.wordpress.org/Function_Reference/add_filter):

*   `cgit_contact_fields` filters the complete array of fields defined for the form.
*   `cgit_contact_validate` allows additional validation. Its arguments are the array of errors `$error`, the field data for the individual field `$field`, and the template array `$template`.
*   `cgit_contact_form` filters the final HTML form output.
*   `cgit_contact_success` filters the success message HTML.
*   `cgit_contact_failure` filters the failure message HTML.
*   `cgit_contact_to` filters the email address the form sends to.
*   `cgit_contact_subject` filters the subject (originally set in the template).
*   `cgit_contact_headers` filters the email headers (blank by default).
*   `cgit_contact_body` filters the email body.
