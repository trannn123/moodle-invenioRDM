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
$file =
    $CFG->dataroot .
    '/temp/inveniordm_submissions/' .
    $submission->filename;
if (!file_exists($file)) {

    throw new moodle_exception(
        'File not found'
    );
}
header(
    'Content-Type: application/octet-stream'
);

header(
    'Content-Disposition: attachment; filename="' .
    basename($submission->filename) .
    '"'
);

readfile($file);

exit;