<?php

require_once(__DIR__ . '/../../../config.php');
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

$dbstatus = false;
$dbmessage = '';

try {
    $DB->count_records('user');
    $dbstatus = true;
    $dbmessage = 'Connected';
} catch (Exception $e) {
    $dbstatus = false;
    $dbmessage = $e->getMessage();
}

$apistatus = false;
$apimessage = '';
$httpcode = 0;

$start = microtime(true);

try {
    $client = new \local_inveniordm\api\invenio_client();
    $result = $client->get_records();
    // tính thời gian phản hồi - ms
    $latency = round((microtime(true) - $start) * 1000);

    if (is_array($result) && empty($result['error'])) {
        $apistatus = true;
        $apimessage = 'Connected';
        $httpcode = 200;
    } else {
        $apistatus = false;
        $apimessage = 'API Error';
        $httpcode = $result['status'] ?? 500;
    }
} catch (Exception $e) {
    $apistatus = false;
    $apimessage = $e->getMessage();
    $latency = round((microtime(true) - $start) * 1000);
}

$healthscore = 0;

if ($dbstatus) {
    $healthscore += 25;
}

if ($apistatus) {
    $healthscore += 25;
}

if (!empty($latency) && $latency < 1000) {
    $healthscore += 25;
} elseif (!empty($latency) && $latency < 2000) {
    $healthscore += 15;
}

if (empty($result['error'])) {
    $healthscore += 25;
}

$dbclass = $dbstatus ? 'success' : 'danger';
$dbtext  = $dbstatus ? 'Online' : 'Offline';

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
$apitext  = $apistatus ? 'Online' : 'Offline';

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