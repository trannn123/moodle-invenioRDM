<?php

require_once(__DIR__ . '/../../../config.php');

require_login();

require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/controller/student_controller.php'
);

$context = context_system::instance();

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/student/search.php'
    )
);

$PAGE->set_context($context);

$PAGE->set_pagelayout('standard');

$PAGE->set_title('Search Records');

$PAGE->set_heading('Search Invenio Records');

echo $OUTPUT->header();

$controller =
    new \local_inveniordm\controller\student_controller();

echo $controller->search();

echo $OUTPUT->footer();