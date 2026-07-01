<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/lecturer_controller.php');
require_once(__DIR__ . '/../classes/service/resource_service.php');

global $CFG;
$courseid = required_param('courseid', PARAM_INT);

require_login();
$context = context_course::instance($courseid);
require_capability('local/inveniordm:upload', $context);
global $DB, $PAGE, $OUTPUT, $USER;

require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/service/log_service.php'
);

$PAGE->set_url(new moodle_url('/local/inveniordm/lecturer/search_resources_to_attach.php',
    ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title('Search Repository');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/main.css'
    )
);
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/search_resources_to_attach.css'
    )
);

$controller = new lecturer_controller();

if (optional_param('attach', '', PARAM_TEXT)) {
    $controller->attach_resource();
}

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_inveniordm/lecturer/search_resources_to_attach',
    $controller->get_search_resources_to_attach_context()
);

echo $OUTPUT->footer();