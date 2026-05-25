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
    '/local/inveniordm/classes/api/invenio_client.php'
);

require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/service/invenio_mapper.php'
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

} else if ($data = $form->get_data()) {

    $payload =
        \local_inveniordm\service\invenio_mapper::map(
            $data,
            $USER
        );

    $fs = get_file_storage();

    $usercontext =
        context_user::instance($USER->id);

    $files = $fs->get_area_files(
        $usercontext->id,
        'user',
        'draft',
        $data->resourcefile,
        'id',
        false
    );

    $filepath = '';

    foreach ($files as $file) {

        $filename =
            $file->get_filename();

        $fullpath =
            $CFG->dirroot .
            '/local/inveniordm/repository/' .
            time() . '_' .
            $filename;

        $file->copy_content_to(
            $fullpath
        );

        $filepath = $fullpath;

        break;
    }

    $relativepath =
        '/local/inveniordm/repository/' .
        basename($filepath);

    $payload['location'] =
        $relativepath;

    $client = new \local_inveniordm\api\invenio_client();

    $ok = $client->create_mock_record($payload);

    clearstatcache();

    if ($ok) {

        redirect(
            new moodle_url(
                '/local/inveniordm/student/search.php'
            ),
            'Resource uploaded successfully',
            2
        );

    } else {

        echo $OUTPUT->notification(
            'Upload failed',
            'error'
        );
    }

} else {

    $form->display();
}

echo $OUTPUT->footer();