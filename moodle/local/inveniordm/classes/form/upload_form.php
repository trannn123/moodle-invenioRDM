<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class upload_form extends moodleform {

    public function definition() {

        $mform = $this->_form;

        // title
        $mform->addElement(
            'text',
            'title',
            'Resource Title'
        );

        $mform->setType('title', PARAM_TEXT);

        $mform->addRule(
            'title',
            'Required',
            'required',
            null,
            'client'
        );

        // file upload
        $mform->addElement(
            'filepicker',
            'resourcefile',
            'Upload PDF'
        );

        // submit
        $mform->addElement(
            'submit',
            'submitbutton',
            'Upload'
        );
    }
}