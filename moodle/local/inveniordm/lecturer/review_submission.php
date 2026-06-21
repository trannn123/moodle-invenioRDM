<?php

require_once(__DIR__.'/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT;

$submissionid = required_param('submissionid', PARAM_INT);

$submission = $DB->get_record('local_inveniordm_submissions', ['id' => $submissionid], '*', MUST_EXIST);
$assignment = $DB->get_record('local_inveniordm_assignments', ['id' => $submission->assignmentid], '*', MUST_EXIST);
$student = $DB->get_record('user', ['id' => $submission->studentid], '*', MUST_EXIST);
$context = context_course::instance($assignment->courseid);
require_capability('local/inveniordm:upload', $context);

$PAGE->set_url(new moodle_url('/local/inveniordm/lecturer/review_submission.php', ['submissionid' => $submissionid]));
$PAGE->requires->css(new moodle_url('/local/inveniordm/styles/review_submission.css'));
$PAGE->set_context($context);
$PAGE->set_title('Review Submission');

// Handle POST request only if not already published
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

        // Redirect with a success message (this will display once)
        redirect(
            new moodle_url(
                '/local/inveniordm/lecturer/review_submission.php',
                ['submissionid' => $submissionid]
            ),
            'Review saved successfully.'
        );
    }
}

// Reload submission for fresh data (in case of redirect we already have it, but this ensures consistency)
$submission = $DB->get_record('local_inveniordm_submissions', ['id' => $submissionid], '*', MUST_EXIST);

$backurl = new moodle_url('/local/inveniordm/lecturer/view_submissions.php', ['assignmentid' => $assignment->id]);

echo $OUTPUT->header();
?>

    <div class="mb-4">
        <a href="<?php echo $backurl; ?>" class="btn btn-outline-dark">
            <i class="fa fa-arrow-left"></i>
            Back to Submissions
        </a>
    </div>

    <div class="hero-section">
        <h1>Review Submission</h1>
        <p>Evaluate student work and provide feedback.</p>
    </div>

    <div class="review-card">
        <div class="submission-info">
            <div class="info-row">
                <span class="info-label">Student</span>
                <span><?php echo fullname($student); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Assignment</span>
                <span><?php echo s($assignment->name); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">File</span>
                <span><?php echo s($submission->filename); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Current Grade</span>
                <span><?php echo s($submission->grade ?: '-'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Current Feedback</span>
                <span><?php echo s($submission->feedback ?: '-'); ?></span>
            </div>
        </div>

        <form method="post">
            <!-- Display validation errors if any -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label">Grade <span class="text-danger">*</span></label>
                <?php if (empty($submission->published_to_invenio)): ?>
                    <input type="text" name="grade" class="form-control"
                           value="<?php echo s($submission->grade ?? ''); ?>"
                           required aria-required="true">
                <?php else: ?>
                    <input type="text" class="form-control" value="<?php echo s($submission->grade ?: '-'); ?>" disabled>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <label class="form-label">Feedback <span class="text-danger">*</span></label>
                <?php if (empty($submission->published_to_invenio)): ?>
                    <textarea name="feedback" class="form-control" rows="6"
                              required aria-required="true"><?php echo s($submission->feedback ?? ''); ?></textarea>
                <?php else: ?>
                    <textarea class="form-control" rows="6" disabled><?php echo s($submission->feedback ?: '-'); ?></textarea>
                <?php endif; ?>
            </div>

            <div class="action-buttons">
                <?php if (empty($submission->published_to_invenio)): ?>
                    <button type="submit" class="btn btn-primary">Save Review</button>
                <?php endif; ?>

                <?php
                $canPublish = !empty($submission->grade) && !empty($submission->feedback) && empty($submission->published_to_invenio);
                if ($canPublish): ?>
                    <a href="publish_submission.php?submissionid=<?php echo $submissionid; ?>" class="btn btn-outline-primary">
                        Publish to Invenio
                    </a>
                <?php elseif (!empty($submission->published_to_invenio)): ?>
                    <span class="text-success">Successfully published to Invenio</span>
                <?php endif; ?>
            </div>
        </form>
    </div>

<?php
echo $OUTPUT->footer();