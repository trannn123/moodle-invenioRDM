<?php

require_once(__DIR__ . '/../../../config.php');

require_login();

global $CFG, $PAGE, $OUTPUT, $USER;

require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/form/upload_form.php'
);

require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/controller/lecturer_controller.php'
);

require_once($CFG->dirroot . '/local/inveniordm/classes/service/invenio_mapper.php');

$context = context_system::instance();

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/upload.php'
    )
);

$PAGE->set_context($context);

$PAGE->set_pagelayout('standard');

$PAGE->set_title('Upload Resource');

$PAGE->set_heading('Upload Repository Resource');

$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/main.css'
    )
);

$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/upload_lecturer.css'
    )
);

$form = new \local_inveniordm\form\upload_form();

echo $OUTPUT->header();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/'));
}
else if ($data = $form->get_data()) {

    echo '<pre>';
    echo "FORM DATA\n";
    print_r($data);
    echo '</pre>';

    $payload = \local_inveniordm\service\invenio_mapper::map($data, $USER);

    echo "<h3>INVENIO PAYLOAD</h3>";
    echo "<pre>";
    print_r($payload);
    echo "</pre>";

    $apiUrl = "http://127.0.0.1:5001/api/records";

    echo "<h3>TEST API CONNECTION</h3>";
    $ch = curl_init($apiUrl);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    echo "<h3>INVENIO RESPONSE</h3>";
    echo "HTTP CODE: " . $httpCode . "<br>";
    echo "<pre>";
    echo $response;
    echo "</pre>";

    exit();
    $fs = get_file_storage();

    $context = context_user::instance($USER->id);

    $files = $fs->get_area_files(
        $context->id,
        'user',
        'draft',
        $data->resourcefile,
        'id',
        false
    );

    foreach ($files as $file) {

        echo "<h3>File Info</h3>";
        echo "Filename: " . $file->get_filename() . "<br>";

        $content = $file->get_content();
        echo "File size: " . strlen($content) . "<br>";

        break;
    }


    echo $OUTPUT->footer();
    exit();
}

$form->display();


echo $OUTPUT->footer();

