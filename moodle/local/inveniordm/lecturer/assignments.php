<?php

require_once(__DIR__.'/../../../config.php');
global $DB, $PAGE, $OUTPUT;
$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id);
require_login($course);
require_capability('local/inveniordm:upload', $context);

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/assignments.php',
        ['courseid' => $courseid]
    )
);
$PAGE->set_context($context);
$PAGE->set_title('Assignments');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/main.css'
    )
);
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/assignments.css'
    )
);

echo $OUTPUT->header();

$backurl = new moodle_url('/local/inveniordm/lecturer/my_courses.php');
$reseturl = new moodle_url(
    '/local/inveniordm/lecturer/assignments.php',
    ['courseid' => $courseid]
);
$createurl = new moodle_url(
    '/local/inveniordm/lecturer/create_assignment.php',
    ['courseid' => $courseid]
);
$search = optional_param('search', '', PARAM_TEXT);

echo '
    <div class="courses-hero mb-4">
        <div class="courses-hero-content">
            <h1><i class="fa fa-tasks"></i> '.format_string($course->fullname).'</h1>
            <p>Manage assignments and review student submissions.</p>
        </div>
        <div class="courses-hero-actions">
            <a href="'.$backurl.'" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> 
                Back
            </a>
        </div>
    </div>
';

echo '
    <div class="search-card mb-4">
        <form method="get" class="search-form">
            <input type="hidden" name="courseid" value="'.$courseid.'">
            <div class="search-input-group">
                <input type="text" name="search" class="form-control" placeholder="Search assignments by name or ID..." value="'.s($search).'">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-search"></i> 
                    Search
                </button>
                <a href="'.$reseturl.'" class="btn btn-outline-secondary">
                    <i class="fa fa-refresh"></i> 
                    Reset
                </a>
                <a href="'.$createurl.'" class="btn btn-success">
                    <i class="fa fa-plus"></i> 
                    Create Assignment
                </a>
            </div>
        </form>
    </div>
';

$assignments = $DB->get_records(
    'local_inveniordm_assignments',
    ['courseid' => $courseid],
    'duedate ASC'
);

if (!empty($search)) {
    $assignments = array_filter(
        $assignments,
        function($a) use ($search) {
            return (
                stripos($a->name, $search) !== false ||
                stripos((string)$a->id, $search) !== false
            );
        }
    );
}

$totalassignments = count($assignments);

echo '
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa fa-tasks"></i></div>
            <div class="stat-content">
                <div class="stat-number">'.$totalassignments.'</div>
                <div class="stat-label">Assignments</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fa fa-hashtag"></i></div>
            <div class="stat-content">
                <div class="stat-number">'.$course->id.'</div>
                <div class="stat-label">Course ID</div>
            </div>
        </div>
    </div>
';

if (empty($assignments)) {
    echo '
        <div class="no-resources">
            <i class="fa fa-inbox fa-2x"></i>
            <p>No assignments found</p>
            <span class="text-muted">Create a new assignment to get started.</span>
        </div>
    ';
    echo $OUTPUT->footer();
    exit;
}

echo '<div class="assignment-grid">';

foreach ($assignments as $a) {
    $resources = $DB->get_records(
        'local_inveniordm_assignment_resources',
        ['assignmentid' => $a->id]
    );

    $submissionsurl = new moodle_url(
        '/local/inveniordm/lecturer/view_submissions.php',
        ['assignmentid' => $a->id]
    );

    $isoverdue = ($a->duedate > 0 && $a->duedate < time());
    $status = $isoverdue ? 'Overdue' : 'Active';
    $statusClass = $isoverdue ? 'status-overdue' : 'status-active';

    if (!$isoverdue && $a->duedate > 0) {
        $daysleft = ceil(($a->duedate - time()) / 86400);
        $remainingtext = $daysleft.' day(s) remaining';
    } else {
        $remainingtext = 'Deadline passed';
    }

    echo '
        <div class="assignment-card">
            <div class="assignment-card-header">
                <h3 class="assignment-title">'.format_string($a->name).'</h3>
                <span class="badge-status '.$statusClass.'">'.$status.'</span>
            </div>
            <div class="assignment-card-body">
                <div class="assignment-info-row">
                    <span class="info-label">Assignment ID</span>
                    <span class="info-value">'.$a->id.'</span>
                </div>
                <div class="assignment-info-row">
                    <span class="info-label">Due Date</span>
                    <span class="info-value">'.($a->duedate ? date('d/m/Y H:i', $a->duedate) : 'No due date').'</span>
                </div>
                <div class="assignment-info-row">
                    <span class="info-label">Timeline</span>
                    <span class="info-value">'.$remainingtext.'</span>
                </div>
                <div class="assignment-info-row">
                    <span class="info-label">Resources</span>
                    <span class="info-value">'.count($resources).' attached</span>
                </div>
    ';

    if (!empty($resources)) {
        echo '<div class="attached-resources">';
        echo '<strong>Attached Resources</strong>';
        echo '<ul class="resource-list">';
        foreach ($resources as $resource) {
            echo '<li>'.s($resource->title).'</li>';
        }
        echo '</ul>';
        echo '</div>';
    }

    if (!empty($a->instructions)) {
        echo '<div class="assignment-instructions">';
        echo format_text($a->instructions, FORMAT_HTML);
        echo '</div>';
    }

    echo '
            </div>
            <div class="assignment-card-actions">
                <a class="btn btn-primary w-100" href="'.$submissionsurl.'">
                    <i class="fa fa-users"></i> 
                    View Submissions
                </a>
            </div>
        </div>
    ';
}

echo '</div>';
echo $OUTPUT->footer();