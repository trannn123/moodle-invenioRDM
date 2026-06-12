<?php

require_once(__DIR__ . '/../../../config.php');
require_login();
global $USER, $OUTPUT, $PAGE, $DB;

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/student/mycourses.php'
    )
);

$PAGE->set_context(
    context_system::instance()
);

$PAGE->set_title('My Courses');
$PAGE->set_heading('My Courses');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/mycourses.css'
    )
);
echo $OUTPUT->header();
$courses = enrol_get_users_courses(
    $USER->id,
    true
);
$totalcourses = 0;
$totalresources = 0;
foreach ($courses as $course) {
    if ($course->id == SITEID) {
        continue;
    }
    $totalcourses++;
    $totalresources += $DB->count_records(
        'local_inveniordm_course_resources',
        [
            'courseid' => $course->id
        ]
    );
}

echo '
<div class="hero-section">
    <h1>My Learning Resources</h1>
    <p>
        Access digital learning resources attached
        to your enrolled courses.
    </p>
</div>
';

echo '
<div class="row mb-4">
    <div class="col-md-4">
        <div class="stats-card">
            <h2>'.$totalcourses.'</h2>
            <p>Courses</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card">
            <h2>'.$totalresources.'</h2>
            <p>Resources</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card">
            <h2>Online</h2>
            <p>Repository</p>
        </div>
    </div>
</div>

';

if (empty($courses)) {
    echo $OUTPUT->notification(
        'No courses found',
        'info'
    );
} else {
    echo '<div class="course-grid">';
    foreach ($courses as $course) {
        if ($course->id == SITEID) {
            continue;
        }
        $resourcecount = $DB->count_records(
            'local_inveniordm_course_resources',
            [
                'courseid' => $course->id
            ]
        );
        $url = new moodle_url(
            '/local/inveniordm/student/course_resources.php',
            [
                'courseid' => $course->id
            ]
        );
        $assignurl = new moodle_url(
            '/local/inveniordm/student/assignments.php',
            ['courseid' => $course->id]
        );
        echo '
        <div class="course-card"> 
            <div class="course-title">
                '.format_string($course->fullname).'
            </div>       
            <div class="course-info-row">
                <strong>Course ID</strong>
                <span>'.$course->id.'</span>
            </div>            
            <div class="course-info-row">
                <strong>Resources</strong>
                <span>'.$resourcecount.'</span>
            </div>       
            <a class="btn btn-primary"
               href="'.$url.'">
                Open Resources
            </a>  
            <a class="btn btn-success" href="'.$assignurl.'">
                Open Assignments
            </a>      
        </div>        
        ';
    }
    echo '</div>';
}
echo $OUTPUT->footer();