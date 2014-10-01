<?php

/**
 * Default form fields
 */
function cgit_contact_add_default_form ($forms) {

    $forms[] = array(

        array(
            'type'        => 'text',
            'name'        => 'username',
            'id'          => 'username',
            'label'       => 'Name',
            'required'    => TRUE,
        ),

        array(
            'type'        => 'email',
            'name'        => 'email',
            'id'          => 'email',
            'label'       => 'Email',
            'required'    => TRUE,
        ),

        array(
            'type'        => 'text',
            'name'        => 'subject',
            'id'          => 'subject',
            'label'       => 'Subject',
        ),

        array(
            'type'        => 'textarea',
            'name'        => 'message',
            'id'          => 'message',
            'label'       => 'Message',
            'required'    => TRUE,
        ),

        array(
            'type'        => 'submit',
            'label'       => 'Send Message',
        ),

    );

    return $forms;

}

add_filter('cgit_contact_forms', 'cgit_contact_add_default_form');

/**
 * Default form fields
 */
function cgit_contact_add_debug_form ($forms) {

    $forms['debug'] = array(

        array(
            'type'        => 'text',
            'name'        => 'example_text',
            'id'          => 'example_text',
            'label'       => 'Name',
            'placeholder' => 'Example placeholder',
            'required'    => TRUE,
        ),

        array(
            'type'        => 'email',
            'name'        => 'example_email',
            'id'          => 'example_email',
            'label'       => 'Email',
            'required'    => TRUE,
        ),

        array(
            'type'        => 'url',
            'name'        => 'example_url',
            'id'          => 'example_url',
            'label'       => 'URL',
            'required'    => TRUE,
        ),

        array(
            'type'        => 'tel',
            'name'        => 'example_tel',
            'id'          => 'example_tel',
            'label'       => 'Telephone',
            'required'    => TRUE,
        ),

        array(
            'type'        => 'number',
            'name'        => 'example_number',
            'id'          => 'example_number',
            'label'       => 'Number',
            'required'    => TRUE,
        ),

        array(
            'type'        => 'wrong',
            'name'        => 'example_wrong',
            'id'          => 'example_wrong',
            'label'       => 'Wrong',
        ),

        array(
            'type'        => 'text',
            'id'          => 'example_default',
            'name'        => 'example_default',
            'label'       => 'Default',
            'value'       => 'Example default value',
        ),

        array(
            'type'        => 'textarea',
            'name'        => 'example_textarea',
            'id'          => 'example_textarea',
            'label'       => 'Message',
            'required'    => TRUE,
        ),

        array(
            'type'        => 'checkbox',
            'id'          => 'example_checkbox',
            'label'       => 'Example checkbox group',
            'options'     => array(
                array(
                    'label' => 'Example checkbox 1',
                    'name'  => 'example_checkbox_1',
                    'id'    => 'example_checkbox_1',
                    'value' => 1,
                ),
                array(
                    'label' => 'Example checkbox 2',
                    'name'  => 'example_checkbox_2',
                    'id'    => 'example_checkbox_2',
                    'value' => 2,
                ),
                array(
                    'label' => 'Example checkbox 3',
                    'name'  => 'example_checkbox_3',
                    'id'    => 'example_checkbox_3',
                    'value' => 3,
                ),
            ),
        ),

        array(
            'type'        => 'radio',
            'id'          => 'example_radio',
            'name'        => 'example_radio',
            'label'       => 'Example radio group',
            'options'     => array(
                array(
                    'label' => 'Example radio 1',
                    'id'    => 'example_radio_1',
                    'value' => 1,
                ),
                array(
                    'label' => 'Example radio 2',
                    'id'    => 'example_radio_2',
                    'value' => 2,
                ),
                array(
                    'label' => 'Example radio 3',
                    'id'    => 'example_radio_3',
                    'value' => 3,
                ),
            ),
        ),

        array(
            'type'        => 'select',
            'id'          => 'example_select',
            'name'        => 'example_select',
            'label'       => 'Example select',
            'options'     => array(
                array(
                    'label' => 'Example option 1',
                    'value' => 1,
                ),
                array(
                    'label' => 'Example option 2',
                    'value' => 2,
                ),
                array(
                    'label' => 'Example option 3',
                    'value' => 3,
                ),
            ),
        ),

        array(
            'type'        => 'hidden',
            'name'        => 'example_hidden',
            'value'       => 1,
        ),

        array(
            'type'        => 'html',
            'html'        => '<!-- Arbitrary HTML -->',
        ),

        array(
            'type'        => 'submit',
            'label'       => 'Send Message',
        ),

    );

    return $forms;

}

add_filter('cgit_contact_forms', 'cgit_contact_add_debug_form');
