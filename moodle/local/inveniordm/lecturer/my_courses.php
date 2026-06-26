<?php

require_once(__DIR__ . '/../../../config.php');
require_login();
global $USER, $PAGE, $OUTPUT, $DB;
$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/my_courses.php'
    )
);

$PAGE->set_context(context_system::instance());
$PAGE->set_title('My Teaching Courses');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/main.css'
    )
);
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/courses.css'
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

$backurl = new moodle_url(
    '/local/inveniordm/index.php'
);

echo '
    <div class="container mt-4">
        <div class="courses-hero mb-4">
            <div class="courses-hero-content">
                <h1>
                    <i class="fa fa-graduation-cap"></i> 
                    My Teaching Courses
                </h1>
                <p>Manage course resources and repository content for your teaching courses.</p>
            </div>
            <div class="courses-hero-actions">
                <a href="'.$backurl.'" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left"></i> 
                    Back
                </a>
            </div>
        </div>
        <div class="search-card mb-4">
            <form method="get" class="search-form">
                <div class="search-input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search teaching courses..." value="'.s($search).'">
                    <button class="btn btn-primary">
                        <i class="fa fa-search"></i>
                        Search
                    </button>
                    <a href="'.$PAGE->url.'" class="btn btn-outline-secondary">
                        <i class="fa fa-refresh"></i>
                        Reset
                    </a>
                </div>
            </form>
        </div>
    
        <div class="stats-grid mb-4">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa fa-book"></i></div>
                <div class="stat-content">
                    <div class="stat-number">'.$totalcourses.'</div>
                    <div class="stat-label">Teaching Courses</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa fa-file"></i></div>
                <div class="stat-content">
                    <div class="stat-number">'.$totalresources.'</div>
                    <div class="stat-label">Attached Resources</div>
                </div>
            </div>
        </div>
';

if (empty($courses)) {
    echo '
        <div class="alert-info-custom">
            <i class="fa fa-inbox fa-2x"></i>
            <p>No courses found</p>
            <span class="text-muted">You are not currently teaching any courses</span>
        </div>
    ';
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
            'courseid'  => $course->id
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
            <div class="course-card-header">
                <h3 class="course-title">'.format_string($course->fullname).'</h3>
                <span class="badge-teaching">Teaching</span>
            </div>
            <div class="course-card-body">
                <div class="course-info-row">
                    <span class="course-info-label">Course ID</span>
                    <span class="course-info-value">'.$course->id.'</span>
                </div>
                <div class="course-info-row">
                    <span class="course-info-label">Short Name</span>
                    <span class="course-info-value">'.s($course->shortname).'</span>
                </div>
                <div class="course-info-row">
                    <span class="course-info-label">Resources</span>
                    <span class="course-info-value">'.$resourcecount.'</span>
                </div>
            </div>
            <div class="course-card-actions">
                <a class="btn btn-primary w-100 mb-2" href="'.$manageurl.'">
                    <i class="fa fa-folder-open"></i> 
                    Manage Resources
                </a>
                <a class="btn btn-outline-primary" href="'.$assignurl.'">
                    <i class="fa fa-tasks"></i> 
                    Open Assignments
                </a>
            </div>
        </div>
    ';
}

echo '</div>';
echo '</div>';
echo $OUTPUT->footer();