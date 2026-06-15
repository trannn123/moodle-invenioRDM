<?php

require_once(__DIR__ . '/../../../config.php');
global $CFG, $PAGE, $OUTPUT;
require_login();

require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/controller/student_controller.php'
);

$context = context_system::instance();

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/student/search.php'
    )
);

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Search Records');
$PAGE->set_heading('Search Invenio Records');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/main.css'
    )
);
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/search.css'
    )
);
echo $OUTPUT->header();
echo '
<div class="mb-3">
    <a href="' . new moodle_url('/local/inveniordm/index.php') . '"
       class="btn btn-outline-secondary">
       <i class="fa fa-arrow-left"></i>
       Back to Dashboard
    </a>
</div>
';
$controller =
    new \local_inveniordm\controller\student_controller();
echo $controller->search();
echo $OUTPUT->footer();