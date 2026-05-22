<?php

namespace local_inveniordm\form;

defined('MOODLE_INTERNAL') || die();

require_once(
    $GLOBALS['CFG']->libdir . '/formslib.php'
);

class upload_form extends \moodleform {

    public function definition() {

        $mform = $this->_form;

        /*
         * Basic Information
         */

        $mform->addElement(
            'header',
            'basicinfo',
            'Basic Information'
        );

        /*
         * Title
         */

        $mform->addElement(
            'text',
            'title',
            'Resource Title'
        );

        $mform->setType(
            'title',
            PARAM_TEXT
        );

        $mform->addRule(
            'title',
            'Required',
            'required',
            null,
            'client'
        );

        /*
         * Description
         */

        $mform->addElement(
            'textarea',
            'description',
            'Description',
            [
                'rows' => 6,
                'cols' => 60
            ]
        );

        $mform->setType(
            'description',
            PARAM_TEXT
        );

        /*
         * File Upload
         */

        $mform->addElement(
            'filepicker',
            'resourcefile',
            'Upload File'
        );

        /*
         * Submit Button
         */

        $this->add_action_buttons(
            true,
            'Upload Resource'
        );
    }
}