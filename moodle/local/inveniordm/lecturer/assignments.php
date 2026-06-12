<?php

require_once(__DIR__.'/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT;
$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);

require_capability(
    'local/inveniordm:upload',
    $context
);

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
echo $OUTPUT->header();
echo '<h2>Assignments</h2>';
$assignments = $DB->get_records(
    'local_inveniordm_assignments',
    [
        'courseid' => $courseid
    ],
    'timecreated DESC'
);

if (!$assignments) {

    echo '<p>No assignments found.</p>';

} else {

    foreach ($assignments as $a) {
        $submissionsurl = new moodle_url(
            '/local/inveniordm/lecturer/view_submissions.php',
            [
                'assignmentid' => $a->id
            ]
        );
        echo '
        <div style="
            border:1px solid #ddd;
            padding:15px;
            margin-bottom:10px;
        ">
            <h4>'.s($a->name).'</h4>

            <p>
                '.s($a->description).'
            </p>

            <p>
                Due:
                '.date(
                'd/m/Y',
                $a->duedate
            ).'
            </p>
            <a
                class="btn btn-primary"
                href="'.$submissionsurl.'"
            >
                View Submissions
            </a>
        </div>
        ';
    }
}
echo $OUTPUT->footer();