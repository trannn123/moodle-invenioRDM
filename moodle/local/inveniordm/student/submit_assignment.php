<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/student_controller.php');
require_once(__DIR__ . '/../classes/service/submission_service.php');

require_login();
global $DB, $PAGE, $OUTPUT, $CFG, $USER;

require_once(
        $CFG->dirroot .
        '/local/inveniordm/classes/service/log_service.php'
);

$PAGE->requires->css(
        new moodle_url(
                '/local/inveniordm/styles/main.css'
        )
);
$PAGE->requires->css(
        new moodle_url(
                '/local/inveniordm/styles/submit_assignment.css'
        )
);

$PAGE->set_url(new moodle_url('/local/inveniordm/student/submit_assignment.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Submit Assignment');

$assignmentid = required_param('assignmentid', PARAM_INT);
$assignment = $DB->get_record(
        'local_inveniordm_assignments',
        ['id' => $assignmentid],
        '*',
        MUST_EXIST
);

$controller = new student_controller();

if (!empty($_POST) && !empty($_FILES['submission']['name'])) {
    $controller->submit_assignment($_POST, $_FILES);
    exit;
}

$submitted = $DB->get_record('local_inveniordm_submissions', [
        'assignmentid' => $assignmentid,
        'studentid' => $USER->id
]);

$context = [
        'assignmentid' => $assignmentid,
        'name' => format_string($assignment->name),
        'submitted' => !empty($submitted),
        'filename' => $submitted->filename ?? '',
        'submittedtime' => $submitted ? userdate($submitted->timecreated) : '',
        'expired' => time() > $assignment->duedate,
        'duedate' => userdate($assignment->duedate),
        'backurl' => new moodle_url('/local/inveniordm/student/assignments.php', [
                'courseid' => $assignment->courseid
        ]),
        'submiturl' => (new moodle_url('/local/inveniordm/student/submit_assignment.php', [
                'assignmentid' => $assignmentid
        ]))->out(false),
        'buttontext' => $submitted
                ? 'Resubmit Assignment'
                : 'Submit Assignment'
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template(
        'local_inveniordm/student/submit_assignment',
        $context
);
echo $OUTPUT->footer();