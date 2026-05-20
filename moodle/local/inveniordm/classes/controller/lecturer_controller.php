<?php

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../form/upload_form.php');
class lecturer_controller {

    public function upload() {

        global $PAGE, $OUTPUT;

        $context = context_system::instance();

        require_capability(
            'local/inveniordm:upload',
            $context
        );

        $PAGE->set_url(
            new moodle_url('/local/inveniordm/lecturer/upload.php')
        );

        $PAGE->set_context($context);

        $PAGE->set_title('Upload Resources');

        $PAGE->set_heading('Lecturer Upload');

        $mform = new upload_form();

        echo $OUTPUT->header();

        $mform->display();

        echo $OUTPUT->footer();
    }
}