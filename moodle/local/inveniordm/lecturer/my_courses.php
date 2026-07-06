<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/lecturer_controller.php');
require_once(__DIR__ . '/../classes/service/course_service.php');
require_once(__DIR__ . '/../classes/service/pagination_service.php');

require_login();
global $USER, $PAGE, $OUTPUT, $DB;
$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/my_courses.php'
    )
);

$PAGE->set_context(context_system::instance());
$PAGE->set_title('My Teaching Courses');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/main.css'
    )
);
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/courses.css'
    )
);

$controller = new lecturer_controller();

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_inveniordm/lecturer/my_courses',
    $controller->get_my_courses_context()
);

echo $OUTPUT->footer();