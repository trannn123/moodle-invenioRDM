<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/lecturer_controller.php');
require_once(__DIR__ . '/../classes/service/assignment_service.php');

global $DB, $PAGE, $OUTPUT;
$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id);
require_login($course);
require_capability('local/inveniordm:upload', $context);

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/assignments.php',
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

$controller = new lecturer_controller();
$context = $controller->get_course_assignments_context();

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_inveniordm/lecturer/assignments',
    $context
);

echo $OUTPUT->footer();