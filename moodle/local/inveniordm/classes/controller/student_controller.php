<?php
namespace local_inveniordm\controller;

use local_inveniordm\api\invenio_client;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../api/invenio_client.php');

class student_controller {

    public function search() {
        global $OUTPUT;

        $client = new \local_inveniordm\api\invenio_client();
        $query = optional_param('q', '', PARAM_TEXT);

        if (trim($query) === '') {
            $records = [];
        } else {
            $response = $client->get_records($query);
            $records = [];

            $hits = $response['hits']['hits'] ?? [];

            foreach ($hits as $record) {
                $records[] = [
                    'id' => $record['id'],
                    'title' => $record['metadata']['title'] ?? 'No title',
                    'author' => $record['metadata']['creators'][0]['person_or_org']['name'] ?? 'Unknown'
                ];
            }
        }

        $context = [
            'query' => $query,
            'records' => $records
        ];

        echo $OUTPUT->header();
        echo $OUTPUT->render_from_template('local_inveniordm/student/search', $context);
        echo $OUTPUT->footer();
    }

    public function view($id) {
        global $OUTPUT;

        $client = new \local_inveniordm\api\invenio_client();
        $record = $client->get_record($id);

        echo $OUTPUT->header();

        // Lấy metadata
        $metadata = $record['metadata'] ?? [];

        $title = $metadata['title'] ?? 'No title';

        // Lấy author
        $author = 'Unknown';
        if (isset($metadata['creators'][0]['person_or_org']['name'])) {
            $author = $metadata['creators'][0]['person_or_org']['name'];
        }

        $publication_date = $metadata['publication_date'] ?? 'Not specified';

        // Lấy resource type
        $resource_type = 'Unknown';
        if (isset($metadata['resource_type']['title']['en'])) {
            $resource_type = $metadata['resource_type']['title']['en'];
        } elseif (isset($metadata['resource_type']['title'])) {
            $resource_type = $metadata['resource_type']['title'];
        }

        // Lấy publisher
        $publisher = $metadata['publisher'] ?? 'Not specified';

        // Hiển thị thông tin
        echo $OUTPUT->heading($title, 2);

        echo html_writer::start_div('card', ['style' => 'margin-bottom: 20px;']);
        echo html_writer::start_div('card-body');

        echo html_writer::tag('h5', 'Details', ['class' => 'card-title']);

        $details = [
            'Author' => $author,
            'Publication Date' => $publication_date,
            'Resource Type' => $resource_type,
            'Publisher' => $publisher,
            'ID' => $id
        ];

        echo html_writer::start_tag('dl', ['class' => 'row']);
        foreach ($details as $label => $value) {
            echo html_writer::start_tag('dt', ['class' => 'col-sm-3']);
            echo $label;
            echo html_writer::end_tag('dt');

            echo html_writer::start_tag('dd', ['class' => 'col-sm-9']);
            echo html_writer::tag('strong', $value);
            echo html_writer::end_tag('dd');
        }
        echo html_writer::end_tag('dl');

        echo html_writer::end_div(); // card-body
        echo html_writer::end_div(); // card

        // Nút back
        $backurl = new \moodle_url('/local/inveniordm/student/search.php');
        echo html_writer::link($backurl, '← Back to Search', ['class' => 'btn btn-primary']);

        echo $OUTPUT->footer();
    }
}