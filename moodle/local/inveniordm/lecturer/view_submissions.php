<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/lecturer_controller.php');
require_once(__DIR__ . '/../classes/service/submission_service.php');

require_login();

global $DB, $PAGE, $OUTPUT;

$assignmentid = required_param('assignmentid', PARAM_INT);

$assignment = $DB->get_record(
    'local_inveniordm_assignments',
    [
        'id' => $assignmentid
    ],
    '*',
    MUST_EXIST
);

$context = context_course::instance(
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
            'assignmentid' => $assignmentid
        ]
    )
);

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Submissions');

$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/main.css'
    )
);

$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/view_submissions.css'
    )
);

$controller = new lecturer_controller();

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'local_inveniordm/lecturer/view_submissions',
    $controller->get_view_submissions_context()
);

echo $OUTPUT->footer();