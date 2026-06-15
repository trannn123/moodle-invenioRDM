<?php

require_once(__DIR__.'/../../../config.php');

require_login();

global $DB, $CFG;

$submissionid =
    required_param(
        'submissionid',
        PARAM_INT
    );

$submission =
    $DB->get_record(
        'local_inveniordm_submissions',
        [
            'id' => $submissionid
        ],
        '*',
        MUST_EXIST
    );
$context = context_course::instance(
    $DB->get_field(
        'local_inveniordm_assignments',
        'courseid',
        ['id' => $submission->assignmentid]
    )
);

$fs = get_file_storage();

$file = $fs->get_file(
    $context->id,
    'local_inveniordm',
    'submission',
    $submission->assignmentid,
    '/',
    $submission->filename
);

if (!$file) {
    throw new moodle_exception(
        'File not found'
    );
}

send_stored_file($file,0,0,true);

exit;