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

        /*
         * CREATE RECORD
         */
        $recordpayload = [
            'files' => [
                'enabled' => true
            ],

            'metadata' => [
                'title' => (
                    !empty(trim($data->title)) &&
                    strlen(trim($data->title)) >= 3
                )
                    ? trim($data->title)
                    : 'Moodle Resource Upload',

                'publication_date' => date('Y-m-d'),

                'resource_type' => [
                    'id' => 'publication-article'
                ],

                'creators' => [
                    [
                        'person_or_org' => [
                            'type' => 'personal',

                            'name' => fullname($USER),

                            'family_name' => $USER->lastname,

                            'given_name' => $USER->firstname
                        ]
                    ]
                ]
            ]
        ];

        $record =
            $client->create_record(
                $recordpayload
            );

        $recordid = $record['data']['id'] ?? null;

        if (!$recordid) {

        }

        /*
         * UPLOAD FILE
         */
        $uploadresult =
            $client->upload_file(
                $recordid,
                [
                    'name' => $filename,
                    'tmp_name' => $filepath
                ]
            );

        /*
         * PUBLISH RECORD
         */
        $publishurl =
            'https://ctu-it-rdm-frontend-1/api/records/' .
            $recordid .
            '/draft/actions/publish';

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $publishurl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Host: 127.0.0.1',
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer scPx1LLmZkoCjM4dkH3tDa3n1KzfZfvBxhwdHATFa8ZN2SO0Sm9Ds8D8VcjV'
            ],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POSTFIELDS => '{}',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $publishresponse = curl_exec($ch);

        $publishcode =
            curl_getinfo(
                $ch,
                CURLINFO_HTTP_CODE
            );

        curl_close($ch);
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