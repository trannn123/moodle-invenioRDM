<?php

require_once(__DIR__ . '/../../../config.php');
require_login();
global $CFG, $PAGE, $OUTPUT;
require_once($CFG->dirroot.'/local/inveniordm/classes/controller/resource_controller.php');

$id = required_param(
    'id',
    PARAM_TEXT
);

$returnurl = optional_param(
    'returnurl',
    '',
    PARAM_LOCALURL
);

$context = context_system::instance();

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/resource/view.php',
        ['id' => $id]
    )
);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title('View Record');
$PAGE->set_heading('Repository Resource');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/main.css'
    )
);
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/view.css'
    )
);

echo $OUTPUT->header();
$controller = new \local_inveniordm\controller\resource_controller();
echo $controller->view($id, $returnurl);
echo $OUTPUT->footer();