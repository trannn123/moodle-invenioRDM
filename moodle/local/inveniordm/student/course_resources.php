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
    new moodle_url('/local/inveniordm/styles/course_resources.css')
);

echo $OUTPUT->header();

echo '<div class="container">';

$backurl = new moodle_url('/local/inveniordm/student/all_courses.php');
echo '
    <div class="courses-hero">
        <div class="courses-hero-content">
            <h1>
                <i class="fa fa-folder-open"></i> Course Resources
            </h1>
            <p>Browse learning resources attached to this course.</p>
        </div>
        <div class="courses-hero-actions">
            <a href="' . $backurl . '" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Back to All Courses
            </a>
        </div>
    </div>
';

$resources = $DB->get_records(
    'local_inveniordm_course_resources',
    ['courseid' => $courseid],
    'timecreated DESC'
);

if (!$resources) {
    echo '<div class="alert-info-custom">';
    echo '<i class="fa fa-info-circle fa-3x"></i>';
    echo '<p>No resources found</p>';
    echo '<div class="text-muted">This course currently has no attached learning resources.</div>';
    echo '</div>';
    echo '</div>'; // close container
    echo $OUTPUT->footer();
    exit;
}

$client = new \local_inveniordm\api\invenio_client();
echo '<div class="course-grid">';

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
            <div class="resource-card-header">
                <span class="resource-title">' . s($res->title) . '</span>
            </div>
            <div class="resource-card-body">
                <div class="resource-info-row">
                    <span class="resource-info-label">Record ID</span>
                    <span class="resource-info-value">' . s($res->recordid) . '</span>
                </div>
                <div class="resource-info-row">
                    <span class="resource-info-label">Added</span>
                    <span class="resource-info-value">' . userdate($res->timecreated) . '</span>
                </div>
            </div>
            <div class="resource-card-actions">
                <a class="btn btn-primary" href="' . $viewurl . '">
                    <i class="fa fa-eye"></i> View Metadata
                </a>
            </div>
        </div>
    ';
}

echo '</div>';
echo '</div>';

echo $OUTPUT->footer();