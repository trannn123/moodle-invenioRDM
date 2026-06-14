<?php

require_once(__DIR__ . '/../../../config.php');

require_login();
require_sesskey();

global $USER, $PAGE;

$courseid = required_param('courseid', PARAM_INT);

$course = get_course($courseid);
$context = context_course::instance($courseid);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/inveniordm/student/enrol_course.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('course');

if (is_enrolled($context, $USER->id)) {
    redirect(
        new moodle_url('/local/inveniordm/student/allcourses.php'),
        'You are already enrolled.'
    );
}

$instances = enrol_get_instances($courseid, true);

$selfinstance = null;

foreach ($instances as $instance) {
    if ($instance->enrol === 'self') {
        $selfinstance = $instance;
        break;
    }
}

if (!$selfinstance) {
    throw new moodle_exception('Self enrolment is not enabled for this course.');
}

$plugin = enrol_get_plugin('self');

$plugin->enrol_user(
    $selfinstance,
    $USER->id,
    5
);

core\notification::success('Enrolled successfully');

redirect(
    new moodle_url('/local/inveniordm/student/mycourses.php')
);