<?php

/**
 * Default form template
 */
function cgit_contact_add_default_template ($templates) {

    $templates[] = array(

        'form' => array(
            'form'          => '<form %attr> %heading %message %fields </form>',
            'heading'       => '<h2>Contact</h2>',
            'message'       => '<p %attr>%message</p>',
            'group'         => '<p>%label %required %error %options</p>',
            'error'         => '<span %attr>%message</span>',
            'error_class'   => 'error',
            'success_class' => 'success',
        ),

        'fields' => array(
            'text'     => '<p><label for="%id">%label %required</label> <input type="%type" name="%name" id="%id" value="%value" %attr /> %error</p>',
            'textarea' => '<p><label for="%id">%label %required</label> <textarea name="%name" id="%id" %attr>%value</textarea> %error</p>',
            'checkbox' => '<input type="checkbox" name="%name" id="%id" value="%value" %attr /> <label for="%id">%label</label>',
            'radio'    => '<input type="radio" name="%name" id="%id" value="%value" %attr /> <label for="%id">%label</label>',
            'hidden'   => '<input type="hidden" name="%name" value="%value" />',
            'select'   => '<p><label for="%id">%label %required</label> <select name="%name" id="%id" %attr>%options</select> %error</p>',
            'option'   => '<option value="%value" %attr>%label</option>',
            'button'   => '<p><button type="%type">%label</button></p>',
            'submit'   => '<p><button>%label</button></p>',
        ),

        'messages' => array(
            'required' => '<span class="required">*</span>',
            'optional' => '',
            'initial'  => '',
            'success'  => 'Your message has been sent. Thank you.',
            'failure'  => 'There was a problem sending your message. Please try again later.',
            'error'    => 'Your message contains errors. Please correct them and try again.',
            'empty'    => 'This is a required field',
            'email'    => 'Please enter a valid email address',
            'url'      => 'Please enter a valid URL',
            'tel'      => 'Please enter a valid telephone number',
            'number'   => 'Please enter a valid number',
        ),

        'email' => array(
            'subject' => '[' . get_bloginfo('name') . '] Website Enquiry',
        ),

    );

    return $templates;

}

add_filter('cgit_contact_templates', 'cgit_contact_add_default_template');
