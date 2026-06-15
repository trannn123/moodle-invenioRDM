<?php

require_once(__DIR__ . '/../../../config.php');
require_login();
$courseid = required_param('courseid', PARAM_INT);
global $DB, $PAGE, $OUTPUT;
$context = context_course::instance($courseid);
$PAGE->set_url(new moodle_url('/local/inveniordm/student/course_resources.php',
    ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title('Course Resources');
$PAGE->set_heading('Course Resources');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/course_resources.css'
    )
);
echo $OUTPUT->header();
$resources = $DB->get_records(
    'local_inveniordm_course_resources',
    ['courseid' => $courseid],
    'timecreated DESC'
);
echo '
<div class="hero-section">
    <h1>Course Resources</h1>
    <p>
        Manage learning resources attached
        to this course.
    </p>
</div>
';
$searchurl = new moodle_url(
    '/local/inveniordm/lecturer/search_resources_to_attach.php',
    [
        'courseid' => $courseid
    ]
);

$assignmentsurl = new moodle_url(
    '/local/inveniordm/lecturer/assignments.php',
    [
        'courseid' => $courseid
    ]
);

echo '
<div class="mb-4">
    <a class="btn btn-primary"
       href="'.$searchurl.'">
        Search New Resource
    </a>

    <a class="btn btn-secondary"
       href="'.$assignmentsurl.'">
        Assignments
    </a>
</div>
';
if (!$resources) {
    echo "<p>No resources found</p>";
} else {
    echo '<div class="resource-grid">';
    foreach ($resources as $res) {
        $viewurl = new moodle_url(
            '/local/inveniordm/resource/view.php',
            [
                'id' => $res->recordid,
                'returnurl' => qualified_me()
            ]
        );
        $downloadurl = new moodle_url(
            '/local/inveniordm/student/download.php',
            [
                'recordid' => $res->recordid
            ]
        );
        $assignmenturl = new moodle_url(
            '/local/inveniordm/lecturer/create_assignment.php',
            [
                'courseid' => $courseid,
                'recordid' => $res->recordid
            ]
        );
        echo '
        <div class="resource-card">
            <div class="resource-title">
                '.s($res->title).'
            </div>
            <div class="resource-info-row">
                <strong>Record ID</strong>
                <span>'.s($res->recordid).'</span>
            </div>
            <div class="resource-actions">
                <a class="btn btn-primary"
                   href="'.$viewurl.'">
                    View Metadata
                </a>
                <a class="btn btn-secondary"
                   href="'.$downloadurl.'">
                    Download
                </a>
                <a class="btn btn-success"
                   href="'.$assignmenturl.'">
                    Create Assignment
                </a>
            </div>
        </div>
    ';
    }
    echo '</div>';
}
echo $OUTPUT->footer();