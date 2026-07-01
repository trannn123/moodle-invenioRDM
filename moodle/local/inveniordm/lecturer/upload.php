<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/lecturer_controller.php');
require_once(__DIR__ . '/../classes/service/upload_service.php');

require_login();

global $CFG, $PAGE, $OUTPUT, $USER;

require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/form/upload_form.php'
);

$context = context_system::instance();

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/upload.php'
    )
);

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Upload Resource');

$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/main.css'
    )
);

$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/upload.css'
    )
);

$form = new \local_inveniordm\form\upload_form();

$controller = new lecturer_controller();

echo $OUTPUT->header();

if ($form->is_cancelled()) {
    redirect(
        new moodle_url('/')
    );

} else if ($data = $form->get_data()) {
    $result = $controller->process_upload(
        $data,
        $USER
    );

    if ($result['success']) {
        echo $OUTPUT->notification(
            $result['message'],
            'success'
        );

        echo '
            <a href="' .
            $CFG->wwwroot .
            '/local/inveniordm/lecturer/upload.php"
               class="btn btn-primary">
                Back to Upload Page
            </a>
        ';

    } else {
        echo $OUTPUT->notification(
            $result['message'],
            'error'
        );

    }

} else {
    $form->display();
}

echo $OUTPUT->footer();