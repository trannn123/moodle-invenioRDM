<?php
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/admin_controller.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

global $DB, $PAGE, $OUTPUT, $CFG;

$admincontroller = new admin_controller();

$PAGE->set_url(new moodle_url('/local/inveniordm/admin/analytics.php'));
$PAGE->set_context(context_system::instance());

$PAGE->requires->css(new moodle_url('/local/inveniordm/styles/main.css'));
$PAGE->requires->css(new moodle_url('/local/inveniordm/styles/analytics.css'));

echo $OUTPUT->header();

$range = optional_param('range', '30days', PARAM_ALPHANUMEXT);

$activitycounts = $admincontroller->get_activity_counts($range);

$uploads = $activitycounts['uploads'];
$views = $activitycounts['views'];
$downloads = $activitycounts['downloads'];
$searches = $activitycounts['searches'];
$attachments = $activitycounts['attachments'];
$submissions = $activitycounts['submissions'];

$recentactivities = $admincontroller->get_recent_activity_data();
$topresources = $admincontroller->get_top_viewed_resources($range);
$topdownloads = $admincontroller->get_top_downloaded_resources($range);
$topusers = $admincontroller->get_top_active_users($range);
$topcourses = $admincontroller->get_top_courses($range);
$breakdown = $admincontroller->get_activity_breakdown($range);

$activityData = $breakdown['activityData'] ?? [];
$pieData = $breakdown['pieData'] ?? [];
$totalActivities = $breakdown['totalActivities'] ?? 0;

$conicGradient = '';
$startAngle = 0;

foreach ($pieData as $index => $item) {
    if (!empty($item['angle'])) {
        $conicGradient .= $item['color'] . ' ' . $startAngle . 'deg ' . ($startAngle + $item['angle']) . 'deg';
        if ($index < count($pieData) - 1) {
            $conicGradient .= ', ';
        }
        $startAngle += $item['angle'];
    }
}

$backurl = new moodle_url('/local/inveniordm/index.php');

echo '<div class="container analytics-container">';

echo '
    <div class="page-hero">
        <div class="page-hero-content">
            <h1><i class="fa fa-chart-line"></i> Analytics Dashboard</h1>
            <p>Monitor repository activities, user interactions, and system performance.</p>
        </div>
    
        <div class="hero-actions">
            <a href="' . $backurl . '" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
';

echo '
    <div class="filter-bar">
        <form method="GET" class="filter-form">
            <select name="range" onchange="this.form.submit()">   
                <option value="today" ' . ($range === 'today' ? 'selected' : '') . '>Today</option>   
                <option value="7days" ' . ($range === '7days' ? 'selected' : '') . '>7 Days</option>
                <option value="30days" ' . ($range === '30days' ? 'selected' : '') . '>30 Days</option>   
            </select>
        </form>
    </div>
';

echo '
    <div class="mb-4">
        <a href="' . $CFG->wwwroot . '/local/inveniordm/admin/export_logs.php"
           class="btn btn-success export-btn">
            <i class="fa fa-download"></i> Export CSV
        </a>
    </div>
';

echo '<div class="stats-grid">';

$stats = [
    ['icon' => 'upload', 'value' => $uploads, 'label' => 'Uploads'],
    ['icon' => 'eye', 'value' => $views, 'label' => 'Views'],
    ['icon' => 'download', 'value' => $downloads, 'label' => 'Downloads'],
    ['icon' => 'search', 'value' => $searches, 'label' => 'Searches'],
    ['icon' => 'paperclip', 'value' => $attachments, 'label' => 'Attachments'],
    ['icon' => 'check-circle', 'value' => $submissions, 'label' => 'Submissions'],
];

foreach ($stats as $s) {
    echo '
        <div class="stat-card">
            <div class="stat-icon"><i class="fa fa-' . $s['icon'] . '"></i></div>
            <div>
                <div class="stat-number">' . $s['value'] . '</div>
                <div class="stat-label">' . $s['label'] . '</div>
            </div>
        </div>
    ';
}

echo '</div>';

echo '<div class="row g-4 mt-4">';

echo '
    <div class="col-lg-6">
    <div class="dashboard-card">
        <div class="card-header">
            <i class="fa fa-eye"></i> Top Viewed Resources
        </div>
        <div class="card-body">
            <table class="table-enhanced">
                <thead>
                    <tr><th>Resource</th><th class="text-center">Views</th></tr>
                </thead>
                <tbody>
