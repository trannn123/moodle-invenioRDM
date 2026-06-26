<?php

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT, $USER;

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/all_assignments.php'
    )
);
$PAGE->set_context(context_system::instance());
$PAGE->set_title('All Assignments');
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

$courses = enrol_get_my_courses();
$search = optional_param('search', '', PARAM_TEXT);
$search = trim($search);

$backurl = new moodle_url('/local/inveniordm/index.php');
$reseturl = $PAGE->url;

echo '
    <div class="page-hero">
        <div class="page-hero-content">
            <h1><i class="fa fa-tasks"></i> All Assignments</h1>
            <p>View assignments across your courses and monitor student submissions.</p>
        </div>
        <div class="hero-actions">
            <a href="' . $backurl . '" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> 
                Back
            </a>
        </div>
    </div>        
';

echo '
    <div class="search-card mb-4">
        <form method="get" class="search-form">
            <div class="search-input-group">
                <input type="text" name="search" class="form-control" placeholder="Search by assignment name or course..." value="' . s($search) . '">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-search"></i> 
                    Search
                </button>
                <a href="' . $reseturl . '" class="btn btn-outline-secondary">
                    <i class="fa fa-refresh"></i> 
                    Reset
                </a>
            </div>
        </form>
    </div>
';

$assignments = [];
foreach ($courses as $course) {
    if ($course->id == SITEID) {
        continue;
    }
    $context = context_course::instance($course->id);
    if (!has_capability('local/inveniordm:upload', $context)) {
        continue;
    }
    $courseassignments = $DB->get_records(
        'local_inveniordm_assignments',
        ['courseid' => $course->id]
    );
    foreach ($courseassignments as $assignment) {
        if (!empty($search)) {
            if (stripos($assignment->name, $search) === false &&
                stripos($course->fullname, $search) === false) {
                continue;
            }
        }
        $assignments[] = [
            'course' => $course,
            'assignment' => $assignment
        ];
    }
}

$totalassignments = count($assignments);

echo '
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa fa-tasks"></i></div>
            <div class="stat-content">
                <div class="stat-number">' . $totalassignments . '</div>
                <div class="stat-label">Assignments</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fa fa-book"></i></div>
            <div class="stat-content">
                <div class="stat-number">' . count($courses) . '</div>
                <div class="stat-label">Courses</div>
            </div>
        </div>
    </div>
';

if (empty($assignments)) {
    echo '
        <div class="alert-info-custom">
            <i class="fa fa-inbox fa-2x"></i>
            <p>No assignments found</p>
            <span class="text-muted">No assignments available across your teaching courses.</span>
        </div>
    ';
    echo $OUTPUT->footer();
    exit;
}

echo '<div class="assignment-grid">';

foreach ($assignments as $item) {
    $course = $item['course'];
    $assignment = $item['assignment'];

    $resourcecount = $DB->count_records(
        'local_inveniordm_assignment_resources',
        ['assignmentid' => $assignment->id]
    );
    $submissioncount = $DB->count_records(
        'local_inveniordm_submissions',
        ['assignmentid' => $assignment->id]
    );

    $status = ($assignment->duedate > 0 && $assignment->duedate < time()) ? 'Overdue' : 'Active';
    $statusClass = ($assignment->duedate > 0 && $assignment->duedate < time()) ? 'status-overdue' : 'status-active';

    $submissionsurl = new moodle_url(
        '/local/inveniordm/lecturer/view_submissions.php',
        ['assignmentid' => $assignment->id]
    );

    echo '
        <div class="assignment-card">
            <div class="assignment-card-header">
                <h3 class="assignment-title">' . format_string($assignment->name) . '</h3>
                <span class="badge-status ' . $statusClass . '">' . $status . '</span>
            </div>
            <div class="assignment-card-body">
                <div class="assignment-info-row">
                    <span class="info-label">Course</span>
                    <span class="info-value">' . format_string($course->fullname) . '</span>
                </div>
                <div class="assignment-info-row">
                    <span class="info-label">Assignment ID</span>
                    <span class="info-value">' . $assignment->id . '</span>
                </div>
                <div class="assignment-info-row">
                    <span class="info-label">Resources</span>
                    <span class="info-value">' . $resourcecount . '</span>
                </div>
                <div class="assignment-info-row">
                    <span class="info-label">Submissions</span>
                    <span class="info-value">' . $submissioncount . '</span>
                </div>
                <div class="assignment-info-row">
                    <span class="info-label">Due Date</span>
                    <span class="info-value">' . ($assignment->duedate ? date('d/m/Y H:i', $assignment->duedate) : 'No due date') . '</span>
                </div>
            </div>
            <div class="assignment-card-actions">
                <a class="btn btn-primary w-100" href="' . $submissionsurl . '">
                    <i class="fa fa-users"></i> 
                    View Submissions
                </a>
            </div>
        </div>
    ';
}

echo '</div>';
echo '</div>';
echo $OUTPUT->footer();