<?php

require_once(__DIR__.'/../../../config.php');

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
        '/local/inveniordm/styles/assignments.css'
    )
);

echo $OUTPUT->header();

$courses = enrol_get_my_courses();

$search = optional_param('search', '', PARAM_TEXT);
$search = trim($search);

echo '
<div class="hero-section">
    <h1>All Assignments</h1>
    <p>
        View assignments across your courses and monitor student submissions.
    </p>
</div>
';

$backurl = new moodle_url(
    '/local/inveniordm/index.php'
);

echo '
<form method="get" class="search-card mb-4">

    <div class="mb-3">
        <input
            type="text"
            name="search"
            class="form-control form-control-lg"
            placeholder="Search by assignment name or course..."
            value="'.s($search).'">
    </div>

    <div class="d-flex flex-wrap gap-2">

        <button class="btn btn-primary">
            <i class="fa fa-search"></i>
            Search
        </button>

        <a href="'.$PAGE->url.'"
           class="btn btn-outline-secondary">
            <i class="fa fa-refresh"></i>
            Reset
        </a>

        <a href="'.$backurl.'"
           class="btn btn-outline-dark">
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

    $context = context_course::instance($course->id);

    if (!has_capability(
        'local/inveniordm:upload',
        $context
    )) {
        continue;
    }

    $courseassignments = $DB->get_records(
        'local_inveniordm_assignments',
        [
            'courseid' => $course->id
        ]
    );

    foreach ($courseassignments as $assignment) {

        if (!empty($search)) {

            if (
                stripos($assignment->name, $search) === false &&
                stripos($course->fullname, $search) === false
            ) {
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
    echo $OUTPUT->notification(
        'No assignments found.',
        'info'
    );

    echo $OUTPUT->footer();
    exit;
}

echo '<div class="row">';

foreach ($assignments as $item) {

    $course = $item['course'];
    $assignment = $item['assignment'];

    $resourcecount = $DB->count_records(
        'local_inveniordm_assignment_resources',
        [
            'assignmentid' => $assignment->id
        ]
    );

    $submissioncount = $DB->count_records(
        'local_inveniordm_submissions',
        [
            'assignmentid' => $assignment->id
        ]
    );

    $status = (
        $assignment->duedate > 0 &&
        $assignment->duedate < time()
    )
        ? 'Overdue'
        : 'Active';

    $submissionsurl = new moodle_url(
        '/local/inveniordm/lecturer/view_submissions.php',
        [
            'assignmentid' => $assignment->id
        ]
    );

    echo '
        <div class="col-12 col-md-6 col-xl-4 mb-4 d-flex">
            <div class="assignment-card w-100">
                <div class="assignment-title">
                    '.format_string($assignment->name).'
                </div>
                <div class="assignment-content">
                    <div class="course-info-row">
                        <strong>Course</strong>
                        <span class="text-end">
                            '.format_string($course->fullname).'
                        </span>
                    </div>
                    <div class="course-info-row">
                        <strong>Assignment ID</strong>
                        <span>'.$assignment->id.'</span>
                    </div>
                    <div class="course-info-row">
                        <strong>Status</strong>
                        <span>'.$status.'</span>
                    </div>
                    <div class="course-info-row">
                        <strong>Resources</strong>
                        <span>'.$resourcecount.'</span>
                    </div>
                    <div class="course-info-row">
                        <strong>Submissions</strong>
                        <span>'.$submissioncount.'</span>
                    </div>
                    <div class="assignment-due">
                        Due:
                        '.(
                        $assignment->duedate
                            ? date(
                            'd/m/Y H:i',
                            $assignment->duedate
                        )
                            : 'No due date'
                        ).'
                    </div>
                </div>
                <div class="submit-btn">
                    <a class="btn btn-primary w-100" href="'.$submissionsurl.'">
                        View Submissions
                    </a>
                </div>
            </div>
        </div>
    ';
}

echo '</div>';

echo $OUTPUT->footer();