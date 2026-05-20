<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

// Include thủ công controller file
require_once($CFG->dirroot . '/local/inveniordm/classes/controller/student_controller.php');

$id = required_param('id', PARAM_TEXT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/inveniordm/student/view.php', ['id' => $id]));
$PAGE->set_title('View Resource');
$PAGE->set_heading('View Resource');

// Tạo instance
$controller = new \local_inveniordm\controller\student_controller();
$controller->view($id);