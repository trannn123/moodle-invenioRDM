<?php

require_once(__DIR__ . '/../../../config.php');

require_login();

global $CFG, $PAGE, $OUTPUT;

require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/form/upload_form.php'
);

require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/controller/lecturer_controller.php'
);

$context = context_system::instance();

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/upload.php'
    )
);

$PAGE->set_context($context);

$PAGE->set_pagelayout('standard');

$PAGE->set_title('Upload Resource');

$PAGE->set_heading('Upload Repository Resource');

$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/main.css'
    )
);

$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/upload_lecturer.css'
    )
);

echo $OUTPUT->header();

$controller =
    new \local_inveniordm\controller\lecturer_controller();

echo $controller->upload();

echo $OUTPUT->footer();