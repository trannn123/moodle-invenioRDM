<?php

require_once(__DIR__.'/../../../config.php');
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
        '/local/inveniordm/styles/course_assignments.css'
    )
);

echo $OUTPUT->header();

$assignments = $DB->get_records(
    'local_inveniordm_assignments',
    [
        'courseid' => $courseid
    ],
    'duedate ASC'
);

echo '
    <div class="hero-section">
        <h1>Assignments</h1>
        <p>View and submit course assignments.</p>
    </div>
';

$backurl = new moodle_url('/local/inveniordm/student/all_courses.php');

echo '
    <div class="mb-4">
        <a href="'.$backurl.'" class="btn btn-outline-secondary">
           <i class="fa fa-arrow-left"></i>
           Back to All Courses
        </a>
    </div>
';

if (!$assignments) {
    echo $OUTPUT->notification('No assignments found', 'info');

} else {
    echo '<div class="assignment-grid">';

    foreach ($assignments as $a) {
        $submiturl = new moodle_url(
            '/local/inveniordm/student/submit_assignment.php',
            [
                'assignmentid' => $a->id
            ]
        );

        $submission = $DB->get_record(
            'local_inveniordm_submissions',
            [
                'assignmentid' => $a->id,
                'studentid' => $USER->id
            ]
        );

        $submitted = !empty($submission);

        $statusbadge = $submitted
            ? '<span class="badge bg-success">Submitted</span>'
            : '<span class="badge bg-warning text-dark">Not Submitted</span>';

        echo '
            <div class="assignment-card">
                <div class="assignment-title">'.s($a->name).'</div>
                <div class="mb-2">'.$statusbadge.'</div>
                <div class="assignment-due">Due:'.date('d/m/Y', $a->duedate).'</div>
        ';

        if ($submitted) {
            $submissioninfo = '
                <div class="mt-2 text-success">
                    <strong>Submitted file:</strong>
                    <span class="submission-file">
                        '.s($submission->filename).'
                    </span>
                </div>
            ';
                } else {
                    $submissioninfo = '';
                }
                echo $submissioninfo;
                echo '
                <div class="mt-3 submit-btn">
                    <a class="btn btn-outline-primary w-100"
                       href="'.$submiturl.'">
                        Submit Assignment
                    </a>
                </div>
            </div>
        ';
    }
    echo '</div>';
}
echo $OUTPUT->footer();