<?php

namespace local_inveniordm\controller;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../api/invenio_client.php');

use local_inveniordm\api\invenio_client;

class student_controller {

    public function search() {

        global $OUTPUT;

        $client = new invenio_client();

        $query = optional_param('q', '', PARAM_TEXT);

        if (trim($query) === '') {

            $records = [];

        } else {

            $response = $client->get_records($query);

            $records = [];

            $hits = $response['hits']['hits'] ?? [];

            foreach ($hits as $record) {

                $records[] = [
                    'id' => $record['id'] ?? '',
                    'title' => $record['metadata']['title'] ?? 'No title',
                    'author' => $record['metadata']['creators'][0]['person_or_org']['name'] ?? 'Unknown'
                ];
            }
        }

        $context = [
            'query' => $query,
            'records' => $records
        ];

        return $OUTPUT->render_from_template(
            'local_inveniordm/student/search',
            $context
        );
    }

    public function view($id) {

        global $OUTPUT;

        $client = new invenio_client();

        $record = $client->get_record($id);

        if (!$record || !isset($record['metadata'])) {

            return $OUTPUT->notification(
                'Record not found or API error',
                'error'
            );
        }

        $metadata = $record['metadata'] ?? [];
        $title =
            $metadata['title']
            ?? 'No title';

        $author = 'Unknown';

        if (
            !empty($metadata['creators']) &&
            isset(
                $metadata['creators'][0]['person_or_org']['name']
            )
        ) {

            $author =
                $metadata['creators'][0]['person_or_org']['name'];
        }

        $publicationdate =
            $metadata['publication_date']
            ?? 'Not specified';

        $resourcetype = 'Unknown';

        if (isset($metadata['resource_type']['title']['en'])) {

            $resourcetype =
                $metadata['resource_type']['title']['en'];

        } else if (isset($metadata['resource_type']['title'])) {

            $resourcetype =
                $metadata['resource_type']['title'];
        }

        $publisher =
            $metadata['publisher']
            ?? 'Not specified';

        $description =
            $metadata['description']
            ?? 'No description';

        $keywords = [];

        if (!empty($metadata['subjects'])) {

            foreach ($metadata['subjects'] as $subject) {

                if (isset($subject['subject'])) {

                    $keywords[] = [
                        'name' => $subject['subject']
                    ];
                }
            }
        }

        $context = [

            'title' => $title,

            'author' => $author,

            'publicationdate' => $publicationdate,

            'resourcetype' => $resourcetype,

            'publisher' => $publisher,

            'description' => strip_tags($description),

            'keywords' => $keywords,

            'backurl' => (
            new \moodle_url(
                '/local/inveniordm/student/search.php'
            )
            )->out()
        ];

        return $OUTPUT->render_from_template(
            'local_inveniordm/student/view',
            $context
        );
    }
}