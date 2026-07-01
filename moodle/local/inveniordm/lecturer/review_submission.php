<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/lecturer_controller.php');
require_once(__DIR__ . '/../classes/service/submission_service.php');

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

$controller = new lecturer_controller();

$templatecontext = $controller->get_review_submission_context($_POST);

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_inveniordm/lecturer/review_submission',
    $templatecontext
);

echo $OUTPUT->footer();