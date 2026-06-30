<?php


require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/admin_controller.php');
require_login();

require_capability(
    'moodle/site:config',
    context_system::instance()
);

$admincontroller = new admin_controller();
$logs = $admincontroller->get_logs_for_export();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="analytics_logs.csv"');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'User ID',
    'Action',
    'Resource ID',
    'Course ID',
    'Time'
]);

foreach ($logs as $log) {
    fputcsv($output, [
        $log->userid,
        $log->action,
        $log->resourceid,
        $log->courseid,
        userdate($log->timecreated)
    ]);
}

fclose($output);
exit;