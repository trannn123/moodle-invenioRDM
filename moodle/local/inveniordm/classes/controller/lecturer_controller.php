<?php

namespace local_inveniordm\controller;

defined('MOODLE_INTERNAL') || die();

use local_inveniordm\form\upload_form;

class lecturer_controller {

    public function upload() {

        global $OUTPUT;

        $form = new upload_form();

        /*
         * Handle submit
         */
        if ($data = $form->get_data()) {

            $client = new \local_inveniordm\api\invenio_client();

            $metadata = [
                'title' => $data->title,
                'resource_type' => [
                    'id' => 'publication'
                ],
                'creators' => [
                    [
                        'person_or_org' => [
                            'type' => 'personal',
                            'name' => 'Unknown'
                        ]
                    ]
                ]
            ];

            // 1. create record
            $metadata['title'] = $metadata['title'] ?? 'Untitled';

            $payload = [
                'metadata' => $metadata,
                'is_published' => false
            ];

            $record = $client->create_record($payload);

            $record_id = $record['id'] ?? null;

            if (!$record_id) {
                return '<pre>CREATE FAILED</pre>';
            }

            // 2. get uploaded file path
            $tempfilepath = $form->save_temp_file('resourcefile');

            if (!$tempfilepath) {
                return '<pre>NO FILE UPLOADED</pre>';
            }

            // build fake $_FILES structure
            $file = [
                'name' => basename($tempfilepath),
                'tmp_name' => $tempfilepath
            ];

            // 3. upload file to invenio
            $upload = $client->upload_file($record_id, $file);
            return '<pre>' . print_r($upload, true) . '</pre>';
        }

        /*
         * Render form (GET request)
         */
        ob_start();

        $form->display();

        $formhtml = ob_get_clean();

        $context = [
            'formhtml' => $formhtml
        ];

        return $OUTPUT->render_from_template(
            'local_inveniordm/lecturer/upload',
            $context
        );
    }
}