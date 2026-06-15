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
        '/local/inveniordm/styles/upload.css'
    )
);

$form = new \local_inveniordm\form\upload_form();
echo $OUTPUT->header();
if ($form->is_cancelled()) {
    redirect(new moodle_url('/'));
} else if ($data = $form->get_data()) {
    $fs = get_file_storage();
    $usercontext = context_user::instance($USER->id);
    $files = $fs->get_area_files(
        $usercontext->id,
        'user',
        'draft',
        $data->resourcefile,
        'id',
        false
    );
    $filepath = '';
    $filename = '';
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

    if (empty($filepath)) {
        echo $OUTPUT->notification(
            'No uploaded file found',
            'error'
        );

    } else {
        $client =
            new \local_inveniordm\api\invenio_client();
        $recordpayload =
            \local_inveniordm\service\invenio_mapper::map(
                $data,
                $USER
            );
        $record =
            $client->create_record(
                $recordpayload
            );
        $recordid = $record['data']['id'] ?? null;

        if (!$recordid) {

        }
        $uploadresult =
            $client->upload_file(
                $recordid,
                [
                    'name' => $filename,
                    'tmp_name' => $filepath
                ]
            );
        $publishresult =
            $client->publish_record(
                $recordid
            );

        $publishcode =
            $publishresult['httpcode'];
        if ($publishcode >= 200 && $publishcode < 300) {
            echo $OUTPUT->notification(
                'Upload resource successfully!',
                'success'
            );
            echo '
                    <a 
                        href="' . $CFG->wwwroot . '/local/inveniordm/lecturer/upload.php"
                        class="btn btn-primary"
                    >
                        Back to Upload Page
                    </a>
            ';
        } else {
            echo $OUTPUT->notification(
                'Upload or publish failed!',
                'error'
            );
        }
    }

} else {
    $form->display();
}

echo $OUTPUT->footer();