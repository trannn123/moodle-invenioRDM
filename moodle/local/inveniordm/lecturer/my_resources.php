<?php

require_once(__DIR__ . '/../../../config.php');
use local_inveniordm\api\invenio_client;
require_login();
global $PAGE, $OUTPUT, $CFG;
$context = context_system::instance();
$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/my_resources.php'
    )
);
$PAGE->set_context($context);
$PAGE->set_title('My Resources');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/my_resources.css'
    )
);
$client = new invenio_client();
$result = $client->get_records();
$backurl = new moodle_url('/local/inveniordm/index.php');

echo $OUTPUT->header();

echo '
    <div class="courses-hero mb-4">
        <div class="courses-hero-content">
            <h1>
                <i class="fa fa-database"></i> 
                My Repository Resources
            </h1>
            <p>Browse and manage resources available in InvenioRDM repository.</p>
        </div>
        <div class="courses-hero-actions">
            <a href="'.$backurl.'" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> 
                Back
            </a>
        </div>
    </div>
';

$records = $result['hits']['hits'] ?? [];
$totalresources = count($records);

echo '
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="stats-card">
                <h2>'.$totalresources.'</h2>
                <p>Resources</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stats-card">
                <h2>
                    <i class="fa fa-cloud"></i> 
                    Online
                </h2>
                <p>Repository Status</p>
            </div>
        </div>
    </div>
';

if (empty($records)) {
    echo '
        <div class="no-resources">
            <i class="fa fa-inbox fa-2x"></i>
            <p>No resources found</p>
            <span class="text-muted">There are no resources in the repository yet.</span>
        </div>
    ';
} else {
    echo '<div class="resource-grid">';

    foreach ($records as $record) {
        $id = $record['id'] ?? '';
        $title = $record['metadata']['title'] ?? 'No title';
        $date = $record['metadata']['publication_date'] ?? '';
        $status = $record['status'] ?? '';
        $filecount = $record['files']['count'] ?? 0;
        $viewurl = new moodle_url(
            '/local/inveniordm/resource/view.php',
            [
                'id' => $id,
                'returnurl' => qualified_me()
            ]
        );

        echo '
            <div class="resource-card">
                <div class="resource-title">'.s($title).'</div>
                <div class="resource-info-row">
                    <strong>ID</strong>
                    <span>'.s($id).'</span>
                </div>
                <div class="resource-info-row">
                    <strong>Date</strong>
                    <span>'.s($date).'</span>
                </div>
                <div class="resource-info-row">
                    <strong>Status</strong>
                    <span>'.s($status).'</span>
                </div>
                <div class="resource-info-row">
                    <strong>Files</strong>
                    <span>'.$filecount.'</span>
                </div>
                <div class="resource-actions">
                    <a class="btn btn-primary" href="'.$viewurl.'">
                        <i class="fa fa-eye"></i> 
                        View Details
                    </a>
                </div>
            </div>
        ';
    }
    echo '</div>';
}

echo $OUTPUT->footer();