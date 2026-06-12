<?php

require_once(__DIR__.'/../../../config.php');

require_login();

global $DB, $PAGE, $OUTPUT;

$assignmentid =
    required_param(
        'assignmentid',
        PARAM_INT
    );

$assignment =
    $DB->get_record(
        'local_inveniordm_assignments',
        [
            'id' => $assignmentid
        ],
        '*',
        MUST_EXIST
    );

$context =
    context_course::instance(
        $assignment->courseid
    );

require_capability(
    'local/inveniordm:upload',
    $context
);

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/view_submissions.php',
        [
            'assignmentid' =>
                $assignmentid
        ]
    )
);

$PAGE->set_context($context);

$PAGE->set_title(
    'Submissions'
);

$PAGE->set_heading(
    'Submissions'
);

echo $OUTPUT->header();
$submissions =
    $DB->get_records(
        'local_inveniordm_submissions',
        [
            'assignmentid' =>
                $assignmentid
        ]
    );
echo '
<h2>
'.s($assignment->name).'
</h2>
';
if (!$submissions) {

    echo '<p>No submissions.</p>';

} else {

    echo '
    <table class="table">

        <tr>
            <th>Student</th>
            <th>File</th>
            <th>Submitted</th>
        </tr>
    ';

    foreach ($submissions as $s) {
        $downloadurl =
            new moodle_url(
                '/local/inveniordm/lecturer/download_submission.php',
                [
                    'submissionid' => $s->id
                ]
            );
        $user =
            $DB->get_record(
                'user',
                [
                    'id' => $s->userid
                ]
            );

        echo '
        <tr>

            <td>
                '.fullname($user).'
            </td>

            <td>
                '.s($s->filename).'
            </td>

            <td>
                '.date(
                'd/m/Y H:i',
                $s->timecreated
            ).'
            </td>
            <td>
                <a
                    class="btn btn-secondary"
                    href="'.$downloadurl.'"
                >
                    Download
                </a>
            </td>
        </tr>
        ';
    }

    echo '</table>';
}
echo $OUTPUT->footer();
