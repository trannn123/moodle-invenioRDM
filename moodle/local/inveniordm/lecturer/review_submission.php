<?php

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT;

$submissionid = required_param('submissionid', PARAM_INT);

$submission = $DB->get_record('local_inveniordm_submissions', ['id' => $submissionid], '*', MUST_EXIST);
$assignment = $DB->get_record('local_inveniordm_assignments', ['id' => $submission->assignmentid], '*', MUST_EXIST);
$student = $DB->get_record('user', ['id' => $submission->studentid], '*', MUST_EXIST);
$context = context_course::instance($assignment->courseid);
require_capability('local/inveniordm:upload', $context);

$PAGE->set_url(new moodle_url('/local/inveniordm/lecturer/review_submission.php', ['submissionid' => $submissionid]));
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/main.css'
    )
);
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/review_submission.css'
    )
);

$PAGE->set_context($context);
$PAGE->set_title('Review Submission');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($submission->published_to_invenio)) {
    $grade = trim(optional_param('grade', '', PARAM_TEXT));
    $feedback = trim(optional_param('feedback', '', PARAM_TEXT));

    if ($grade === '') {
        $errors['grade'] = 'Grade is required.';
    }
    if ($feedback === '') {
        $errors['feedback'] = 'Feedback is required.';
    }

    if (empty($errors)) {
        $DB->set_field('local_inveniordm_submissions', 'grade', $grade, ['id' => $submissionid]);
        $DB->set_field('local_inveniordm_submissions', 'feedback', $feedback, ['id' => $submissionid]);

        redirect(
            new moodle_url(
                '/local/inveniordm/lecturer/review_submission.php',
                ['submissionid' => $submissionid]
            ),
            'Review saved successfully.'
        );
    }
}

$submission = $DB->get_record('local_inveniordm_submissions', ['id' => $submissionid], '*', MUST_EXIST);

$backurl = new moodle_url('/local/inveniordm/lecturer/view_submissions.php', ['assignmentid' => $assignment->id]);

echo $OUTPUT->header();

echo '<div class="container">';

echo '
    <div class="page-hero">
        <div class="page-hero-content">
            <h1><i class="fa fa-check-circle"></i> Review Submission</h1>
            <p>Evaluate student work and provide feedback.</p>
        </div>
        <div class="hero-actions">
            <a href="' . $backurl . '" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Back to Submissions
            </a>
        </div>
    </div>
';

echo '<div class="review-card mt-4">';

echo '<div class="review-card-body">';
echo '<div class="review-info-row"><span class="info-label">Student </span><span class="info-value">' . fullname($student) . '</span></div>';
echo '<div class="review-info-row"><span class="info-label">Assignment </span><span class="info-value">' . s($assignment->name) . '</span></div>';
echo '<div class="review-info-row"><span class="info-label">File </span><span class="info-value">' . s($submission->filename) . '</span></div>';
echo '<div class="review-info-row"><span class="info-label">Current Grade </span><span class="info-value">' . s($submission->grade ?: '-') . '</span></div>';
echo '<div class="review-info-row"><span class="info-label">Current Feedback </span><span class="info-value">' . s($submission->feedback ?: '-') . '</span></div>';
echo '</div>';

echo '<form method="post">';

if (!empty($errors)) {
    echo '<div class="alert alert-danger"><ul class="mb-0">';
    foreach ($errors as $error) {
        echo '<li>' . $error . '</li>';
    }
    echo '</ul></div>';
}

echo '<div class="mb-3">';
echo '<label class="form-label">Grade <span class="text-danger">*</span></label>';
if (empty($submission->published_to_invenio)) {
    echo '<input type="text" name="grade" class="form-control" value="' . s($submission->grade ?? '') . '" required aria-required="true">';
} else {
    echo '<input type="text" class="form-control" value="' . s($submission->grade ?: '-') . '" disabled>';
}
echo '</div>';

echo '<div class="mb-4">';
echo '<label class="form-label">Feedback <span class="text-danger">*</span></label>';
if (empty($submission->published_to_invenio)) {
    echo '<textarea name="feedback" class="form-control" rows="6" required aria-required="true">' . s($submission->feedback ?? '') . '</textarea>';
} else {
    echo '<textarea class="form-control" rows="6" disabled>' . s($submission->feedback ?: '-') . '</textarea>';
}
echo '</div>';

echo '<div class="review-card-actions" style="border-top: none; padding-left: 0; padding-right: 0;">';
if (empty($submission->published_to_invenio)) {
    echo '<button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Review</button>';
}

$canPublish = !empty($submission->grade) && !empty($submission->feedback) && empty($submission->published_to_invenio);
if ($canPublish) {
    echo '<a href="publish_submission.php?submissionid=' . $submissionid . '" class="btn btn-outline-primary"><i class="fa fa-cloud-upload"></i> Publish to Invenio</a>';
} elseif (!empty($submission->published_to_invenio)) {
    echo '<span class="text-success"><i class="fa fa-check-circle"></i> Successfully published to Invenio</span>';
}
echo '</div>';

echo '</form>';
echo '</div>';

echo '</div>';

echo $OUTPUT->footer();