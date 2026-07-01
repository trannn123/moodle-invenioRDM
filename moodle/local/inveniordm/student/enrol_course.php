<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/student_controller.php');
require_once(__DIR__ . '/../classes/service/course_service.php');

require_login();
require_sesskey();
global $USER, $PAGE;

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
$context = context_course::instance($courseid);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/inveniordm/student/enrol_course.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('course');

$controller = new student_controller();
$controller->enrol_course();