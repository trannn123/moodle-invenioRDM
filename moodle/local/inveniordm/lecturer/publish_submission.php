<?php

global $CFG;
require_once(__DIR__.'/../../../config.php');
require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/api/invenio_client.php'
);
require_login();

global $DB;

$submissionid = required_param(
    'submissionid',
    PARAM_INT
);

$submission = $DB->get_record(
    'local_inveniordm_submissions',
    [
        'id' => $submissionid
    ],
    '*',
    MUST_EXIST
);

$assignment = $DB->get_record(
    'local_inveniordm_assignments',
    [
        'id' => $submission->assignmentid
    ],
    '*',
    MUST_EXIST
);

$context = context_course::instance(
    $assignment->courseid
);

require_capability(
    'local/inveniordm:upload',
    $context
);

$fs = get_file_storage();

$files = $fs->get_area_files(
    $context->id,
    'local_inveniordm',
    'submission',
    $submission->id,
    'id',
    false
);
if (empty($files)) {
    throw new moodle_exception(
        'Submission file not found'
    );
}

$file = reset($files);
$tempfile = tempnam(sys_get_temp_dir(), 'inv_');

$file->copy_content_to($tempfile);

$client = new \local_inveniordm\api\invenio_client();
$student = $DB->get_record(
    'user',
    [
        'id' => $submission->studentid
    ],
    '*',
    MUST_EXIST
);$recordpayload = [
    'files' => [
        'enabled' => true
    ],
    'metadata' => [
        'title' => $assignment->name . ' - ' . fullname($student),

        'description' =>
            "Assignment submission\n" .
            "Student: " . fullname($student) . "\n" .
            "Grade: " . $submission->grade . "\n" .
            "Feedback: " . $submission->feedback,

        'publication_date' => date('Y-m-d'),

        'resource_type' => [
            'id' => 'publication-article'
        ],

        'creators' => [[
            'person_or_org' => [
                'type' => 'personal',
                'given_name' => $student->firstname,
                'family_name' => $student->lastname
            ]
        ]]
    ]
];
//$record = $client->create_record(
//    $recordpayload
//);

$result = $client->create_record($recordpayload);

$recordid = $result['data']['id'] ?? null;

$uploadresult = $client->upload_file(
    $recordid,
    [
        'name' => $file->get_filename(),
        'tmp_name' => $tempfile
    ]
);

$publishurl =
    'http://ctu-it-rdm-web-api-1:5000/api/records/' .
    $recordid .
    '/draft/actions/publish';

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $publishurl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $client->get_token()
    ],
    CURLOPT_POSTFIELDS => '{}'
]);

$response = curl_exec($ch);

$httpcode = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);

$error = curl_error($ch);

curl_close($ch);


if (!empty($submission->published_to_invenio)) {

    throw new moodle_exception(
        'Already published to Invenio'
    );
}

$DB->set_field(
    'local_inveniordm_submissions',
    'published_to_invenio',
    1,
    ['id' => $submissionid]
);

$DB->set_field(
    'local_inveniordm_submissions',
    'recordid',
    $recordid,
    ['id' => $submissionid]
);

$check = $DB->get_record(
    'local_inveniordm_submissions',
    ['id' => $submissionid]
);

echo '<pre>';
print_r($check);
die();

redirect(
    new moodle_url(
        '/local/inveniordm/lecturer/review_submission.php',
        [
            'submissionid' => $submissionid
        ]
    ),
    'Published to Invenio successfully'
);