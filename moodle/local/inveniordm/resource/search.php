<?php

require_once(__DIR__ . '/../../../config.php');
global $CFG, $PAGE, $OUTPUT;
require_login();
require_once($CFG->dirroot.'/local/inveniordm/classes/controller/resource_controller.php');
$context = context_system::instance();

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/resource/search.php'
    )
);

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Search Records');
$PAGE->set_heading('Search Invenio Records');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/main.css'
    )
);
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/search.css'
    )
);

$backurl = optional_param('returnurl', '', PARAM_URL);
if (empty($backurl)) {
    $backurl = (new \moodle_url('/local/inveniordm/index.php'))->out(false);
}

echo $OUTPUT->header();

$controller = new \local_inveniordm\controller\resource_controller();
echo $controller->search($backurl);
echo $OUTPUT->footer();