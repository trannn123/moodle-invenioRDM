<?php

require_once(__DIR__.'/../../../config.php');

require_login();

global $DB, $PAGE, $OUTPUT, $CFG, $USER;

$assignmentid =
    required_param(
        'assignmentid',
        PARAM_INT
    );

$assignment =
    $DB->get_record(
        'local_inveniordm_assignments',
        [
            'id' => $assignmentid
        ],
        '*',
        MUST_EXIST
    );

$context =
    context_course::instance(
        $assignment->courseid
    );

if (
    has_capability(
        'local/inveniordm:upload',
        $context
    )
) {
    throw new moodle_exception(
        'nopermissions',
        'error'
    );
}

$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/submit_assignment.css'
    )
);
$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/student/submit_assignment.php',
        [
            'assignmentid' => $assignmentid
        ]
    )
);
$PAGE->set_context($context);
$PAGE->set_title(
    'Submit Assignment'
);
$PAGE->set_heading(
    'Submit Assignment'
);
echo $OUTPUT->header();
echo '
<div class="hero-section">
    <h1>
        Submit Assignment
    </h1>

    <p>
        Upload your work for review.
    </p>
</div>
';
echo '
<div class="submit-card">
    <h2>
        '.s($assignment->name).'
    </h2>
    <p>
        '.s($assignment->description).'
    </p>
    <p>
        Due:
        '.date(
        'd/m/Y',
        $assignment->duedate
    ).'
    </p>
    <form
        method="post"
        enctype="multipart/form-data"
    >
        <div class="upload-area">

            <input
                type="file"
                name="submission"
                id="submission"
                required
            >
        
            <label
                for="submission"
                class="upload-label"
            >
        
                <div class="upload-icon">
                    📄
                </div>
        
                <div class="upload-text">
                    Click to select file
                </div>
        
                <div class="upload-subtext">
                    PDF, DOCX, ZIP...
                </div>
        
            </label>
        
            <div
                id="selected-file"
                class="selected-file"
            ></div>
        
        </div>
        <button
            class="btn btn-success"
            type="submit"
        >
            Submit
        </button>
    </form>
</div>
';
echo '
<script>

document
.getElementById("submission")
.addEventListener(
    "change",
    function() {

        const file =
            this.files[0];

        if (file) {

            document
            .getElementById(
                "selected-file"
            )
            .innerHTML =
                "Selected: " +
                file.name;
        }
    }
);

</script>
';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_FILES['submission']['name'])) {
        throw new moodle_exception('No file selected');
    }

    $filename = $_FILES['submission']['name'];
    $tmpfile  = $_FILES['submission']['tmp_name'];

    $client = new \local_inveniordm\api\invenio_client();

    // 1. tạo record
    $recordpayload = [
        'files' => ['enabled' => true],
        'metadata' => [
            'title' => $assignment->name . ' - ' . fullname($USER),
            'publication_date' => date('Y-m-d'),
            'resource_type' => ['id' => 'publication-article'],
            'creators' => [[
                'person_or_org' => [
                    'type' => 'personal',
                    'name' => fullname($USER)
                ]
            ]]
        ]
    ];

    $record = $client->create_record($recordpayload);
    $recordid = $record['data']['id'] ?? null;

    if (!$recordid) {
        throw new moodle_exception('Create record failed');
    }

    // 2. upload file
    $client->upload_file(
        $recordid,
        [
            'name' => $filename,
            'tmp_name' => $tmpfile
        ]
    );

    // 3. publish
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
            'Authorization: Bearer ' . "scPx1LLmZkoCjM4dkH3tDa3n1KzfZfvBxhwdHATFa8ZN2SO0Sm9Ds8D8VcjV"
        ],
        CURLOPT_POSTFIELDS => '{}'
    ]);
    curl_exec($ch);
    curl_close($ch);

    // 4. CHỈ lưu DB SAU KHI SUCCESS
    $DB->insert_record('local_inveniordm_submissions', [
        'assignmentid' => $assignmentid,
        'userid' => $USER->id,
        'recordid' => $recordid,
        'filename' => $filename,
        'timecreated' => time()
    ]);

    redirect(
        new moodle_url('/local/inveniordm/student/assignments.php', [
            'courseid' => $assignment->courseid
        ]),
        'Submitted to InvenioRDM successfully'
    );
}
echo $OUTPUT->footer();

