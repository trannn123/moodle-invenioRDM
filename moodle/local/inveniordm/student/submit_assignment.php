<?php

require_once(__DIR__ . '/../../../config.php');

require_login();

global $DB, $PAGE, $OUTPUT, $CFG, $USER;

$assignmentid = required_param('assignmentid', PARAM_INT);

$assignment = $DB->get_record(
    'local_inveniordm_assignments',
    ['id' => $assignmentid],
    '*',
    MUST_EXIST
);

$context = context_course::instance($assignment->courseid);

if (has_capability('local/inveniordm:upload', $context)) {
    throw new moodle_exception('nopermissions', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_FILES['submission']['name'])) {
        throw new moodle_exception('No file selected');
    }

    $filename = $_FILES['submission']['name'];
    $tmpfile  = $_FILES['submission']['tmp_name'];

    $client = new \local_inveniordm\api\invenio_client();

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

    $recordpayload = [
        'files' => ['enabled' => true],
        'metadata' => [
            'title' => $assignment->name . ' - ' . fullname($USER),
            'description' => 'Submission for assignment',
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

    $record = $client->create_record($recordpayload);
    $recordid = $record['data']['id'] ?? null;

    if (!$recordid) {
        throw new moodle_exception('Create Invenio record failed');
    }

    $upload = $client->upload_file(
        $recordid,
        [
            'name' => $filename,
            'tmp_name' => $tmpfile
        ]
    );

    if (!empty($upload['error'])) {
        throw new moodle_exception('Upload file to Invenio failed');
    }

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
            'Authorization: Bearer ' . INVENIO_TOKEN
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

    $DB->insert_record('local_inveniordm_submissions', [
        'assignmentid' => $assignmentid,
        'studentid'       => $USER->id,
        'recordid'     => $recordid,
        'filename'     => $filename,
        'timecreated'  => time()
    ]);

    redirect(
        new moodle_url('/local/inveniordm/student/assignments.php', [
            'courseid' => $assignment->courseid
        ]),
        'Submitted successfully'
    );
}

$PAGE->requires->css(
    new moodle_url('/local/inveniordm/styles/submit_assignment.css')
);

$PAGE->set_url(
    new moodle_url('/local/inveniordm/student/submit_assignment.php', [
        'assignmentid' => $assignmentid
    ])
);

$PAGE->set_context($context);
$PAGE->set_title('Submit Assignment');
$PAGE->set_heading('Submit Assignment');

echo $OUTPUT->header();
?>

    <div class="hero-section">
        <h1>Submit Assignment</h1>
        <p>Upload your work for review.</p>
    </div>

    <div class="submit-card">
        <h2><?= s($assignment->name) ?></h2>
        <p><?= s($assignment->description) ?></p>
        <p>Due: <?= date('d/m/Y', $assignment->duedate) ?></p>

        <form method="post" enctype="multipart/form-data">
            <div class="upload-area">

                <input type="file" name="submission" id="submission" required>

                <label for="submission" class="upload-label">
                    <div class="upload-icon">📄</div>
                    <div class="upload-text">Click to select file</div>
                    <div class="upload-subtext">PDF, DOCX, ZIP...</div>
                </label>

                <div id="selected-file" class="selected-file"></div>

            </div>

            <button class="btn btn-success" type="submit">
                Submit
            </button>
        </form>
    </div>

    <script>
        document.getElementById("submission").addEventListener("change", function () {
            const file = this.files[0];
            if (file) {
                document.getElementById("selected-file").innerHTML =
                    "Selected: " + file.name;
            }
        });
    </script>

<?php
echo $OUTPUT->footer();