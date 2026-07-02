<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/admin_controller.php');
require_once(__DIR__ . '/../classes/service/admin_log_service.php');

require_login();

require_capability(
    'moodle/site:config',
    context_system::instance()
);

global $PAGE, $OUTPUT;

$PAGE->set_url(
    new moodle_url('/local/inveniordm/admin/logs.php')
);

$PAGE->set_context(
    context_system::instance()
);

$PAGE->set_title('System Logs');

$PAGE->requires->css(
    new moodle_url('/local/inveniordm/styles/main.css')
);

$PAGE->requires->css(
    new moodle_url('/local/inveniordm/styles/logs.css')
);

$controller = new admin_controller();

echo $OUTPUT->header();

$context = $controller->get_logs_context();

echo $OUTPUT->render_from_template(
    'local_inveniordm/admin/logs',
    $context
);

echo $OUTPUT->footer();