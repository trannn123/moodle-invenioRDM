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
echo "<h2>Course Resources</h2>";
if (!$resources) {
    echo "<p>No resources found</p>";
} else {
    echo '<div class="course-resource-list">';
    foreach ($resources as $res) {
        $viewurl = new moodle_url(
            '/local/inveniordm/student/view.php',
            [
                'id' => $res->recordid
            ]
        );
        $downloadurl = new moodle_url(
            '/local/inveniordm/student/download.php',
            [
                'recordid' => $res->recordid
            ]
        );
        echo '
        <div class="course-resource-card">
            <div class="course-resource-title">
                '.s($res->title).'
            </div>
            <div class="course-resource-actions">
                <a class="btn btn-primary"
                   href="'.$viewurl.'">
                    View Metadata
                </a>
                <a class="btn btn-secondary"
                   href="'.$downloadurl.'">
                    Download
                </a>
            </div>
        </div>
    ';
    }
    echo '</div>';
}
echo $OUTPUT->footer();