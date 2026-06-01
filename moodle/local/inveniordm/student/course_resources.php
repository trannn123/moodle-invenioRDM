<?php

require_once(__DIR__ . '/../../../config.php');

require_login();

global $DB, $PAGE, $OUTPUT;

$courseid = required_param('courseid', PARAM_INT);

$context = context_course::instance($courseid);

$PAGE->set_url(new moodle_url(
    '/local/inveniordm/student/course_resources.php',
    ['courseid' => $courseid]
));

$PAGE->set_context($context);
$PAGE->set_title('Course Resources');
$PAGE->set_heading('Course Resources');

echo $OUTPUT->header();

echo '<h2>Course Resources (Course ID: ' . $courseid . ')</h2>';

$resources = $DB->get_records(
    'local_inveniordm_course_resources',
    ['courseid' => $courseid],
    'timecreated DESC'
);

if (!$resources) {
    echo $OUTPUT->notification('No resources found', 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = ['Title', 'Record ID', 'Action'];
$table->data = [];

$client = new \local_inveniordm\api\invenio_client();

foreach ($resources as $res) {

    try {
        $record = $client->get_record($res->recordid);
        $link = $record['links']['self_html'] ?? '';
    } catch (Exception $e) {
        $link = '';
    }

    if (!$link) {
        $link = 'https://127.0.0.1/records/' . $res->recordid;
    }

    $table->data[] = [
        s($res->title),
        s($res->recordid),
        html_writer::link($link, 'View', ['target' => '_blank'])
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();