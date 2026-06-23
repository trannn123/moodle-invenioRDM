<?php

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT, $CFG, $USER;

require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/service/log_service.php'
);

$assignmentid = required_param('assignmentid', PARAM_INT);

$assignment = $DB->get_record(
    'local_inveniordm_assignments',
    ['id' => $assignmentid],
    '*',
    MUST_EXIST
);

$context = context_course::instance($assignment->courseid);

$existing = $DB->get_record(
        'local_inveniordm_submissions',
        [
                'assignmentid' => $assignmentid,
                'studentid' => $USER->id
        ]
);
$buttontext = $existing ? 'Resubmit Assignment' : 'Submit Assignment';

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

    \local_inveniordm\service\log_service::add($USER->id, 'SUBMIT_ASSIGNMENT', null, $assignment->courseid);

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

$backurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $CFG->wwwroot;

$expired = time() > $assignment->duedate;

?>
    <div class="hero-section">
        <h1>Submit Assignment</h1>
        <p>Upload your work for review.</p>
    </div>
    <div class="mb-4">
        <a href="<?php echo $backurl; ?>" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i>
            Back to Assignments
        </a>
    </div>

    <div class="submit-card">
        <h2><?php echo s($assignment->name); ?></h2>
        <div class="assignment-instructions">
            <?php echo format_text($assignment->instructions, FORMAT_HTML); ?>
        </div>
        <p>
            <strong>Due date:</strong>
            <?php echo userdate($assignment->duedate); ?>
        </p>

        <?php if ($existing): ?>
            <div class="alert alert-success">
                <strong>Submission received.</strong>
                <br>
                Current file:
                <?php echo s($existing->filename); ?>
                <br>
                Submitted at:
                <?php echo userdate($existing->timecreated); ?>
            </div>

        <?php endif; ?>

        <?php
        $resources = $DB->get_records(
                'local_inveniordm_assignment_resources',
                [
                        'assignmentid' => $assignmentid
                ]
        );
        ?>

        <?php if (!empty($resources)): ?>
            <div class="attached-resources">
                <h4>Attached Resources</h4>

                <?php foreach ($resources as $resource): ?>

                    <?php
                    $resourceurl = new moodle_url(
                            '/local/inveniordm/resource/view.php',
                            [
                                    'id' => $resource->recordid,
                                    'returnurl' =>
                                            '/local/inveniordm/student/submit_assignment.php?assignmentid=' .
                                            $assignmentid
                            ]
                    );
                    ?>

                    <div class="resource-card">
                        <div>
                            <strong>
                                <?php echo s($resource->title); ?>
                            </strong>
                            <br>
                            <small>
                                Resource ID:
                                <?php echo s($resource->recordid); ?>
                            </small>
                        </div>

                        <a
                                href="<?php echo $resourceurl; ?>"
                                class="btn btn-sm btn-outline-primary">
                            View Resource
                        </a>
                    </div>

                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($expired): ?>
            <div class="alert alert-danger">
                <strong>Assignment closed.</strong>
                <br>
                The submission deadline has passed.
            </div>
        <?php else: ?>
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
                <button class="btn btn-outline-primary" type="submit">
                    <?php echo $buttontext; ?>
                </button>
            </form>
        <?php endif; ?>

    </div>

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

<?php
echo $OUTPUT->footer();