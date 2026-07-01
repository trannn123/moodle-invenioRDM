<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/lecturer_controller.php');
require_once(__DIR__ . '/../classes/service/course_service.php');

require_login();
global $DB, $USER, $PAGE, $OUTPUT;
$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/create_assignment.php',
        ['courseid' => $courseid]
    )
);

$PAGE->set_context($context);
$PAGE->set_title('Create Assignment');

require_capability('local/inveniordm:upload', $context);
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/main.css'
    )
);
$PAGE->requires->css(
    new moodle_url('/local/inveniordm/styles/create_assignment.css')
);

$controller = new lecturer_controller();
$data = $controller->get_create_assignment_context($courseid, $_POST);

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_inveniordm/lecturer/create_assignment',
    $data
);

echo $OUTPUT->footer();