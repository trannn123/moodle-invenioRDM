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
                '/local/inveniordm/lecturer/search_resources.css.php',
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
            '/local/inveniordm/lecturer/search_resources.css.php',
            [
                'courseid' => $courseid
            ]
        ),
        'Attached & downloaded successfully'
    );
}

$PAGE->set_url(new moodle_url('/local/inveniordm/lecturer/search_resources.css.php',
    ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title('Manage Course Resources');
$PAGE->set_heading('Manage Course Resources');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/search_resources.css'
    )
);
echo $OUTPUT->header();

echo '
<div class="hero-section">
    <h1>Search Repository</h1>
    <p>
        Search learning resources from
        InvenioRDM and attach them to this course.
    </p>
</div>
';

$q = optional_param('q', '', PARAM_TEXT);

echo '
<form method="get" class="search-box">
    <input
        type="hidden"
        name="courseid"
        value="'.$courseid.'"
    >
    <input
        type="text"
        name="q"
        value="'.s($q).'"
        placeholder="Search resources..."
        class="form-control"
    >
    <button
        type="submit"
        class="btn btn-primary"
    >
        Search
    </button>
</form>
';

if (!empty($q)) {
    $records = $client->get_records($q);
    $hits = $records['hits']['hits'] ?? [];
    echo '<div class="resource-grid">';
    foreach ($hits as $r) {
        $id = $r['id'];
        $title =
            $r['metadata']['title']
            ?? 'No title';
        $viewurl = new moodle_url(
            '/local/inveniordm/student/view.php',
            [
                'id' => $id
            ]
        );

        echo '
        <div class="resource-card">
            <div class="resource-title">
                '.s($title).'
            </div>    
            <div class="resource-info-row">    
                <strong>Record ID</strong>   
                <span>'.s($id).'</span>    
            </div>    
            <div class="resource-actions">    
                <a
                    class="btn btn-outline-primary"
                    href="'.$viewurl.'"
                    target="_blank"
                >
                    View Details
                </a>           
                <a
                    class="btn btn-success"
                    href="?courseid='.$courseid.
                        '&attach='.urlencode($id).'"
                >
                    Attach Resource
                </a>            
            </div>    
        </div>
        ';
    }
    echo '</div>';
}
echo $OUTPUT->footer();