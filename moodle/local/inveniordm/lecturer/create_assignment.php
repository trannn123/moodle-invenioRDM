<?php

require_once(__DIR__.'/../../../config.php');
require_login();
global $DB, $USER, $PAGE, $OUTPUT;
$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);
require_capability('local/inveniordm:upload', $context);
$PAGE->requires->css(
    new moodle_url('/local/inveniordm/styles/create_assignment.css')
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignment = new stdClass();
    $assignment->courseid = $courseid;
    $assignment->name = required_param('name', PARAM_TEXT);
    $assignment->instructions = optional_param('instructions', '', PARAM_TEXT);
    $assignment->duedate = strtotime(required_param('duedate', PARAM_TEXT));
    $assignment->createdby = $USER->id;
    $assignment->timecreated = time();
    $selectedresources = $_POST['resources'] ?? [];;
    $assignmentid = $DB->insert_record('local_inveniordm_assignments', $assignment);
    foreach ($selectedresources as $recordid => $title) {
        $DB->insert_record(
            'local_inveniordm_assignment_resources',
            (object)[
                'assignmentid' => $assignmentid,
                'recordid' => $recordid,
                'title' => $title
            ]
        );
    }
    redirect(
        new moodle_url(
            '/local/inveniordm/lecturer/assignments.php',
            ['courseid' => $courseid]
        ),
        'Assignment created successfully'
    );
}

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/create_assignment.php',
        [
            'courseid' => $courseid
        ]
    )
);
$PAGE->set_context($context);
$PAGE->set_title('Create Assignment');

echo $OUTPUT->header();
$backurl = new moodle_url(
    '/local/inveniordm/lecturer/assignments.php',
    [
        'courseid' => $courseid
    ]
);

echo '
<div class="mb-4">
    <a href="'.$backurl.'" class="btn btn-outline-dark">
        <i class="fa fa-arrow-left"></i>
        Back to Assignments
    </a>
</div>
';

$client = new \local_inveniordm\api\invenio_client();
$searchresource = optional_param(
    'searchresource',
    '',
    PARAM_TEXT
);
$records = $client->get_records($searchresource);
$hits = $records['hits']['hits'] ?? [];
echo '
<form method="get" class="mb-4">
    <input
        type="hidden"
        name="courseid"
        value="'.$courseid.'">

    <div class="input-group">
        <input
            type="text"
            name="searchresource"
            class="form-control"
            value="'.s($searchresource).'"
            placeholder="Search Invenio resources">
        <button
            type="submit"
            class="btn btn-outline-primary">
            Search
        </button>
    </div>
</form>
';

echo '
    <h2>Create Assignment</h2>
    <form method="post">
        <div class="mb-3">
            <label>Assignment Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
    
        <div class="mb-3">
            <label>Instructions</label>
            <textarea name="instructions" class="form-control" rows="6"></textarea>
        </div>
    
        <div class="mb-3">
            <label>Due Date</label>
            <input type="date" name="duedate" class="form-control" required>
        </div>
';

echo '
    <div class="mb-4">
        <label class="form-label">
            Attach Resources
        </label>
';

foreach ($hits as $hit) {
    $recordid = $hit['id'] ?? '';
    $title =
        $hit['metadata']['title']
        ?? 'Untitled';

    echo '
        <div class="form-check">
            <input
                class="form-check-input"
                type="checkbox"
                name="resources['.s($recordid).']"
                value="'.s($title).'">
            <label class="form-check-label">
                '.s($title).'
                <small class="text-muted">
                    ('.$recordid.')
                </small>
            </label>
        </div>
    ';
}

echo '
    </div>
    <button type="submit" class="btn btn-primary">
        Save Assignment
    </button>
</form>
';

echo $OUTPUT->footer();