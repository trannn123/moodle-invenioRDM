<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/student_controller.php');
require_once(__DIR__ . '/../classes/service/course_service.php');

require_login();
global $DB, $PAGE, $OUTPUT, $USER;

$PAGE->set_url(new moodle_url('/local/inveniordm/student/all_courses.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('All Courses');
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

$controller = new student_controller();
$templatecontext = $controller->get_all_courses_context();

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_inveniordm/student/all_courses',
    $templatecontext
);

echo $OUTPUT->footer();