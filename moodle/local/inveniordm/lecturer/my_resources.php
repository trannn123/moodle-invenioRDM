<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/lecturer_controller.php');
require_once(__DIR__ . '/../classes/service/resource_service.php');

require_login();
global $PAGE, $OUTPUT, $CFG;
$context = context_system::instance();
$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/my_resources.php'
    )
);
$PAGE->set_context($context);
$PAGE->set_title('My Resources');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/main.css'
    )
);
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/my_resources.css'
    )
);

$controller = new lecturer_controller();

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_inveniordm/lecturer/my_resources',
    $controller->get_my_resources_context()
);

echo $OUTPUT->footer();