';

foreach ($topresources as $r) {
    echo '
        <tr>
            <td>' . s($r['title']) . '</td>
            <td class="text-center"><span class="badge-count">' . $r['totalviews'] . '</span></td>
        </tr>
    ';
}

echo '
                </tbody>
            </table>
        </div>
    </div>
    </div>
';

echo '
    <div class="col-lg-6">
    <div class="dashboard-card">
        <div class="card-header">
            <i class="fa fa-download"></i> Top Downloaded Resources
        </div>
        <div class="card-body">
            <table class="table-enhanced">
                <thead>
                    <tr><th>Resource</th><th class="text-center">Downloads</th></tr>
                </thead>
                <tbody>
';

foreach ($topdownloads as $r) {
    echo '
        <tr>
            <td>' . s($r['title']) . '</td>
            <td class="text-center"><span class="badge-count">' . $r['totaldownloads'] . '</span></td>
        </tr>
    ';
}

echo '
                </tbody>
            </table>
        </div>
    </div>
    </div>
';

echo '</div>';

echo '<div class="row g-4 mt-4">';

echo '
    <div class="col-lg-6">
    <div class="dashboard-card">
        <div class="card-header">
            <i class="fa fa-users"></i> Top Active Users
        </div>
        <div class="card-body">
            <table class="table-enhanced">
                <thead>
                    <tr><th>User</th><th class="text-center">Activities</th></tr>
                </thead>
                <tbody>
';

foreach ($topusers as $u) {
    echo '
        <tr>
            <td>' . s($u['username']) . '</td>
            <td class="text-center"><span class="badge-count">' . $u['activitycount'] . '</span></td>
        </tr>
    ';
}

echo '
                </tbody>
            </table>
        </div>
    </div>
    </div>
';

echo '
    <div class="col-lg-6">
    <div class="dashboard-card">
        <div class="card-header">
            <i class="fa fa-chart-pie"></i> Activity Breakdown
        </div>
        <div class="card-body">
    
            <div class="pie-chart-wrapper">
                <div class="pie-chart">
                    <div class="pie-slice" style="background: conic-gradient(' . $conicGradient . ');">
                        <div class="pie-center">
                            <div class="pie-total">' . $totalActivities . '</div>
                            <div class="pie-label">Total</div>
                        </div>
                    </div>
                </div>
            </div>
    
            <div class="chart-legend">
';

foreach ($activityData as $a) {
    echo '
        <div class="legend-item">
            <span class="legend-color" style="background:' . $a['color'] . '"></span>
            <span class="legend-label">' . $a['label'] . '</span>
            <span class="legend-value">' . $a['value'] . '</span>
        </div>
    ';
}

echo '
        </div>

    </div>
</div>
</div>';

echo '</div>';

echo '
    <div class="dashboard-card mt-4">
        <div class="card-header">
            <i class="fa fa-graduation-cap"></i> Most Active Courses
        </div>
        <div class="card-body">
            <table class="table-enhanced">
                <thead>
                    <tr><th>Course</th><th class="text-center">Activities</th></tr>
                </thead>
                <tbody>
';

foreach ($topcourses as $item) {
    echo '
        <tr>
            <td>' . s($item['coursename']) . '</td>
            <td class="text-center"><span class="badge-count">' . $item['totalactivities'] . '</span></td>
        </tr>
    ';
}

echo '
            </tbody>
        </table>
    </div>
</div>';

echo '
    <div class="dashboard-card mt-4">
        <div class="card-header">
            <i class="fa fa-clock"></i> Recent Activities
        </div>
        <div class="card-body">
            <table class="table-enhanced">
                <thead>
                    <tr>
                        <th>User</th><th>Action</th><th>Resource</th><th>Course</th><th>Time</th>
                    </tr>
                </thead>
                <tbody>
';

foreach ($recentactivities as $item) {
    $log = $item['log'];

    echo '
        <tr>
            <td>' . s($item['username']) . '</td>
            <td>' . s($item['action']) . '</td>
            <td>' . s($item['resourcename']) . '</td>
            <td>' . s($item['coursename']) . '</td>
            <td>' . userdate($log->timecreated) . '</td>
        </tr>
    ';
}

echo '
                </tbody>
            </table>
        </div>
    </div>
';

echo '</div>';

echo $OUTPUT->footer();