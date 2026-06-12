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
    $assignment->recordid = $recordid;

    $assignment->name =
        required_param('name', PARAM_TEXT);

    $assignment->description =
        optional_param(
            'description',
            '',
            PARAM_TEXT
        );

    $assignment->duedate =
        strtotime(
            required_param(
                'duedate',
                PARAM_TEXT
            )
        );

    $assignment->createdby = $USER->id;
    $assignment->timecreated = time();

    $DB->insert_record(
        'local_inveniordm_assignments',
        $assignment
    );

    redirect(
        new moodle_url(
            '/local/inveniordm/lecturer/course_resources.php',
            [
                'courseid' => $courseid
            ]
        ),
        'Assignment created'
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