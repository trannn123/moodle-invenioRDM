<?php

require_once(__DIR__ . '/../../../config.php');
require_login();
global $USER, $PAGE, $OUTPUT, $DB;
$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/mycourses.php'
    )
);
$PAGE->set_context(context_system::instance());
$PAGE->set_title('My Teaching Courses');
$PAGE->set_heading('My Teaching Courses');

$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/courses_and_assignments.css'
    )
);

echo $OUTPUT->header();

$search = optional_param('search', '', PARAM_TEXT);
$search = trim($search);
$courses = enrol_get_users_courses($USER->id, true);

if (!empty($search)) {
    $courses = array_filter(
        $courses,
        function($course) use ($search) {
            if ($course->id == SITEID) {
                return false;
            }
            return (
                stripos($course->fullname, $search) !== false ||
                stripos($course->shortname, $search) !== false ||
                stripos((string)$course->id, $search) !== false
            );
        }
    );
}

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
        <h1>My Teaching Courses</h1>
        <p>Manage course resources and repository content for your teaching courses.</p>
    </div>
';

$backurl = new moodle_url(
    '/local/inveniordm/index.php'
);

echo '
    <form method="get" class="search-card mb-4">
        <div class="mb-3">
            <input type="text" name="search" class="form-control form-control-lg" placeholder="Search teaching courses..." value="'.s($search).'">
        </div>
    
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-primary">
                <i class="fa fa-search"></i>
                Search
            </button>
    
            <a href="'.$PAGE->url.'" class="btn btn-outline-secondary">
                <i class="fa fa-refresh"></i>
                Reset
            </a>
    
            <a href="'.$backurl.'" class="btn btn-outline-dark">
                <i class="fa fa-arrow-left"></i>
                Back
            </a>
        </div>
    </form>
';

echo '
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="stats-card">
                <h2>'.$totalcourses.'</h2>
                <p>Teaching Courses</p>
            </div>
        </div>
    
        <div class="col-md-6">
            <div class="stats-card">
                <h2>'.$totalresources.'</h2>
                <p>Attached Resources</p>
            </div>
        </div>
    </div>
';

if (empty($courses)) {
    echo $OUTPUT->notification('No courses found', 'info');
    echo $OUTPUT->footer();
    exit;
}

echo '<div class="course-grid">';

foreach ($courses as $course) {
    if ($course->id == SITEID) {
        continue;
    }

    $manageurl = new moodle_url(
        '/local/inveniordm/lecturer/course_resources.php',
        [
            'courseid' => $course->id
        ]
    );

    $assignurl = new moodle_url(
        '/local/inveniordm/lecturer/assignments.php',
        [
            'courseid'  => $course->id,
            'returnurl' => '/local/inveniordm/lecturer/mycourses.php'
        ]
    );

    $resourcecount = $DB->count_records(
        'local_inveniordm_course_resources',
        [
            'courseid' => $course->id
        ]
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
                <strong>Short Name</strong>
                <span>'.s($course->shortname).'</span>
            </div>
    
            <div class="course-info-row">
                <strong>Resources</strong>
                <span>'.$resourcecount.'</span>
            </div>
    
            <div class="mb-3">
                <span class="badge bg-success text-white p-2">Teaching</span>
            </div>
    
            <a class="btn btn-primary w-100 mb-2" href="'.$manageurl.'">
                Manage Resources
            </a>
    
            <a class="btn btn-outline-primary w-100" href="'.$assignurl.'">
                Open Assignments
            </a>
        </div>
    ';
}

echo '</div>';
echo $OUTPUT->footer();