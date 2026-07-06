<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/admin_controller.php');
require_once(__DIR__ . '/../classes/service/repository_service.php');
require_once(__DIR__ . '/../classes/service/pagination_service.php');

require_login();

require_capability(
    'moodle/site:config',
    context_system::instance()
);

global $PAGE, $OUTPUT;

$PAGE->set_url(
    new moodle_url('/local/inveniordm/admin/repository.php')
);

$PAGE->set_context(
    context_system::instance()
);

$PAGE->set_title('Repository Management');

$PAGE->requires->css(
    new moodle_url('/local/inveniordm/styles/main.css')
);

$PAGE->requires->css(
    new moodle_url('/local/inveniordm/styles/repository.css')
);

$controller = new admin_controller();

$context = $controller->get_repository_context();

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_inveniordm/admin/repository',
    $context
);

echo $OUTPUT->footer();