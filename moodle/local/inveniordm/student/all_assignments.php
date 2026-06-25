<?php

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT, $USER;

$PAGE->set_url(new moodle_url('/local/inveniordm/student/all_assignments.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('All Assignments');
$PAGE->requires->css(
    new moodle_url('/local/inveniordm/styles/assignments.css') // contains all the CSS you provided
);

echo $OUTPUT->header();

// Start container
echo '<div class="container">';

// Hero Section
$backurl = new moodle_url('/local/inveniordm/index.php');
echo '
<div class="courses-hero">
    <div class="courses-hero-content">
        <h1>
            <i class="fa fa-tasks"></i> All Assignments
        </h1>
        <p>Browse all assignments across your enrolled courses.</p>
    </div>
    <div class="courses-hero-actions">
        <a href="' . $backurl . '" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    </div>
</div>
';

// Search
$search = optional_param('search', '', PARAM_TEXT);
$search = trim($search);
echo '
<div class="search-card mb-4">
    <form method="get" class="search-form" action="' . $PAGE->url . '">
        <div class="search-input-group">
            <input type="text" name="search" class="form-control" 
                   placeholder="Search by assignment name or course..." 
                   value="' . s($search) . '">
            <button class="btn btn-primary" type="submit">
                <i class="fa fa-search"></i> Search
            </button>
            <a href="' . $PAGE->url . '" class="btn btn-outline-secondary">
                <i class="fa fa-refresh"></i> Reset
            </a>
        </div>
    </form>
</div>
';

// Fetch data
$courses = enrol_get_my_courses();
$assignments = [];

foreach ($courses as $course) {
    if ($course->id == SITEID) {
        continue;
    }
    $localassignments = $DB->get_records(
        'local_inveniordm_assignments',
        ['courseid' => $course->id]
    );
    foreach ($localassignments as $assignment) {
        if (!empty($search)) {
            if (stripos($assignment->name, $search) === false &&
                stripos($course->fullname, $search) === false) {
                continue;
            }
        }
        $assignments[] = ['course' => $course, 'assignment' => $assignment];
    }
}

$totalassignments = count($assignments);
$totalcourses = count($courses);

// Stats
echo '
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fa fa-file-alt"></i></div>
        <div>
            <div class="stat-number">' . $totalassignments . '</div>
            <div class="stat-label">Assignments</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa fa-book"></i></div>
        <div>
            <div class="stat-number">' . $totalcourses . '</div>
            <div class="stat-label">Courses</div>
        </div>
    </div>
</div>
';

if (empty($assignments)) {
    echo '
    <div class="alert-info-custom">
        <i class="fa fa-info-circle fa-3x"></i>
        <p>No assignments found</p>
        <div class="text-muted">You are not enrolled in any courses with assignments, or your search returned no results.</div>
    </div>
    ';
    echo '</div>'; // close container
    echo $OUTPUT->footer();
    exit;
}

// Assignment Grid
echo '<div class="assignment-grid">';

foreach ($assignments as $item) {
    $course = $item['course'];
    $assignment = $item['assignment'];
    $submission = $DB->get_record(
        'local_inveniordm_submissions',
        [
            'assignmentid' => $assignment->id,
            'studentid' => $USER->id
        ]
    );
    $submitted = !empty($submission);
    $submiturl = new moodle_url(
        '/local/inveniordm/student/submit_assignment.php',
        ['assignmentid' => $assignment->id]
    );

    // Status badge
    $statusclass = $submitted ? 'status-active' : 'status-overdue';
    $statuslabel = $submitted ? 'Submitted' : 'Not Submitted';
    $badge = '<span class="badge-status ' . $statusclass . '">' . $statuslabel . '</span>';

    echo '
    <div class="assignment-card">
        <div class="assignment-card-header">
            <span class="assignment-title">' . format_string($assignment->name) . '</span>
            ' . $badge . '
        </div>
        <div class="assignment-card-body">
            <div class="assignment-info-row">
                <span class="info-label">Course</span>
                <span class="info-value">' . format_string($course->fullname) . '</span>
            </div>
            <div class="assignment-info-row">
                <span class="info-label">Due Date</span>
                <span class="info-value">' . ($assignment->duedate ? userdate($assignment->duedate, get_string('strftimedate', 'langconfig')) : 'No due date') . '</span>
            </div>
            ' . ($submitted ? '
            <div class="assignment-info-row">
                <span class="info-label">Submitted File</span>
                <span class="info-value">' . s($submission->filename) . '</span>
            </div>
            ' : '') . '
        </div>
        <div class="assignment-card-actions">
            <a class="btn ' . ($submitted ? 'btn-outline-primary' : 'btn-primary') . '" href="' . $submiturl . '">
                <i class="fa ' . ($submitted ? 'fa-eye' : 'fa-upload') . '"></i>
                ' . ($submitted ? 'View Submission' : 'Submit Assignment') . '
            </a>
        </div>
    </div>
    ';
}

echo '</div>'; // end assignment-grid
echo '</div>'; // end container

echo $OUTPUT->footer();