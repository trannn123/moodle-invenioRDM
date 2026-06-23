<?php

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT, $USER;

$PAGE->set_url(new moodle_url('/local/inveniordm/student/all_assignments.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('All Assignments');
$PAGE->requires->css(
    new moodle_url('/local/inveniordm/styles/assignments.css')
);

echo $OUTPUT->header();

$courses = enrol_get_my_courses();
$search = optional_param('search', '', PARAM_TEXT);
$search = trim($search);

echo '
    <div class="hero-section">
        <h1>All Assignments</h1>
        <p>Browse all assignments across your enrolled courses.</p>
    </div>
';

$backurl = new moodle_url('/local/inveniordm/index.php');

echo '
    <form method="get" class="search-card mb-4">
        <div class="mb-3">
            <input type="text" name="search" class="form-control form-control-lg" placeholder="Search by assignment name or course..." value="'.s($search).'">
        </div>
    
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-primary">
                <i class="fa fa-search"></i>
                Search
            </button>
    
            <a href="'.$PAGE->url.'" class="btn btn-outline-secondary">
                <i class="fa fa-refresh"></i>
                Reset
            </a>
    
            <a href="'.$backurl.'" class="btn btn-outline-dark">
                <i class="fa fa-arrow-left"></i>
                Back
            </a>
        </div>
    </form>
';

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
                <h2>'.count($courses).'</h2>
                <p>Courses</p>
            </div>
        </div>
    </div>
';

if (empty($assignments)) {
    echo $OUTPUT->notification('No assignments found', 'info');
    echo $OUTPUT->footer();
    exit;
}

echo '<div class="row">';

foreach ($assignments as $item) {
    $course = $item['course'];
    $assignment = $item['assignment'];
    $coursecontext = context_course::instance($course->id);
    $isenrolled = is_enrolled($coursecontext, $USER->id);
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

    $statusbadge = $submitted
        ? '<span class="badge bg-success">Submitted</span>'
        : '<span class="badge bg-warning text-dark">Not Submitted</span>';

    echo '
        <div class="col-12 col-md-6 col-xl-4 mb-4">
            <div class="assignment-card">
                <div class="assignment-title">
                    '.format_string($assignment->name).'
                </div>
                <div class="assignment-content">
                    <div class="mb-2">
                        '.$statusbadge.'
                    </div>
                    <div class="assignment-description">
                        '.format_string($course->fullname).'
                    </div>
    ';
            if ($submitted) {
                echo '
                    <div class="mt-2 text-success">
                        <strong>Submitted file:</strong>
                        <span class="submission-file">
                            '.s($submission->filename).'
                        </span>
                    </div>
                ';
            }

                echo '
                    <div class="assignment-due">
                        Due:
                        '.(
                    $assignment->duedate
                        ? date('d/m/Y', $assignment->duedate)
                        : 'No due date'
                    ).'
                    </div>
                </div>
            
                <div class="submit-btn">
                    <a class="btn btn-outline-primary w-100" href="'.$submiturl.'">
                        Submit Assignment
                    </a>
                </div>
            </div>
        </div>
    ';
}

echo '</div>';
echo $OUTPUT->footer();