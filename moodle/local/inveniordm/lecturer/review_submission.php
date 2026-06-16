<?php

require_once(__DIR__.'/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT;

$submissionid = required_param(
    'submissionid',
    PARAM_INT
);

$submission = $DB->get_record(
    'local_inveniordm_submissions',
    [
        'id' => $submissionid
    ],
    '*',
    MUST_EXIST
);

$assignment = $DB->get_record(
    'local_inveniordm_assignments',
    [
        'id' => $submission->assignmentid
    ],
    '*',
    MUST_EXIST
);

$student = $DB->get_record(
    'user',
    [
        'id' => $submission->studentid
    ],
    '*',
    MUST_EXIST
);

$context = context_course::instance(
    $assignment->courseid
);

require_capability('local/inveniordm:upload', $context);

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/review_submission.php',
        [
            'submissionid' => $submissionid
        ]
    )
);

$PAGE->set_context($context);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grade = trim(optional_param('grade', '', PARAM_TEXT));
    $feedback = trim(optional_param('feedback', '', PARAM_TEXT));
    $submission->grade = $grade;
    $submission->feedback = $feedback;

    $DB->set_field(
        'local_inveniordm_submissions',
        'grade',
        $grade,
        ['id' => $submissionid]
    );

    $DB->set_field(
        'local_inveniordm_submissions',
        'feedback',
        $feedback,
        ['id' => $submissionid]
    );

    redirect(
        new moodle_url(
            '/local/inveniordm/lecturer/review_submission.php',
            [
                'submissionid' => $submissionid
            ]
        ),
        'Review saved'
    );
}

$PAGE->set_title('Review Submission');
$PAGE->set_heading('Review Submission');
echo $OUTPUT->header();

echo '
    <h2>Review Submission</h2>
    <p>
        <strong>Student:</strong>
        '.fullname($student).'
    </p>
    
    <p>
        <strong>File:</strong>
        '.s($submission->filename).'
    </p>
    
    <p>
        <strong>Current Grade:</strong>
        '.s($submission->grade ?: '-').'
    </p>
    
    <p>
        <strong>Current Feedback:</strong>
        '.s($submission->feedback ?: '-').'
    </p>
    
    <form method="post"> 
        <div class="mb-3">
            <label class="form-label">Grade</label>
            <input type="text" name="grade" class="form-control" value="'.s($submission->grade ?? '').'">
        </div>
    
        <div class="mb-3">
            <label class="form-label">Feedback</label>
            <textarea name="feedback" class="form-control" rows="5">'.s($submission->feedback ?? '').'</textarea>
        </div>
    
        <button type="submit" class="btn btn-success">Save Review</button>
';

if (empty($submission->published_to_invenio)) {

    echo '
        <a href="publish_submission.php?submissionid='.$submissionid.'" class="btn btn-primary">Publish to Invenio</a>
    ';
} else {
    echo '
        <span class="badge bg-success">
            Published to Invenio
        </span>
    ';
}

echo '</form>';
echo $OUTPUT->footer();