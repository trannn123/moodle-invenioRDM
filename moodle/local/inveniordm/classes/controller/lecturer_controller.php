<?php

namespace local_inveniordm\controller;

defined('MOODLE_INTERNAL') || die();

use local_inveniordm\form\upload_form;

class lecturer_controller {

    public function upload() {
        die('NEW CODE RUNNING');
        global $OUTPUT, $USER;

        $form = new upload_form();

        /*
         * Handle submit
         */
        if ($data = $form->get_data()) {
            $client = new \local_inveniordm\api\invenio_client();

            $metadata = [
                'files' => [
                    'enabled' => true
                ],

                'metadata' => [

                    // title PHẢI >= 3 ký tự
                    'title' => 'Moodle Resource Upload',

                    'publication_date' => date('Y-m-d'),

                    'resource_type' => [
                        'id' => 'publication-article'
                    ],

                    'creators' => [
                        [
                            'person_or_org' => [

                                'type' => 'personal',

                                // fullname hiển thị
                                'name' => fullname($USER),

                                // REQUIRED
                                'family_name' => $USER->lastname,

                                // REQUIRED
                                'given_name' => $USER->firstname
                            ]
                        ]
                    ]
                ]
            ];
            die('<pre>' . print_r($metadata, true) . '</pre>');
            $record = $client->create_record($metadata);
            $record_id = $record['data']['id'] ?? null;

            if (!$record_id) {
                return '<pre>CREATE FAILED</pre>';
            }

            // 2. get uploaded file path
            $filename = $form->get_new_filename('resourcefile');

            $tempfilepath = $form->save_temp_file('resourcefile');

            if (!$tempfilepath || !$filename) {
                return '<pre>NO FILE UPLOADED</pre>';
            }

            $file = [
                'name' => $filename,
                'tmp_name' => $tempfilepath
            ];

            // 3. upload file to invenio
            $upload = $client->upload_file($record_id, $file);
            $publish = $client->publish_record($record_id);
            return '<pre>' .
                print_r([
                    'record' => $record,
                    'upload' => $upload,
                    'publish' => $publish
                ], true)
                . '</pre>';
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