<?php

require_once(__DIR__.'/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT;

$assignmentid = required_param('assignmentid', PARAM_INT);

$assignment =
    $DB->get_record(
        'local_inveniordm_assignments',
        [
            'id' => $assignmentid
        ],
        '*',
        MUST_EXIST
    );

$context = context_course::instance($assignment->courseid);

require_capability(
    'local/inveniordm:upload',
    $context
);

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/view_submissions.php',
        [
            'assignmentid' => $assignmentid
        ]
    )
);

$PAGE->set_context($context);
$PAGE->set_title('Submissions');
$PAGE->set_heading('Submissions');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/view_submissions.css'
    )
);

echo $OUTPUT->header();

$submissions =
    $DB->get_records(
        'local_inveniordm_submissions',
        [
            'assignmentid' => $assignmentid
        ]
    );
$studentrole = $DB->get_record(
    'role',
    [
        'shortname' => 'student'
    ],
    '*',
    MUST_EXIST
);

$students = get_role_users($studentrole->id, $context);
$submissionmap = [];

foreach ($submissions as $submission) {
    $submissionmap[$submission->studentid] = $submission;
}

echo '
    <div class="hero-section">
        <h1>'.s($assignment->name).'</h1>
        <p>Review and download student submissions.</p>
    </div>
';

echo '
<div class="card mb-4">
    <div class="card-body">
        <h5>Instructions</h5>
        '.format_text(
        $assignment->instructions,
        FORMAT_HTML
    ).'
    </div>
</div>
';

$resources = $DB->get_records(
    'local_inveniordm_assignment_resources',
    [
        'assignmentid' => $assignmentid
    ]
);

if ($resources) {
    echo '
    <div class="card mb-4">
        <div class="card-body">
            <h5>Attached Resources</h5>
            <ul>
    ';
    foreach ($resources as $resource) {
        echo '<li>'.s($resource->title).'</li>';
    }
    echo '
            </ul>
        </div>
    </div>
    ';
}

$backurl = new moodle_url(
    '/local/inveniordm/lecturer/assignments.php',
    [
        'courseid' => $assignment->courseid
    ]
);

echo '
    <div class="mb-4">
        <a href="'.$backurl.'" class="btn btn-outline-dark">
            <i class="fa fa-arrow-left"></i>
            Back
        </a>
    </div>
';

$totalsubmissions = count($submissions);
$totalstudents = count($students);
$totalnotsubmited = $totalstudents - $totalsubmissions;

echo '
<div class="row mb-4">
    <div class="col-md-4">
        <div class="stats-card">
            <h2>'.$totalstudents.'</h2>
            <p>Students</p>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stats-card">
            <h2>'.$totalsubmissions.'</h2>
            <p>Submitted</p>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stats-card">
            <h2>'.$totalnotsubmited.'</h2>
            <p>Not Submitted</p>
        </div>
    </div>
</div>
';

$search = trim(optional_param('search', '', PARAM_TEXT));

echo '
    <form method="get" class="search-card mb-4">
        <input type="hidden" name="assignmentid" value="'.$assignmentid.'">
        <div class="mb-3">
            <input type="text" name="search" class="form-control form-control-lg" placeholder="Search student..."value="'.s($search).'">
        </div>
    
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
';

if (!$students) {
    echo $OUTPUT->notification('No students enrolled.', 'info');
} else {
    echo '
        <div class="table-responsive">
        <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Student</th>
                <th>Status</th>
                <th>File</th>
                <th>Submitted At</th>
                <th>Action</th>
                <th>Grade</th>
                <th>Feedback</th>
            </tr>
        </thead>
        <tbody>
    ';

    if (!empty($search)) {
        $students = array_filter(
            $students,
            function($student) use ($search) {
                return stripos(fullname($student), $search) !== false;
            }
        );
    }

    foreach ($students as $student) {
        $submission = $submissionmap[$student->id] ?? null;
        $submitted = !empty($submission);
        $status = $submitted
            ? '<span class="badge bg-success">Submitted</span>'
            : '<span class="badge bg-danger">Not Submitted</span>';

        $filename = $submitted
            ? s($submission->filename)
            : '-';

        $submittedate = $submitted
            ? userdate($submission->timecreated, '%d/%m/%Y %H:%M')
            : '-';

        if ($submitted) {
            $downloadurl = new moodle_url(
                '/local/inveniordm/lecturer/download_submission.php',
                [
                    'submissionid' => $submission->id
                ]
            );
            $reviewurl = new moodle_url(
                '/local/inveniordm/lecturer/review_submission.php',
                [
                    'submissionid' => $submission->id
                ]
            );
            $action = '
                <div class="action-buttons">
                    <a class="btn btn-sm btn-primary" href="'.$downloadurl.'">Download</a>
                    <a class="btn btn-sm btn-outline-primary" href="'.$reviewurl.'">Review</a>
                </div>
            ';
        } else {
            $action = '-';
        }

        $grade = $submission->grade ?? '-';
        $feedback = !empty($submission->feedback)
            ? s($submission->feedback)
            : '-';

        echo '
            <tr>
                <td>'.fullname($student).'</td>
                <td>'.$status.'</td>           
                <td>'.$filename.'</td>
                <td>'.$submittedate.'</td>
                <td>'.$action.'</td>
                <td>'.$grade.'</td>
                <td>'.$feedback.'</td>
            </tr>
        ';
    }
    $userids = [];

    echo '</table>';
}

echo $OUTPUT->footer();
