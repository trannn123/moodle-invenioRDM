<?php

require_once(__DIR__.'/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT;
$PAGE->set_url(
    new moodle_url('/local/inveniordm/admin/analytics.php')
);
$PAGE->set_context(context_system::instance());

echo $OUTPUT->header();

echo '
<div class="hero-section">
    <h1>Analytics Dashboard</h1>
    <p>
        Monitor repository activities, user interactions, and learning resource usage.
    </p>
</div>
';

$uploads = $DB->count_records(
'local_inveniordm_logs',
['action' => 'UPLOAD_RESOURCE']
);

$views = $DB->count_records(
'local_inveniordm_logs',
['action' => 'VIEW_RESOURCE']
);

$downloads = $DB->count_records(
'local_inveniordm_logs',
['action' => 'DOWNLOAD_RESOURCE']
);

$searches = $DB->count_records(
'local_inveniordm_logs',
['action' => 'SEARCH_RESOURCE']
);

$attachments = $DB->count_records(
'local_inveniordm_logs',
['action' => 'ATTACH_RESOURCE']
);

$submissions = $DB->count_records(
'local_inveniordm_logs',
['action' => 'SUBMIT_ASSIGNMENT']
);

echo '
    <div class="analytics-grid">
        <div class="stat-card">
            <h2>'.$uploads.'</h2>
            <p>Uploads</p>
        </div> 
        <div class="stat-card">
            <h2>'.$views.'</h2>
            <p>Views</p>
        </div>  
        <div class="stat-card">
            <h2>'.$downloads.'</h2>
            <p>Downloads</p>
        </div> 
        <div class="stat-card">
            <h2>'.$searches.'</h2>
            <p>Searches</p>
        </div>  
        <div class="stat-card">
            <h2>'.$attachments.'</h2>
            <p>Attachments</p>
        </div>
        <div class="stat-card">
            <h2>'.$submissions.'</h2>
            <p>Submissions</p>
        </div>
    </div>
';

$recentactivities =
    $DB->get_records_sql("
        SELECT * FROM {local_inveniordm_logs} 
        ORDER BY timecreated DESC LIMIT 10
    ");

$userids = [];

foreach ($recentactivities as $activity) {
    if (!empty($activity->userid)) {
        $userids[$activity->userid] = $activity->userid;
    }
}

$users = [];
$courseids = [];
$courses = [];

if ($userids) {
    list($sqlin, $params) = $DB->get_in_or_equal($userids);
    $users = $DB->get_records_select(
        'user',
        "id $sqlin",
        $params,
        '',
        'id,
         firstname,
         lastname,
         firstnamephonetic,
         lastnamephonetic,
         middlename,
         alternatename'
    );
}

foreach ($recentactivities as $activity) {
    if (!empty($activity->courseid)) {
        $courseids[$activity->courseid] = $activity->courseid;
    }
}


if ($courseids) {
    list($sqlin, $params) = $DB->get_in_or_equal($courseids);
    $courses = $DB->get_records_select(
        'course',
        "id $sqlin",
        $params,
        '',
        'id, fullname'
    );
}


$topresources =
    $DB->get_records_sql("
        SELECT resourceid, COUNT(*) AS totalviews
        FROM {local_inveniordm_logs}
        WHERE action = 'VIEW_RESOURCE'
        AND resourceid <> ''
        GROUP BY resourceid
        ORDER BY totalviews DESC LIMIT 5
    ");

$topdownloads =
    $DB->get_records_sql("
        SELECT resourceid,
               COUNT(*) AS totaldownloads
        FROM {local_inveniordm_logs}
        WHERE action = 'DOWNLOAD_RESOURCE'
        AND resourceid <> ''
        GROUP BY resourceid
        ORDER BY totaldownloads DESC
        LIMIT 5
    ");

$topusers =
    $DB->get_records_sql("
    SELECT userid, COUNT(*) AS activitycount
    FROM {local_inveniordm_logs}
    GROUP BY userid
    ORDER BY activitycount DESC
    LIMIT 5
");

$activitystats =
    $DB->get_records_sql("
        SELECT action,
               COUNT(*) AS total
        FROM {local_inveniordm_logs}
        GROUP BY action
        ORDER BY total DESC
    ");

$topcourses =
    $DB->get_records_sql("
        SELECT courseid,
               COUNT(*) AS totalactivities
        FROM {local_inveniordm_logs}
        WHERE courseid IS NOT NULL
        GROUP BY courseid
        ORDER BY totalactivities DESC
        LIMIT 5
    ");

foreach ($topcourses as $course) {
    if (!empty($course->courseid)) {
        $courseids[$course->courseid] = $course->courseid;
    }
}

$topuserids = [];

foreach ($topusers as $user) {
    $topuserids[$user->userid] = $user->userid;
}

$topuserrecords = [];

if ($topuserids) {
    list($sqlin, $params) = $DB->get_in_or_equal($topuserids);
    $topuserrecords = $DB->get_records_select(
        'user',
        "id $sqlin",
        $params,
        '',
        'id,
         firstname,
         lastname,
         firstnamephonetic,
         lastnamephonetic,
         middlename,
         alternatename'
    );
}

$actionlabels = [
    'UPLOAD_RESOURCE' => 'Upload Resource',
    'VIEW_RESOURCE' => 'View Resource',
    'DOWNLOAD_RESOURCE' => 'Download Resource',
    'SEARCH_RESOURCE' => 'Search Resource',
    'ATTACH_RESOURCE' => 'Attach Resource',
    'SUBMIT_ASSIGNMENT' => 'Submit Assignment'
];

echo '
<div class="card mt-4">
    <div class="card-body">
        <h3>Top Viewed Resources</h3>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Resource ID</th>
                    <th>Views</th>
                </tr>
            </thead>
            <tbody>
';

foreach ($topresources as $resource) {
    echo '
    <tr>
        <td>'.s($resource->resourceid).'</td>
        <td>'.$resource->totalviews.'</td>
    </tr>
    ';
}

echo '
            </tbody>
        </table>
    </div>
</div>
';

echo '
<div class="card mt-4">
    <div class="card-body">
        <h3>Top Downloaded Resources</h3>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Resource ID</th>
                    <th>Downloads</th>
                </tr>
            </thead>
            <tbody>
';

foreach ($topdownloads as $resource) {
    echo '
    <tr>
        <td>'.s($resource->resourceid).'</td>
        <td>'.$resource->totaldownloads.'</td>
    </tr>
    ';
}

echo '
            </tbody>
        </table>
    </div>
</div>
';

echo '
<div class="card mt-4">
    <div class="card-body">
        <h3>Top Active Users</h3>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Activities</th>
                </tr>
            </thead>
            <tbody>
';

foreach ($topusers as $item) {

    $username = '-';

    if (isset($topuserrecords[$item->userid])) {
        $user = $topuserrecords[$item->userid];
        $username = fullname($user);
    }

    echo '
    <tr>
        <td>'.s($username).'</td>
        <td>'.$item->activitycount.'</td>
    </tr>
    ';
}

echo '
            </tbody>
        </table>
    </div>
</div>
';

echo '
<div class="card mt-4">
    <div class="card-body">
        <h3>Activity Breakdown</h3>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
';

foreach ($activitystats as $item) {
    $action =
        $actionlabels[$item->action]
        ?? $item->action;
    echo '
    <tr>
        <td>'.$action.'</td>
        <td>'.$item->total.'</td>
    </tr>
    ';
}

echo '
            </tbody>
        </table>
    </div>
</div>
';

echo '
<div class="card mt-4">
    <div class="card-body">
        <h3>Most Active Courses</h3>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Activities</th>
                </tr>
            </thead>
            <tbody>
';

foreach ($topcourses as $item) {

    $coursename = '-';

    if (isset($courses[$item->courseid])) {
        $coursename =
            $courses[$item->courseid]->fullname;
    }

    echo '
    <tr>
        <td>'.s($coursename).'</td>
        <td>'.$item->totalactivities.'</td>
    </tr>
    ';
}

echo '
            </tbody>
        </table>
    </div>
</div>
';

echo '
    <div class="card mt-4">
        <div class="card-body">
            <h3>Recent Activities</h3>
            <table class="table table-striped">
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
';

                foreach ($recentactivities as $activity) {
                    $username = '-';

                    if (isset($users[$activity->userid])) {
                        $user = $users[$activity->userid];
                        $username = fullname($user);
                    }

                    $coursename = '-';

                    if (isset($courses[$activity->courseid])) {
                        $coursename = $courses[$activity->courseid]->fullname;
                    }

                    $action = $actionlabels[$activity->action] ?? $activity->action;

                    echo '
                    <tr>
                        <td>'.$username.'</td>
                        <td>'.$action.'</td>
                        <td>'.s($activity->resourceid).'</td>
                        <td>'.s($coursename).'</td>
                        <td>'.userdate($activity->timecreated).'</td>
                    </tr>
                    ';
                }

                echo '
                </tbody>
            </table>
        </div>
    </div>
    ';

echo $OUTPUT->footer();