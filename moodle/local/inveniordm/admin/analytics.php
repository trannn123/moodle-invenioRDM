<?php
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/admin_controller.php');
require_once(__DIR__ . '/../classes/service/analytics_service.php');
require_once(__DIR__ . '/../classes/service/pagination_service.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

global $PAGE, $OUTPUT;

$admincontroller = new admin_controller();

$PAGE->set_url(new moodle_url('/local/inveniordm/admin/analytics.php'));
$PAGE->set_context(context_system::instance());

$PAGE->requires->css(new moodle_url('/local/inveniordm/styles/main.css'));
$PAGE->requires->css(new moodle_url('/local/inveniordm/styles/analytics.css'));

$controller = new admin_controller();

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_inveniordm/admin/analytics',
    $controller->get_analytics_context()
);

echo $OUTPUT->footer();