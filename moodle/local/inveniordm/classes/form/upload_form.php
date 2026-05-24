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
         * Language
         */

        $mform->addElement(
            'select',
            'language',
            'Language',
            [
                'en' => 'English',
                'vi' => 'Vietnamese'
            ]
        );

        /*
         * Format
         */

        $mform->addElement(
            'select',
            'format',
            'Format',
            [
                'pdf' => 'PDF',
                'video' => 'Video',
                'doc' => 'DOC'
            ]
        );

        /*
         * Document Type
         */

        $mform->addElement(
            'select',
            'documenttype',
            'Document Type',
            [
                'text' => 'Text',
                'dataset' => 'Dataset',
                'image' => 'Image',
                'video' => 'Video'
            ]
        );

        /*
         * Discipline
         */

        $mform->addElement(
            'select',
            'discipline',
            'Discipline',
            [
                'Artificial Intelligence' =>
                    'Artificial Intelligence',

                'Computer Networking' =>
                    'Computer Networking',

                'Cyber Security' =>
                    'Cyber Security'
            ]
        );

        /*
         * Educational Level
         */

        $mform->addElement(
            'select',
            'educationallevel',
            'Educational Level',
            [
                'bachelor' => 'Bachelor',
                'master' => 'Master'
            ]
        );

        /*
         * Target Audience
         */

        $mform->addElement(
            'select',
            'targetaudience',
            'Target Audience',
            [
                'learner' => 'Learner',
                'teacher' => 'Teacher'
            ]
        );

        /*
         * Learning Resource Type
         */

        $mform->addElement(
            'select',
            'learningresourcetype',
            'Learning Resource Type',
            [
                'lesson' => 'Lesson',
                'tutorial' => 'Tutorial',
                'lab' => 'Lab',
                'exercise' => 'Exercise'
            ]
        );

        /*
         * Keywords
         */

        $mform->addElement(
            'text',
            'keywords',
            'Keywords'
        );

        $mform->setType(
            'keywords',
            PARAM_TEXT
        );

        /*
         * Copyright
         */

        $mform->addElement(
            'select',
            'copyright',
            'Copyright',
            [
                'yes' => 'Yes',
                'no' => 'No'
            ]
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