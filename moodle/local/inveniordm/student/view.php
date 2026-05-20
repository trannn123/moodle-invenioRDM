<?php

require_once(__DIR__ . '/../../../config.php');

$id = required_param('id', PARAM_TEXT);

$context = context_system::instance();

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/student/view.php',
        ['id' => $id]
    )
);

$PAGE->set_context($context);

$PAGE->set_pagelayout('standard');

require_login();

$PAGE->set_title('View Resource');

$PAGE->set_heading('View Resource');

echo $OUTPUT->header();

require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/controller/student_controller.php'
);

$controller = new \local_inveniordm\controller\student_controller();

echo $controller->view($id);

echo $OUTPUT->footer();