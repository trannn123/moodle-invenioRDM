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

$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/review_submission.css'
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

$backurl = new moodle_url(
    '/local/inveniordm/lecturer/view_submissions.php',
    [
        'assignmentid' => $assignment->id
    ]
);

echo '
    <div class="hero-section">
        <h1>Review Submission</h1>
        <p>Evaluate student work and provide feedback.</p>
    </div>
    
    <div class="mb-4">
        <a href="'.$backurl.'" class="btn btn-outline-primary">
            <i class="fa fa-arrow-left"></i>
            Back to Submissions
        </a>
    </div>
    
    <div class="review-card">
        <div class="submission-info">
            <div class="info-row">
                <span class="info-label">Student</span>
                <span>'.fullname($student).'</span>
            </div>
    
            <div class="info-row">
                <span class="info-label">Assignment</span>
                <span>'.s($assignment->name).'</span>
            </div>
    
            <div class="info-row">
                <span class="info-label">File</span>
                <span>'.s($submission->filename).'</span>
            </div>
    
            <div class="info-row">
                <span class="info-label">Current Grade</span>
                <span>'.s($submission->grade ?: '-').'</span>
            </div>
    
            <div class="info-row">
                <span class="info-label">Current Feedback</span>
                <span>'.s($submission->feedback ?: '-').'</span>
            </div>
        </div>
    
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Grade</label>
                <input type="text" name="grade" class="form-control" value="'.s($submission->grade ?? '').'">
            </div>
    
            <div class="mb-4">
                <label class="form-label">Feedback</label>
                <textarea name="feedback" class="form-control" rows="6">'.s($submission->feedback ?? '').'</textarea>
            </div>
    
            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">Save Review</button>
';

if (empty($submission->published_to_invenio)) {
    echo '
        <a href="publish_submission.php?submissionid='.$submissionid.'" class="btn btn-outline-primary">Publish to Invenio</a>
    ';
} else {
    echo '
        <span class="badge bg-primary">
            Published to Invenio
        </span>
    ';
}

echo '
            </div>
        </form>
    </div>
';
echo $OUTPUT->footer();