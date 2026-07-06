<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/student_controller.php');
require_once(__DIR__ . '/../classes/service/assignment_service.php');
require_once(__DIR__ . '/../classes/service/pagination_service.php');

global $DB, $PAGE, $OUTPUT, $USER;

$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid]);
if (!$course) {
    throw new moodle_exception('invalidcourseid', 'error');
}
$context = context_course::instance($course->id);
require_login($course);

if (!is_enrolled($context, $USER)) {
    throw new moodle_exception('notenrolled', 'enrol');
}

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/student/assignments.php',
        ['courseid' => $courseid]
    )
);
$PAGE->set_context($context);
$PAGE->set_title('Assignments');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/main.css'
    )
);
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/assignments.css'
    )
);

$controller = new student_controller();
$templatecontext = $controller->get_course_assignments_context();

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_inveniordm/student/assignments',
    $templatecontext
);

echo $OUTPUT->footer();