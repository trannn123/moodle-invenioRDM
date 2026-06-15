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

require_capability(
    'local/inveniordm:upload',
    $context
);

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

echo $OUTPUT->header();
$resources = $DB->get_records(
    'local_inveniordm_course_resources',
    [
        'courseid' => $courseid
    ],
    'timecreated DESC'
);
echo '<h3>Select Resource For Assignment</h3>';

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
    <div style="
        border:1px solid #ddd;
        padding:15px;
        margin-bottom:10px;
    ">
        <strong>
            '.s($resource->title).'
        </strong>

        <br>

        Record:
        '.s($resource->recordid).'

        <br><br>
    </div>
    ';
    if (!$existingassignment) {

        echo '
        <a
            href="'.$createurl.'"
            class="btn btn-success"
        >
            Create Assignment
        </a>
        ';

    } else {

        echo '
        <span class="badge bg-secondary">
            Assignment Already Exists
        </span>
        ';
    }
}
echo $OUTPUT->footer();