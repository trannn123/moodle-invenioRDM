<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/student_controller.php');
require_once(__DIR__ . '/../classes/service/course_service.php');
require_login();
global $DB, $PAGE, $OUTPUT;

$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);

$PAGE->set_url(new moodle_url(
    '/local/inveniordm/student/course_resources.php',
    ['courseid' => $courseid]
));
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

$controller = new student_controller();
$templatecontext = $controller->get_course_resources_context();

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_inveniordm/student/course_resources',
    $templatecontext
);

echo $OUTPUT->footer();