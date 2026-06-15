<?php

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT;
$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);

$PAGE->set_url(new moodle_url(
    '/local/inveniordm/student/course_resources.php',
    ['courseid' => $courseid]
));
$PAGE->set_context($context);
$PAGE->set_title('Course Resources');
$PAGE->set_heading('Course Resources');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/course_resources.css'
    )
);
echo $OUTPUT->header();
echo '
<div class="hero-section">
    <h1>Course Resources</h1>
    <p>
        Browse learning resources attached
        to this course.
    </p>
</div>
';

$backurl = new moodle_url(
    '/local/inveniordm/student/all_courses.php'
);
echo '
<div class="mb-4">
    <a href="'.$backurl.'"
       class="btn btn-outline-secondary">
       <i class="fa fa-arrow-left"></i>
        Back to All Courses
    </a>
</div>
';

$resources = $DB->get_records(
    'local_inveniordm_course_resources',
    ['courseid' => $courseid],
    'timecreated DESC'
);

if (!$resources) {
    echo $OUTPUT->notification('No resources found', 'info');
    echo $OUTPUT->footer();
    exit;
}


$client = new \local_inveniordm\api\invenio_client();
echo '<div class="resource-grid">';
foreach ($resources as $res) {
    $viewurl = new moodle_url(
        '/local/inveniordm/resource/view.php',
        [
            'id' => $res->recordid,
            'returnurl' => qualified_me()
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
        <div class="resource-info-row">
            <strong>Added</strong>
            <span>'.userdate($res->timecreated).'</span>
        </div>   
        <div class="resource-actions">   
            <a class="btn btn-primary"
               href="'.$viewurl.'">
                View Metadata
            </a>   
        </div>    
    </div>    
    ';
}
echo '</div>';
echo $OUTPUT->footer();