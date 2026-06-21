<?php
// analytics.php
require_once(__DIR__.'/../../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

global $DB, $PAGE, $OUTPUT, $CFG;
$PAGE->set_url(new moodle_url('/local/inveniordm/admin/analytics.php'));
$PAGE->set_context(context_system::instance());
$PAGE->requires->css(new moodle_url('/local/inveniordm/styles/analytics.css'));

echo $OUTPUT->header();
?>
    <div class="back-button-container">
        <a href="<?php echo isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $CFG->wwwroot; ?>" class="btn btn-back">
            <i class="fa fa-arrow-left"></i>
            Back
        </a>
    </div>

    <div class="hero-section">
        <h1>Analytics Dashboard</h1>
        <p>Monitor repository activities, user interactions, and learning resource usage.</p>
    </div>



    <div class="mb-4">
        <a href="<?php echo $CFG->wwwroot; ?>/local/inveniordm/admin/export_logs.php" class="btn btn-success export-btn">
            <i class="fa fa-download"></i>
            Export CSV
        </a>
    </div>

<?php
// Counts
$uploads     = $DB->count_records('local_inveniordm_logs', ['action' => 'UPLOAD_RESOURCE']);
$views       = $DB->count_records('local_inveniordm_logs', ['action' => 'VIEW_RESOURCE']);
$downloads   = $DB->count_records('local_inveniordm_logs', ['action' => 'DOWNLOAD_RESOURCE']);
$searches    = $DB->count_records('local_inveniordm_logs', ['action' => 'SEARCH_RESOURCE']);
$attachments = $DB->count_records('local_inveniordm_logs', ['action' => 'ATTACH_RESOURCE']);
$submissions = $DB->count_records('local_inveniordm_logs', ['action' => 'SUBMIT_ASSIGNMENT']);
?>

    <div class="analytics-grid">
        <div class="stat-card uploads">    <i class="fa fa-upload fa-2x mb-2"></i><h2><?php echo $uploads; ?></h2><p>Uploads</p></div>
        <div class="stat-card views">      <i class="fa fa-eye fa-2x mb-2"></i><h2><?php echo $views; ?></h2><p>Views</p></div>
        <div class="stat-card downloads">  <i class="fa fa-download fa-2x mb-2"></i><h2><?php echo $downloads; ?></h2><p>Downloads</p></div>
        <div class="stat-card searches">   <i class="fa fa-search fa-2x mb-2"></i><h2><?php echo $searches; ?></h2><p>Searches</p></div>
        <div class="stat-card attachments"><i class="fa fa-paperclip fa-2x mb-2"></i><h2><?php echo $attachments; ?></h2><p>Attachments</p></div>
        <div class="stat-card submissions"><i class="fa fa-check-circle fa-2x mb-2"></i><h2><?php echo $submissions; ?></h2><p>Submissions</p></div>
    </div>

<?php
// Recent activities
$recentactivities = $DB->get_records_sql("SELECT * FROM {local_inveniordm_logs} ORDER BY timecreated DESC LIMIT 10");

$userids = [];
foreach ($recentactivities as $activity) {
    if (!empty($activity->userid)) $userids[$activity->userid] = $activity->userid;
}
$users = [];
if ($userids) {
    list($sqlin, $params) = $DB->get_in_or_equal($userids);
    $users = $DB->get_records_select('user', "id $sqlin", $params, '', 'id, firstname, lastname, firstnamephonetic, lastnamephonetic, middlename, alternatename');
}

$courseids = [];
foreach ($recentactivities as $activity) {
    if (!empty($activity->courseid)) $courseids[$activity->courseid] = $activity->courseid;
}
$courses = [];
if ($courseids) {
    list($sqlin, $params) = $DB->get_in_or_equal($courseids);
    $courses = $DB->get_records_select('course', "id $sqlin", $params, '', 'id, fullname');
}

// Top resources
$topresources = $DB->get_records_sql("SELECT resourceid, COUNT(*) AS totalviews FROM {local_inveniordm_logs} WHERE action = 'VIEW_RESOURCE' AND resourceid <> '' GROUP BY resourceid ORDER BY totalviews DESC LIMIT 5");
$topdownloads = $DB->get_records_sql("SELECT resourceid, COUNT(*) AS totaldownloads FROM {local_inveniordm_logs} WHERE action = 'DOWNLOAD_RESOURCE' AND resourceid <> '' GROUP BY resourceid ORDER BY totaldownloads DESC LIMIT 5");

$resourceids = [];
foreach ($topresources as $item) { if (!empty($item->resourceid)) $resourceids[$item->resourceid] = $item->resourceid; }
foreach ($topdownloads as $item) { if (!empty($item->resourceid)) $resourceids[$item->resourceid] = $item->resourceid; }
foreach ($recentactivities as $item) { if (!empty($item->resourceid)) $resourceids[$item->resourceid] = $item->resourceid; }

$resourcerecords = [];
if ($resourceids) {
    list($sqlin, $params) = $DB->get_in_or_equal($resourceids);
    $resourcerecords = $DB->get_records_select('local_inveniordm_course_resources', "recordid $sqlin", $params, '', 'recordid,title');
}

$topusers = $DB->get_records_sql("SELECT userid, COUNT(*) AS activitycount FROM {local_inveniordm_logs} GROUP BY userid ORDER BY activitycount DESC LIMIT 5");
$activitystats = $DB->get_records_sql("SELECT action, COUNT(*) AS total FROM {local_inveniordm_logs} GROUP BY action ORDER BY total DESC");
$topcourses = $DB->get_records_sql("SELECT courseid, COUNT(*) AS totalactivities FROM {local_inveniordm_logs} WHERE courseid IS NOT NULL GROUP BY courseid ORDER BY totalactivities DESC LIMIT 5");

$topuserids = [];
foreach ($topusers as $user) { $topuserids[$user->userid] = $user->userid; }
$topuserrecords = [];
if ($topuserids) {
    list($sqlin, $params) = $DB->get_in_or_equal($topuserids);
    $topuserrecords = $DB->get_records_select('user', "id $sqlin", $params, '', 'id, firstname, lastname, firstnamephonetic, lastnamephonetic, middlename, alternatename');
}

$actionlabels = [
    'UPLOAD_RESOURCE' => 'Upload Resource',
    'VIEW_RESOURCE' => 'View Resource',
    'DOWNLOAD_RESOURCE' => 'Download Resource',
    'SEARCH_RESOURCE' => 'Search Resource',
    'ATTACH_RESOURCE' => 'Attach Resource',
    'SUBMIT_ASSIGNMENT' => 'Submit Assignment'
];

// Prepare data for pie chart
$colorMap = [
    'UPLOAD_RESOURCE' => '#3b7bc9',
    'VIEW_RESOURCE' => '#35a77c',
    'DOWNLOAD_RESOURCE' => '#d97747',
    'SEARCH_RESOURCE' => '#b48ad9',
    'ATTACH_RESOURCE' => '#d45a7a',
    'SUBMIT_ASSIGNMENT' => '#4e9fcf'
];

// Calculate total for percentages
$totalActivities = 0;
$activityData = [];
foreach ($activitystats as $item) {
    $totalActivities += (int)$item->total;
    $activityData[] = [
        'label' => $actionlabels[$item->action] ?? $item->action,
        'value' => (int)$item->total,
        'color' => $colorMap[$item->action] ?? '#6c757d',
        'action' => $item->action
    ];
}

// Calculate percentages and angles for the pie chart
$pieData = [];
$currentAngle = 0;
foreach ($activityData as $item) {
    $percentage = $totalActivities > 0 ? ($item['value'] / $totalActivities) * 100 : 0;
    $angle = $totalActivities > 0 ? ($item['value'] / $totalActivities) * 360 : 0;
    $pieData[] = [
        'label' => $item['label'],
        'value' => $item['value'],
        'color' => $item['color'],
        'percentage' => $percentage,
        'angle' => $angle,
        'startAngle' => $currentAngle,
        'endAngle' => $currentAngle + $angle
    ];
    $currentAngle += $angle;
}
?>

    <!-- Two column cards: top viewed & top downloaded -->
    <div class="row g-4 mt-4">
        <div class="col-lg-6">
            <div class="card analytics-card h-100">
                <div class="card-header">
                    <i class="fa fa-eye header-icon"></i>
                    Top Viewed Resources
                </div>
                <div class="card-body">
                    <table class="table table-enhanced">
                        <thead>
                        <tr>
                            <th>Resource</th>
                            <th class="text-center">Views</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($topresources as $resource):
                            $name = isset($resourcerecords[$resource->resourceid]) ? $resourcerecords[$resource->resourceid]->title : $resource->resourceid; ?>
                            <tr>
                                <td><?php echo s($name); ?></td>
                                <td class="text-center"><span class="badge-count"><?php echo $resource->totalviews; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card analytics-card h-100">
                <div class="card-header">
                    <i class="fa fa-download header-icon"></i>
                    Top Downloaded Resources
                </div>
                <div class="card-body">
                    <table class="table table-enhanced">
                        <thead>
                        <tr>
                            <th>Resource</th>
                            <th class="text-center">Downloads</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($topdownloads as $resource):
                            $name = isset($resourcerecords[$resource->resourceid]) ? $resourcerecords[$resource->resourceid]->title : $resource->resourceid; ?>
                            <tr>
                                <td><?php echo s($name); ?></td>
                                <td class="text-center"><span class="badge-count"><?php echo $resource->totaldownloads; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top active users & Activity Breakdown Pie Chart -->
    <div class="row g-4 mt-4">
        <div class="col-lg-6">
            <div class="card analytics-card h-100">
                <div class="card-header">
                    <i class="fa fa-users header-icon"></i>
                    Top Active Users
                </div>
                <div class="card-body">
                    <table class="table table-enhanced">
                        <thead>
                        <tr>
                            <th>User</th>
                            <th class="text-center">Activities</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($topusers as $item):
                            $username = isset($topuserrecords[$item->userid]) ? fullname($topuserrecords[$item->userid]) : '-'; ?>
                            <tr>
                                <td><?php echo s($username); ?></td>
                                <td class="text-center"><span class="badge-count"><?php echo $item->activitycount; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card analytics-card h-100">
                <div class="card-header">
                    <i class="fa fa-chart-pie header-icon"></i>
                    Activity Breakdown
                </div>
                <div class="card-body">
                    <div class="chart-wrapper">
                        <div class="pie-chart-container">
                            <div class="pie-chart">
                                <?php
                                $conicGradient = '';
                                $startAngle = 0;
                                foreach ($pieData as $index => $item):
                                    if ($item['angle'] > 0):
                                        $conicGradient .= $item['color'] . ' ' . $startAngle . 'deg ' . ($startAngle + $item['angle']) . 'deg';
                                        if ($index < count($pieData) - 1) {
                                            $conicGradient .= ', ';
                                        }
                                        $startAngle += $item['angle'];
                                    endif;
                                endforeach;
                                ?>
                                <div class="pie-slice" style="background: conic-gradient(<?php echo $conicGradient; ?>);">
                                    <?php if ($totalActivities > 0): ?>
                                        <div class="pie-center">
                                            <span class="pie-total"><?php echo $totalActivities; ?></span>
                                            <span class="pie-label">Total</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="chart-legend">
                            <?php foreach ($activityData as $item): ?>
                                <div class="legend-item">
                                    <span class="legend-color" style="background-color: <?php echo $item['color']; ?>"></span>
                                    <span class="legend-label"><?php echo $item['label']; ?></span>
                                    <span class="legend-value"><?php echo $item['value']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Most Active Courses -->
    <div class="card analytics-card mt-4">
        <div class="card-header">
            <i class="fa fa-graduation-cap header-icon"></i>
            Most Active Courses
        </div>
        <div class="card-body">
            <table class="table table-enhanced">
                <thead>
                <tr>
                    <th>Course</th>
                    <th class="text-center">Activities</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($topcourses as $item):
                    $coursename = isset($courses[$item->courseid]) ? $courses[$item->courseid]->fullname : '-'; ?>
                    <tr>
                        <td><?php echo s($coursename); ?></td>
                        <td class="text-center"><span class="badge-count"><?php echo $item->totalactivities; ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="card analytics-card mt-4">
        <div class="card-header">
            <i class="fa fa-clock header-icon"></i>
            Recent Activities
        </div>
        <div class="card-body">
            <table class="table table-enhanced">
                <thead>
                <tr>
                    <th>User</th>
                    <th>Action</th>
                    <th>Resource</th>
                    <th>Course</th>
                    <th>Time</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentactivities as $activity):
                    $resourcename = '-';
                    $username = isset($users[$activity->userid]) ? fullname($users[$activity->userid]) : '-';
                    $coursename = isset($courses[$activity->courseid]) ? $courses[$activity->courseid]->fullname : '-';
                    $action = $actionlabels[$activity->action] ?? $activity->action;
                    if (!empty($activity->resourceid) && isset($resourcerecords[$activity->resourceid])) {
                        $resourcename = $resourcerecords[$activity->resourceid]->title;
                    }
                    ?>
                    <tr>
                        <td><span class="user-name"><?php echo $username; ?></span></td>
                        <td><span class="activity-tag"><?php echo $action; ?></span></td>
                        <td><?php echo s($resourcename); ?></td>
                        <td><?php echo s($coursename); ?></td>
                        <td><span class="time-stamp"><?php echo userdate($activity->timecreated); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php echo $OUTPUT->footer(); ?>