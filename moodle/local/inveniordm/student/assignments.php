<?php

require_once(__DIR__ . '/../../../config.php');
global $DB, $PAGE, $OUTPUT, $USER;

$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid]);
if (!$course) {
    throw new moodle_exception('invalidcourseid', 'error');
}
$context = context_course::instance($course->id);
require_login($course);

if (!is_enrolled($context, $USER)) {
    throw new moodle_exception('notenrolled', 'enrol');
}

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/student/assignments.php',
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

echo '<div class="container">';

$backurl = new moodle_url('/local/inveniordm/student/all_courses.php');
echo '
    <div class="page-hero">
        <div class="page-hero-content">
            <h1>
                <i class="fa fa-tasks"></i> Assignments
            </h1>
            <p>View and submit course assignments.</p>
        </div>
        <div class="hero-actions">
            <a href="' . $backurl . '" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Back to All Courses
            </a>
        </div>
    </div>
';

$assignments = $DB->get_records(
    'local_inveniordm_assignments',
    ['courseid' => $courseid],
    'duedate ASC'
);

if (!$assignments) {
    echo '
        <div class="alert-info-custom">
            <i class="fa fa-info-circle fa-3x"></i>
            <p>No assignments found</p>
            <div class="text-muted">This course currently has no assignments.</div>
        </div>
    ';
    echo '</div>';
    echo $OUTPUT->footer();
    exit;
}

echo '<div class="assignment-grid">';

foreach ($assignments as $a) {
    $submiturl = new moodle_url(
        '/local/inveniordm/student/submit_assignment.php',
        ['assignmentid' => $a->id]
    );

    $submission = $DB->get_record(
        'local_inveniordm_submissions',
        [
            'assignmentid' => $a->id,
            'studentid' => $USER->id
        ]
    );

    $submitted = !empty($submission);
    $statuslabel = $submitted ? 'Submitted' : 'Not Submitted';
    $badgeclass = $submitted ? 'badge-status status-active' : 'badge-status status-overdue';

    echo '
        <div class="assignment-card">
            <div class="assignment-card-header">
                <span class="assignment-title">' . s($a->name) . '</span>
                <span class="' . $badgeclass . '">' . $statuslabel . '</span>
            </div>
            <div class="assignment-card-body">
                <div class="assignment-info-row">
                    <span class="info-label">Due Date</span>
                    <span class="info-value">' . userdate($a->duedate, get_string('strftimedate', 'langconfig')) . '</span>
                </div>
                ' . (isset($a->description) && trim($a->description) ? '
                <div class="assignment-info-row">
                    <span class="info-label">Description</span>
                    <span class="info-value">' . s($a->description) . '</span>
                </div>
                ' : '') . '
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

echo '</div>';
echo '</div>';

echo $OUTPUT->footer();