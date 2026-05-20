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

        $title = $metadata['title'] ?? 'No title';

        $author = 'Unknown';

        if (isset($metadata['creators'][0]['person_or_org']['name'])) {
            $author = $metadata['creators'][0]['person_or_org']['name'];
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

        $html = '';

        $html .= $OUTPUT->heading($title, 2);

        $html .= \html_writer::start_div(
            'card',
            ['style' => 'margin-bottom:20px;']
        );

        $html .= \html_writer::start_div('card-body');

        $html .= \html_writer::tag(
            'h5',
            'Details',
            ['class' => 'card-title']
        );

        $details = [
            'Author' => $author,
            'Publication Date' => $publicationdate,
            'Resource Type' => $resourcetype,
            'Publisher' => $publisher,
            'ID' => $id
        ];

        $html .= \html_writer::start_tag(
            'dl',
            ['class' => 'row']
        );

        foreach ($details as $label => $value) {

            $html .= \html_writer::tag(
                'dt',
                s($label),
                ['class' => 'col-sm-3']
            );

            $html .= \html_writer::tag(
                'dd',
                \html_writer::tag('strong', s($value)),
                ['class' => 'col-sm-9']
            );
        }

        $html .= \html_writer::end_tag('dl');

        $html .= \html_writer::end_div();

        $html .= \html_writer::end_div();

        $backurl = new \moodle_url(
            '/local/inveniordm/student/search.php'
        );

        $html .= \html_writer::link(
            $backurl,
            '← Back to Search',
            ['class' => 'btn btn-primary']
        );

        return $html;
    }
}