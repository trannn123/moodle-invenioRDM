<?php

require_once(__DIR__ . '/../../../config.php');
require_login();
$courseid = required_param('courseid', PARAM_INT);
global $DB, $PAGE, $OUTPUT;
$context = context_course::instance($courseid);
$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/course_resources.php',
        ['courseid' => $courseid]
    )
);

$PAGE->set_context($context);
$PAGE->set_title('Course Resources');
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

$backurl = new moodle_url('/local/inveniordm/lecturer/my_courses.php');
$searchurl = new moodle_url(
    '/local/inveniordm/lecturer/search_resources_to_attach.php',
    ['courseid' => $courseid]
);
$totalresources = count($resources);

echo '
    <div class="container mt-4">
        <div class="courses-hero mb-4">
            <div class="courses-hero-content">
                <h1><i class="fa fa-folder-open"></i> Course Resources</h1>
                <p>Manage learning resources attached to this course.</p>
            </div>
            <div class="courses-hero-actions">
                <a href="'.$backurl.'" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left"></i> 
                    Back
                </a>
            </div>
        </div>
    
        <div class="mb-4">
            <a class="btn btn-primary" href="'.$searchurl.'">
                <i class="fa fa-search"></i> 
                Search New Resource
            </a>
        </div>
    
        <div class="stats-grid mb-4">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa fa-file"></i></div>
                <div class="stat-content">
                    <div class="stat-number">'.$totalresources.'</div>
                    <div class="stat-label">Resources</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa fa-hashtag"></i></div>
                <div class="stat-content">
                    <div class="stat-number">'.$courseid.'</div>
                    <div class="stat-label">Course ID</div>
                </div>
            </div>
        </div>
';

if (!$resources) {
    echo '
        <div class="alert-info-custom">
            <i class="fa fa-inbox fa-2x"></i>
            <p>No resources found</p>
            <span class="text-muted">This course has no resources attached yet.</span>
        </div>
    ';
} else {
    echo '<div class="course-grid">';
    foreach ($resources as $res) {
        $viewurl = new moodle_url(
            '/local/inveniordm/resource/view.php',
            [
                'id' => $res->recordid,
                'returnurl' => qualified_me()
            ]
        );
        $downloadurl = new moodle_url(
            '/local/inveniordm/resource/download.php',
            ['recordid' => $res->recordid]
        );
        echo '
            <div class="resource-card">
                <div class="resource-card-header">
                    <h3 class="resource-title">'.s($res->title).'</h3>
                </div>
                <div class="resource-card-body">
                    <div class="resource-info-row">
                        <span class="resource-info-label">Record ID</span>
                        <span class="resource-info-value">'.s($res->recordid).'</span>
                    </div>
                    <div class="resource-info-row">
                        <span class="resource-info-label">Attached</span>
                        <span class="resource-info-value">'.userdate($res->timecreated, "%d/%m/%Y").'</span>
                    </div>
                </div>
                <div class="resource-card-actions">
                    <a class="btn btn-outline-primary" href="'.$viewurl.'">
                        <i class="fa fa-eye"></i> 
                        View Metadata
                    </a>
                    <a class="btn btn-outline-secondary" href="'.$downloadurl.'">
                        <i class="fa fa-download"></i> 
                        Download
                    </a>
                </div>
            </div>
        ';
    }
    echo '</div>';
}

echo '</div>';
echo $OUTPUT->footer();