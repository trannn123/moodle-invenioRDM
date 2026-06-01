<?php

require_once(__DIR__ . '/../../../config.php');
use local_inveniordm\api\invenio_client;

require_login();

global $PAGE, $OUTPUT, $CFG;

$context = context_system::instance();

$PAGE->set_url(
    new moodle_url('/local/inveniordm/lecturer/myresources.php')
);

$PAGE->set_context($context);

$PAGE->set_title('My Resources');
$PAGE->set_heading('My Resources');

$client = new invenio_client();
$result = $client->get_records();

echo $OUTPUT->header();

echo '<h2>My Resources</h2>';

echo '<h2>My Resources</h2>';

$records = $result['hits']['hits'] ?? [];

if (empty($records)) {
    echo '<p>No resources found.</p>';
} else {

    echo '<table border="1" cellpadding="8" cellspacing="0">';

    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Title</th>';
    echo '<th>Publication Date</th>';
    echo '<th>Status</th>';
    echo '<th>Files</th>';
    echo '<th>Actions</th>';
    echo '</tr>';

    foreach ($records as $record) {

        $id = $record['id'] ?? '';

        $title =
            $record['metadata']['title']
            ?? 'No title';

        $date =
            $record['metadata']['publication_date']
            ?? '';

        $status =
            $record['status']
            ?? '';

        $filecount =
            $record['files']['count']
            ?? 0;

        echo '<tr>';

        echo '<td>' . s($id) . '</td>';

        echo '<td>' . s($title) . '</td>';

        echo '<td>' . s($date) . '</td>';

        echo '<td>' . s($status) . '</td>';

        echo '<td>' . $filecount . '</td>';

        echo '<td>';

        echo '<a href="' .
            $CFG->wwwroot .
            '/local/inveniordm/student/view.php?id=' .
            urlencode($id) .
            '">View</a>';

        echo '</td>';

        echo '</tr>';
    }

    echo '</table';
}

echo $OUTPUT->footer();