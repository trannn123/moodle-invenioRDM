<?php

use local_inveniordm\api\invenio_client;

require_once(__DIR__ . '/../../../config.php');
global $CFG;
$courseid = required_param('courseid', PARAM_INT);
$attach = optional_param('attach', '', PARAM_TEXT);
require_login();
$context = context_course::instance($courseid);
require_capability('local/inveniordm:upload', $context);
global $DB, $PAGE, $OUTPUT, $USER;

require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/api/invenio_client.php'
);

require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/service/log_service.php'
);

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
                '/local/inveniordm/lecturer/search_resources_to_attach.php',
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

    \local_inveniordm\service\log_service::add($USER->id, 'ATTACH_RESOURCE', $attach, $courseid);

    redirect(
        new moodle_url(
            '/local/inveniordm/lecturer/search_resources_to_attach.php',
            [
                'courseid' => $courseid
            ]
        ),
        'Attached & downloaded successfully'
    );
}

$PAGE->set_url(new moodle_url('/local/inveniordm/lecturer/search_resources_to_attach.php',
    ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title('Search Repository');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/main.css'
    )
);
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/search_resources_to_attach.css'
    )
);

echo $OUTPUT->header();

$backurl = new moodle_url(
    '/local/inveniordm/lecturer/course_resources.php',
    [
        'courseid' => $courseid
    ]
);

echo '<div class="container">';

echo '
    <div class="page-hero">
        <div class="page-hero-content">
            <h1><i class="fa fa-search"></i> Search Repository</h1>
            <p>Search learning resources from InvenioRDM and attach them to this course.</p>
        </div>
        <div class="hero-actions">
            <a href="' . $backurl . '" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Back to Course Resources
            </a>
        </div>
    </div>
';

$q = optional_param('q', '', PARAM_TEXT);

echo '
    <div class="search-card mb-4">
        <form method="get" class="search-form">
            <input type="hidden" name="courseid" value="' . $courseid . '">
            <div class="search-input-group">
                <input type="text" name="q" value="' . s($q) . '" placeholder="Search resources..." class="form-control">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-search"></i> Search
                </button>
            </div>
        </form>
    </div>
';

$searchq = !empty($q) ? $q : '*';
$records = $client->get_records($searchq);
$hits = $records['hits']['hits'] ?? [];

if (empty($hits)) {
    echo '
        <div class="alert-info-custom">
            <i class="fa fa-info-circle fa-3x"></i>
            <p>No resources found</p>
            <div class="text-muted">Try a different search term.</div>
        </div>
    ';
} else {
    echo '<div class="resource-grid">';
    foreach ($hits as $r) {
        $id = $r['id'];
        $title = $r['metadata']['title'] ?? 'No title';
        $viewurl = new moodle_url(
            '/local/inveniordm/resource/view.php',
            [
                'id' => $id,
                'returnurl' => qualified_me()
            ]
        );

        $attached = $DB->record_exists('local_inveniordm_course_resources', ['courseid' => $courseid, 'recordid' => $id]);

        echo '
            <div class="resource-card">
                <div class="resource-title">' . s($title) . '</div>
                <div class="resource-info-row">
                    <span class="info-label">Record ID</span>
                    <span class="info-value">' . s($id) . '</span>
                </div>
                <div class="resource-actions">
                    <a class="btn btn-outline-primary" href="' . $viewurl . '" target="_blank"><i class="fa fa-eye"></i> View Details</a>
                    ' . ($attached ? '<span class="badge-status status-active"><i class="fa fa-check"></i> Attached</span>' :
                '<a class="btn btn-primary" href="?courseid=' . $courseid . '&attach=' . urlencode($id) . '"><i class="fa fa-link"></i> Attach Resource</a>') . '
                </div>
            </div>
        ';
    }
    echo '</div>';
}

echo '</div>';

echo $OUTPUT->footer();