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
    if (time() > $assignment->duedate) {
        throw new moodle_exception('Assignment submission deadline has passed');
    }
    if (empty($_FILES['submission']['name'])) {
        throw new moodle_exception('No file selected');
    }

    $filename = $_FILES['submission']['name'];
    $tmpfile  = $_FILES['submission']['tmp_name'];

    $existing = $DB->get_record(
        'local_inveniordm_submissions',
        [
            'assignmentid' => $assignmentid,
            'studentid' => $USER->id
        ]
    );

    if ($existing) {
        $submissionid = $existing->id;
    } else {
        $submissionid = $DB->insert_record(
            'local_inveniordm_submissions',
            [
                'assignmentid' => $assignmentid,
                'studentid'    => $USER->id,
                'filename'     => $filename,
                'status'       => 'submitted',
                'timecreated'  => time()
            ]
        );
    }

    if ($existing) {
        $existing->filename = $filename;
        $existing->status = 'submitted';
        $DB->update_record('local_inveniordm_submissions', $existing);
    }

    $fs = get_file_storage();

    $fileinfo = [
        'contextid' => $context->id,
        'component' => 'local_inveniordm',
        'filearea'  => 'submission',
        'itemid'    => $submissionid,
        'filepath'  => '/',
        'filename'  => $filename
    ];

    $fs->create_file_from_pathname($fileinfo, $tmpfile);

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

$backurl = new moodle_url(
        '/local/inveniordm/student/assignments.php',
        [
                'courseid' => $assignment->courseid
        ]
);

$expired = time() > $assignment->duedate;

echo '
    <div class="hero-section">
        <h1>Submit Assignment</h1>
        <p>Upload your work for review.</p>
    </div>
    
    <div class="mb-4">
        <a href="'.$backurl.'" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i>
            Back to Assignments
        </a>
    </div>
    
    <div class="submit-card">
        <h2>'.s($assignment->name).'</h2>
        <p>'.s($assignment->description).'</p>
        <p>
            <strong>Due date:</strong>
            '.userdate($assignment->duedate).'
        </p>
';

if ($expired) {
    echo '
        <div class="alert alert-danger">
            <strong>Assignment closed.</strong>
            <br>
            The submission deadline has passed.
        </div>
    ';

} else {
    echo '
        <form method="post" enctype="multipart/form-data">
            <div class="upload-area">
                <input type="file" name="submission" id="submission" required>
    
                <label for="submission" class="upload-label">
                    <div class="upload-icon"><i class="fa fa-cloud-upload-alt"></i></div>
                    <div class="upload-text">Click to select file</div>
                    <div class="upload-subtext">PDF, DOCX, ZIP...</div>
                </label>
    
                <div id="selected-file" class="selected-file"></div>
            </div>
    
            <button class="btn btn-success" type="submit">Submit</button>
    </form>
    ';
}

echo '</div>';

echo '
    <script>
        const fileInput = document.getElementById("submission");
        if (fileInput) {
            fileInput.addEventListener("change", function () {
                const file = this.files[0];
                if (file) {
                    document.getElementById("selected-file").innerHTML = "Selected: " + file.name;
                }
            });
        }
    </script>
';

echo $OUTPUT->footer();