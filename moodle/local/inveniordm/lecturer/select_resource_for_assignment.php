<?php

require_once(__DIR__.'/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT;

$courseid = required_param(
    'courseid',
    PARAM_INT
);

$course = $DB->get_record(
    'course',
    ['id' => $courseid],
    '*',
    MUST_EXIST
);

$context = context_course::instance($courseid);
require_capability('local/inveniordm:upload', $context);

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/select_resource_for_assignment.php',
        [
            'courseid' => $courseid
        ]
    )
);

$PAGE->set_context($context);
$PAGE->set_title('Select Resource');
$PAGE->set_heading('Select Resource');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/select_resource.css'
    )
);

echo $OUTPUT->header();
$backurl = new moodle_url(
    '/local/inveniordm/lecturer/assignments.php',
    [
        'courseid' => $courseid
    ]
);

echo '
    <div class="mb-4">
        <a href="'.$backurl.'" class="btn btn-outline-dark">
            <i class="fa fa-arrow-left"></i>
            Back to Assignments
        </a>
    </div>
';

$resources = $DB->get_records(
    'local_inveniordm_course_resources',
    [
        'courseid' => $courseid
    ],
    'timecreated DESC'
);

echo '
    <div class="hero-section">
        <h1>Select Learning Resource</h1>
        <p>Choose a learning resource attached to this course and create an assignment from it.
        </p>
    </div>
';

foreach ($resources as $resource) {
    $existingassignment = $DB->record_exists(
        'local_inveniordm_assignments',
        [
            'courseid' => $courseid,
            'resource_recordid' => $resource->recordid
        ]
    );

    $createurl = new moodle_url(
        '/local/inveniordm/lecturer/create_assignment.php',
        [
            'courseid' => $courseid,
            'recordid' => $resource->recordid
        ]
    );

    echo '
        <div class="resource-card">
            <div class="resource-title">
                <i class="fa fa-file-text-o"></i>
                '.s($resource->title).'
            </div>
    
            <div class="resource-meta">
                <div>
                    <span class="meta-label">Record ID</span>
                    '.s($resource->recordid).'
                </div>
            </div>
    ';

    if (!$existingassignment) {
        echo '
            <div class="status-badge">
                <span class="badge bg-success">Ready for Assignment</span>
            </div>
    
            <div class="resource-actions">
                <a href="'.$createurl.'" class="btn btn-outline-primary">Create Assignment</a>
            </div>
        ';
    } else {
        echo '
        <div class="status-badge">
            <span class="badge bg-secondary">Assignment Already Exists</span>
        </div>
    ';
    }
    echo '</div>';
}

echo $OUTPUT->footer();