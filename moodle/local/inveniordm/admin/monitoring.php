<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/admin_controller.php');
require_login();
global $CFG, $DB, $PAGE, $OUTPUT;

require_capability(
    'moodle/site:config',
    context_system::instance()
);

$PAGE->set_url(new moodle_url('/local/inveniordm/admin/monitoring.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('System Monitoring');

echo $OUTPUT->header();

$admincontroller = new admin_controller();

$dbinfo = $admincontroller->check_database_status();

$dbstatus = $dbinfo['status'];
$dbmessage = $dbinfo['message'];

$apiinfo = $admincontroller->check_api_status();

$apistatus = $apiinfo['status'];
$apimessage = $apiinfo['message'];
$httpcode = $apiinfo['httpcode'];
$latency = $apiinfo['latency'];
$result = $apiinfo['result'];

$healthscore = $admincontroller->calculate_health_score(
    $dbstatus,
    $apistatus,
    $latency,
    $result
);

$dbclass = $dbstatus ? 'success' : 'danger';
$dbtext = $dbstatus ? 'Online' : 'Offline';

echo '
    <div class="hero-section">
        <h1>System Monitoring</h1>
        <p>Monitor Moodle services and InvenioRDM integration status.</p>
    </div>
';

echo '
    <div class="card mt-4">
        <div class="card-header">
            <i class="fa fa-database"></i>
            Database Status
        </div>
    
        <div class="card-body">
            <div class="alert alert-' . $dbclass . ' mb-0">
                <strong>' . $dbtext . '</strong><br>
                ' . s($dbmessage) . '
            </div>
        </div>
    </div>
';


$apiclass = $apistatus ? 'success' : 'danger';
$apitext = $apistatus ? 'Online' : 'Offline';

echo '
<div class="card mt-4">
    <div class="card-header">
        <i class="fa fa-cloud"></i>
        InvenioRDM API Status
    </div>

    <div class="card-body">
        <div class="alert alert-' . $apiclass . ' mb-0">
            <strong>' . $apitext . '</strong><br>
            HTTP Code: ' . $httpcode . '<br>
            Latency: ' . $latency . ' ms<br>
            Message: ' . s($apimessage) . '
        </div>
    </div>
</div>
';

$healthclass = 'success';

if ($healthscore < 50) {
    $healthclass = 'danger';
} elseif ($healthscore < 80) {
    $healthclass = 'warning';
}

echo '
<div class="card mt-4">
    <div class="card-header">
        <i class="fa fa-heartbeat"></i>
        System Health Score
    </div>

    <div class="card-body">
        <div class="alert alert-' . $healthclass . ' mb-0">
            <strong>Score: ' . $healthscore . '/100</strong><br>
            System overall status evaluation
        </div>
    </div>
</div>
';

echo $OUTPUT->footer();