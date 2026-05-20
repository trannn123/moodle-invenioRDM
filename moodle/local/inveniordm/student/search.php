<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

// Include thủ công controller file
require_once($CFG->dirroot . '/local/inveniordm/classes/controller/student_controller.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/inveniordm/student/search.php'));
$PAGE->set_title('Search Records');
$PAGE->set_heading('Search Invenio Records');

$controller = new \local_inveniordm\controller\student_controller();
$controller->search();