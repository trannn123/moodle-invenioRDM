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

    echo '<table border="1" cellpadding="8">';
    echo '<tr><th>Title</th><th>Download</th></tr>';

    foreach ($resources as $r) {

        $downloadurl = moodle_url::make_pluginfile_url(
            context_system::instance()->id,
            'local_inveniordm',
            'resource',
            $r->id,
            '/',
            basename($r->localpath)
        );

        echo '<tr>';
        echo '<td>'.s($r->title).'</td>';
        echo '<td><a href="'.$downloadurl.'">Download</a></td>';
        echo '</tr>';
    }

    echo '</table>';
}

echo $OUTPUT->footer();