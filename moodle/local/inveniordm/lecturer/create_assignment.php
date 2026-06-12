<?php

require_once(__DIR__.'/../../../config.php');
require_login();
global $DB, $USER, $PAGE, $OUTPUT;

$courseid = required_param('courseid', PARAM_INT);
$recordid = required_param('recordid', PARAM_TEXT);
$context = context_course::instance($courseid);

require_capability(
    'local/inveniordm:upload',
    $context
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignment = new stdClass();
    $assignment->courseid = $courseid;
    $assignment->name = required_param('name', PARAM_TEXT);
    $assignment->description = optional_param('description', '', PARAM_TEXT);
    $assignment->duedate = strtotime(required_param('duedate', PARAM_TEXT));

    $assignment->createdby = $USER->id;
    $assignment->timecreated = time();

    $fullname = fullname($USER);
    $nameParts = explode(' ', trim($fullname));
    $family_name = array_pop($nameParts);
    $given_name = implode(' ', $nameParts);

    if (empty($family_name)) {
        $family_name = 'User';
    }
    if (empty($given_name)) {
        $given_name = 'Unknown';
    }

    $payload = [
        'files' => ['enabled' => false],
        'metadata' => [
            'title' => $assignment->name,
            'description' => $assignment->description,
            'publication_date' => date('Y-m-d'),
            'resource_type' => ['id' => 'publication-article'],
            'creators' => [[
                'person_or_org' => [
                    'type' => 'personal',
                    'given_name' => $given_name,
                    'family_name' => $family_name
                ]
            ]]
        ]
    ];
    $client = new \local_inveniordm\api\invenio_client();

    $result = $client->create_record($payload);
    $recordid = $result['data']['id'] ?? null;

    if (!$recordid) {
        throw new moodle_exception('Failed to create Invenio record');
    }
    $publishurl =
        'http://ctu-it-rdm-web-api-1:5000/api/records/' .
        $recordid .
        '/draft/actions/publish';

    if ($publishurl) {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $publishurl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . "scPx1LLmZkoCjM4dkH3tDa3n1KzfZfvBxhwdHATFa8ZN2SO0Sm9Ds8D8VcjV"
            ],
            CURLOPT_POSTFIELDS => '{}'
        ]);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new moodle_exception('CURL error: ' . $error);
        }

        if ($httpcode >= 300) {
            throw new moodle_exception('Publish failed: ' . $response);
        }
    }
    $assignment->recordid = $recordid;
    $assignmentid = $DB->insert_record(
        'local_inveniordm_assignments',
        $assignment
    );
    redirect(
        new moodle_url(
            '/local/inveniordm/lecturer/course_resources.php',
            ['courseid' => $courseid]
        ),
        'Assignment created successfully'
    );
}

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/create_assignment.php',
        [
            'courseid' => $courseid,
            'recordid' => $recordid
        ]
    )
);
$PAGE->set_context($context);
$PAGE->set_title('Create Assignment');
$PAGE->set_heading('Create Assignment');
echo $OUTPUT->header();
echo '
<h2>Create Assignment</h2>

<form method="post">

    <div class="mb-3">
        <label>
            Assignment Name
        </label>

        <input
            type="text"
            name="name"
            class="form-control"
            required
        >
    </div>

    <div class="mb-3">
        <label>
            Description
        </label>

        <textarea
            name="description"
            class="form-control"
        ></textarea>
    </div>

    <div class="mb-3">
        <label>
            Due Date
        </label>

        <input
            type="date"
            name="duedate"
            class="form-control"
            required
        >
    </div>

    <button
        type="submit"
        class="btn btn-success"
    >
        Save Assignment
    </button>

</form>
';
echo $OUTPUT->footer();