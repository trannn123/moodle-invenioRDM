<?php

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT;

$assignmentid = required_param('assignmentid', PARAM_INT);

$assignment = $DB->get_record(
    'local_inveniordm_assignments',
    ['id' => $assignmentid],
    '*',
    MUST_EXIST
);

$context = context_course::instance($assignment->courseid);
require_capability('local/inveniordm:upload', $context);

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/view_submissions.php',
        ['assignmentid' => $assignmentid]
    )
);
$PAGE->set_context($context);
$PAGE->set_title('Submissions');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/main.css'
    )
);
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/view_submissions.css'
    )
);

echo $OUTPUT->header();

$submissions = $DB->get_records(
    'local_inveniordm_submissions',
    ['assignmentid' => $assignmentid]
);
$studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
$students = get_role_users($studentrole->id, $context);
$submissionmap = [];

foreach ($submissions as $submission) {
    $submissionmap[$submission->studentid] = $submission;
}

echo '<div class="container">';

$backurl = new moodle_url(
    '/local/inveniordm/lecturer/assignments.php',
    ['courseid' => $assignment->courseid]
);
echo '
    <div class="page-hero">
        <div class="page-hero-content">
            <h1><i class="fa fa-file-text-o"></i> ' . s($assignment->name) . '</h1>
            <p>Review and download student submissions.</p>
        </div>
        <div class="hero-actions">
            <a href="' . $backurl . '" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
';

echo '
<div class="invenio-detail-card mb-4">
    <div class="card-header">
        <h5><i class="fa fa-info-circle"></i> Instructions</h5>
    </div>
    <div class="card-body">
        ' . format_text($assignment->instructions, FORMAT_HTML) . '
    </div>
</div>
';

$resources = $DB->get_records(
    'local_inveniordm_assignment_resources',
    ['assignmentid' => $assignmentid]
);
if ($resources) {
    echo '
        <div class="invenio-detail-card mb-4">
            <div class="card-header">
                <h5><i class="fa fa-paperclip"></i> Attached Resources</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
    ';
    foreach ($resources as $resource) {
        echo '<li><i class="fa fa-file-text-o text-primary me-2"></i>' . s($resource->title) . '</li>';
    }
    echo '
                </ul>
            </div>
        </div>
    ';
}

$totalsubmissions = count($submissions);
$totalstudents = count($students);
$totalnotsubmited = $totalstudents - $totalsubmissions;

echo '
    <div class="stats-grid" style="margin-top: 24px; margin-bottom: 24px;">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa fa-users"></i></div>
            <div>
                <div class="stat-number">' . $totalstudents . '</div>
                <div class="stat-label">Students</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
            <div>
                <div class="stat-number">' . $totalsubmissions . '</div>
                <div class="stat-label">Submitted</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fa fa-clock-o"></i></div>
            <div>
                <div class="stat-number">' . $totalnotsubmited . '</div>
                <div class="stat-label">Not Submitted</div>
            </div>
        </div>
    </div>
';

$search = trim(optional_param('search', '', PARAM_TEXT));
echo '
    <div class="search-card mb-4">
        <form method="get" class="search-form" action="' . $PAGE->url . '">
            <input type="hidden" name="assignmentid" value="' . $assignmentid . '">
            <div class="search-input-group">
                <input type="text" name="search" class="form-control" 
                       placeholder="Search student..." value="' . s($search) . '">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-search"></i> Search
                </button>
            </div>
        </form>
    </div>
';

if (!$students) {
    echo '<div class="alert alert-info">No students enrolled.</div>';
} else {
    echo '
        <div class="submissions-table-wrapper">
            <div class="table-header">
                <h3><i class="fa fa-list-ul"></i> Submissions List</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-hover submissions-table">
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
            function ($student) use ($search) {
                return stripos(fullname($student), $search) !== false;
            }
        );
    }

    foreach ($students as $student) {
        $submission = $submissionmap[$student->id] ?? null;
        $submitted = !empty($submission);

        $status = $submitted
            ? '<span class="badge-status status-active">Submitted</span>'
            : '<span class="badge-status status-overdue">Not Submitted</span>';

        $filename = $submitted ? s($submission->filename) : '-';
        $submittedate = $submitted
            ? userdate($submission->timecreated, '%d/%m/%Y %H:%M')
            : '-';

        if ($submitted) {
            $downloadurl = new moodle_url(
                '/local/inveniordm/lecturer/download_submission.php',
                ['submissionid' => $submission->id]
            );
            $reviewurl = new moodle_url(
                '/local/inveniordm/lecturer/review_submission.php',
                ['submissionid' => $submission->id]
            );
            $action = '
                <div class="action-buttons">
                    <a class="btn btn-sm btn-primary action-btn" href="' . $downloadurl . '">
                        <i class="fa fa-download"></i> Download
                    </a>
                    <a class="btn btn-sm btn-outline-primary action-btn" href="' . $reviewurl . '">
                        <i class="fa fa-eye"></i> Review
                    </a>
                </div>
            ';
        } else {
            $action = '<span class="text-muted">—</span>';
        }

        $grade = $submission->grade ?? '-';
        $feedback = !empty($submission->feedback) ? s($submission->feedback) : '-';

        echo '
            <tr>
                <td class="student-name"><strong>' . fullname($student) . '</strong></td>
                <td>' . $status . '</td>
                <td>' . $filename . '</td>
                <td>' . $submittedate . '</td>
                <td>' . $action . '</td>
                <td><span class="grade-badge">' . $grade . '</span></td>
                <td>' . $feedback . '</td>
            </tr>
        ';
    }

    echo '
                    </tbody>
                </table>
            </div>
        </div>
    ';
}

echo '</div>';

echo $OUTPUT->footer();