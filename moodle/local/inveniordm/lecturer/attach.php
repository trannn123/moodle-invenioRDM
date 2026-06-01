<?php

require_once(__DIR__ . '/../../../config.php');

require_login();

global $DB;

$courseid = required_param(
    'courseid',
    PARAM_INT
);

$recordid = required_param(
    'recordid',
    PARAM_TEXT
);

$title = required_param(
    'title',
    PARAM_TEXT
);

$data = new stdClass();

$data->courseid = $courseid;
$data->recordid = $recordid;
$data->title = $title;
$data->timecreated = time();

$DB->insert_record(
    'local_inveniordm_course_resources',
    $data
);

redirect(
    new moodle_url(
        '/local/inveniordm/lecturer/manage_course.php',
        ['id' => $courseid]
    ),
    'Resource attached successfully'
);