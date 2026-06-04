<?php

use local_inveniordm\api\invenio_client;
use local_inveniordm\service\file_service;

require_once(__DIR__ . '/../../../config.php');
global $CFG;

$courseid = required_param('courseid', PARAM_INT);
$attach   = optional_param('attach', '', PARAM_TEXT);

require_login();
$context = context_course::instance($courseid);
require_capability('local/inveniordm:upload', $context);

global $DB, $PAGE, $OUTPUT;

require_once($CFG->dirroot . '/local/inveniordm/classes/api/invenio_client.php');

$client = new invenio_client();

/**
 * =========================
 * ATTACH + DOWNLOAD LEVEL 3
 * =========================
 */
if (!empty($attach)) {

    $record = $client->get_record($attach);

    if (empty($record)) {
        throw new moodle_exception('Invalid record');
    }

    $title = $record['metadata']['title'] ?? 'Unknown';

    $files = $record['files']['entries'] ?? [];

    if (empty($files)) {
        throw new moodle_exception('No file in record');
    }

    $file = array_values($files)[0];

    $filename = $file['key'];

    $fileurl = str_replace(
        'https://127.0.0.1:5001',
        'https://ctu-it-rdm-frontend-1',
        $file['links']['content']
    );

    $exists = $DB->record_exists(
        'local_inveniordm_course_resources',
        [
            'courseid' => $courseid,
            'recordid' => $attach
        ]
    );

    if ($exists) {
        redirect(
            new moodle_url(
                '/local/inveniordm/lecturer/search_resources.php',
                [
                    'courseid' => $courseid
                ]
            ),
            'Resource already attached'
        );
    }

    $DB->insert_record(
        'local_inveniordm_course_resources',
        [
            'courseid' => $courseid,
            'recordid' => $attach,
            'title' => $title,
            'timecreated' => time()
        ]
    );

    redirect(
        new moodle_url(
            '/local/inveniordm/lecturer/search_resources.php',
            [
                'courseid' => $courseid
            ]
        ),
        'Attached & downloaded successfully'
    );
}

/**
 * =========================
 * UI
 * =========================
 */

$PAGE->set_url(new moodle_url('/local/inveniordm/lecturer/search_resources.php',
    ['courseid' => $courseid]));

$PAGE->set_context($context);
$PAGE->set_title('Manage Course Resources');
$PAGE->set_heading('Manage Course Resources');

echo $OUTPUT->header();

echo "<h2>Course ID: $courseid</h2>";

$q = optional_param('q', '', PARAM_TEXT);

echo '<form method="get">';
echo '<input type="hidden" name="courseid" value="'.$courseid.'">';
echo '<input type="text" name="q" value="'.s($q).'" placeholder="Search...">';
echo '<input type="submit" value="Search">';
echo '</form>';

if (!empty($q)) {

    $records = $client->get_records($q);
    $hits = $records['hits']['hits'] ?? [];

    echo '<table border="1" cellpadding="8">';
    echo '<tr><th>Record ID</th><th>Title</th><th>Action</th></tr>';

    foreach ($hits as $r) {

        $id = $r['id'];
        $title = $r['metadata']['title'] ?? 'No title';

        echo '<tr>';
        echo '<td>'.s($id).'</td>';
        echo '<td>'.s($title).'</td>';

        echo '<td>
            <a href="?courseid='.$courseid.'&attach='.urlencode($id).'">
                Download & Attach
            </a>
        </td>';

        echo '</tr>';
    }

    echo '</table>';
}

echo $OUTPUT->footer();