<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/lecturer_controller.php');
require_once(__DIR__ . '/../classes/service/resource_service.php');
require_once(__DIR__ . '/../classes/service/pagination_service.php');

require_login();
$courseid = required_param('courseid', PARAM_INT);
global $DB, $PAGE, $OUTPUT;
$context = context_course::instance($courseid);
$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/course_resources.php',
        ['courseid' => $courseid]
    )
);

$PAGE->set_context($context);
$PAGE->set_title('Course Resources');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/main.css'
    )
);
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/course_resources.css'
    )
);

$controller = new lecturer_controller();
$data = $controller->get_course_resources_context($courseid);

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_inveniordm/lecturer/course_resources',
    $data
);

echo $OUTPUT->footer();