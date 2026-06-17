<?php

require_once(__DIR__.'/../../../config.php');
global $DB, $PAGE, $OUTPUT;
$courseid = required_param('courseid', PARAM_INT);
$returnurl = optional_param('returnurl', '/local/inveniordm/lecturer/mycourses.php',PARAM_LOCALURL);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id);
require_login($course);
require_capability('local/inveniordm:upload', $context);
if (!has_capability('local/inveniordm:upload', $context)) {
    throw new moodle_exception('nopermission', 'error');
}
$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/assignments.php',
        [
            'courseid' => $courseid
        ]
    )
);
$PAGE->set_context($context);
$PAGE->set_title('Assignments');
$PAGE->set_heading('Assignments');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/courses_and_assignments.css'
    )
);

echo $OUTPUT->header();
echo '
    <div class="hero-section">
        <h1>'.format_string($course->fullname).'</h1>
        <p>Manage assignments and review student submissions.</p>
    </div>
';
$backurl = new moodle_url($returnurl);
$reseturl = new moodle_url(
    '/local/inveniordm/lecturer/assignments.php',
    [
        'courseid' => $courseid
    ]
);
$createurl = new moodle_url(
    '/local/inveniordm/lecturer/select_resource_for_assignment.php',
    [
        'courseid' => $courseid
    ]
);
$search = optional_param('search', '', PARAM_TEXT);

echo '
    <form method="get" class="search-card mb-4">
        <div class="mb-3">
            <a href="'.$backurl.'" class="btn btn-outline-dark">
                <i class="fa fa-arrow-left"></i>
                Back
            </a>
        </div>
        <input type="hidden" name="courseid" value="'.$courseid.'">
        <div class="mb-3">
            <input type="text" name="search" class="form-control form-control-lg" placeholder="Search assignments..." value="'.s($search).'">
        </div>
    
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="'.$reseturl.'" class="btn btn-outline-secondary">Reset</a>     
            <a href="'.$createurl.'" class="btn btn-outline-primary ms-auto">Create Assignment</a> 
        </div>
    </form>
';

$assignments = $DB->get_records(
    'local_inveniordm_assignments',
    [
        'courseid' => $courseid
    ],
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
<div class="row mb-4">
    <div class="col-md-6">
        <div class="stats-card">
            <h2>'.$totalassignments.'</h2>
            <p>Assignments</p>
        </div>
    </div>

    <div class="col-md-6">
        <div class="stats-card">
            <h2>'.$course->id.'</h2>
            <p>Course ID</p>
        </div>
    </div>
</div>
';

if (empty($assignments)) {
    echo $OUTPUT->notification('No assignments found.', 'info');
    echo $OUTPUT->footer();
    exit;
}

echo '<div class="course-grid">';

foreach ($assignments as $a) {
    $submissionsurl = new moodle_url(
        '/local/inveniordm/lecturer/view_submissions.php',
        [
            'assignmentid' => $a->id
        ]
    );

    $isoverdue = ($a->duedate > 0 && $a->duedate < time());
    $status = $isoverdue ? 'Overdue' : 'Active';

    if (!$isoverdue && $a->duedate > 0) {
        $daysleft = ceil(($a->duedate - time()) / 86400);
        $remainingtext = $daysleft.' day(s) remaining';
    } else {
        $remainingtext = 'Deadline passed';
    }

    echo '
        <div class="course-card">
            <div class="course-title">
                '.format_string($a->name).'
            </div>
            
            <div class="course-info-row">
                <strong>Assignment ID</strong>
                <span>'.$a->id.'</span>
            </div>
    
            <div class="course-info-row">
                <strong>Resource ID</strong>
                <span>'.s($a->resource_recordid).'</span>
            </div>
    
            <div class="course-info-row">
                <strong>Status</strong>
                <span>'.$status.'</span>
            </div>
    
            <div class="course-info-row">
                <strong>Due Date</strong>
                <span>'.($a->duedate ? date('d/m/Y H:i', $a->duedate) : 'No due date').'</span>
            </div>
    
            <div class="course-info-row">
                <strong>Timeline</strong>
                <span>'.$remainingtext.'</span>
            </div>
    
            <div class="mt-3 mb-3">
                '.(!empty($a->description) ? format_text($a->description, FORMAT_HTML) : '<em>No description</em>').'
            </div>
    
            <a class="btn btn-primary w-100" href="'.$submissionsurl.'">View Submissions</a>
        </div>
    ';
}

echo '</div>';
echo $OUTPUT->footer();