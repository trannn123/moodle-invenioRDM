<?php

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT, $USER;

$PAGE->set_url(new moodle_url('/local/inveniordm/student/all_courses.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('All Courses');
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
$courses = get_courses();

if (!empty($search)) {
    $courses = array_filter($courses, function($course) use ($search) {
        if ($course->id == SITEID) {
            return false;
        }
        return (
            stripos($course->fullname, $search) !== false ||
            stripos($course->shortname, $search) !== false ||
            stripos((string)$course->id, $search) !== false
        );
    });
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
        ['courseid' => $course->id]
    );
}

echo '<div class="container">';

echo '
    <div class="courses-hero">
        <div class="courses-hero-content">
            <h1>
                <i class="fa fa-graduation-cap"></i> All Courses
            </h1>
            <p>Browse all available courses and their associated learning resources.</p>
        </div>
        <div class="courses-hero-actions">
            <a href="' . new moodle_url('/local/inveniordm/index.php') . '" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
';

$backurl = new moodle_url('/local/inveniordm/index.php');
echo '
    <div class="search-card mb-4" style="margin-top: 24px;">
        <form method="get" class="search-form" action="' . $PAGE->url . '">
            <div class="search-input-group">
                <input type="text" name="search" class="form-control" placeholder="Search by course name, short name, or ID..." value="' . s($search) . '">
                <button class="btn btn-primary" type="submit">
                    <i class="fa fa-search"></i> Search
                </button>
                <a href="' . $PAGE->url . '" class="btn btn-outline-secondary">
                    <i class="fa fa-refresh"></i> Reset
                </a>
            </div>
        </form>
    </div>
';

echo '
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa fa-book"></i></div>
            <div>
                <div class="stat-number">' . $totalcourses . '</div>
                <div class="stat-label">Courses</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fa fa-file-alt"></i></div>
            <div>
                <div class="stat-number">' . $totalresources . '</div>
                <div class="stat-label">Resources</div>
            </div>
        </div>
    </div>
';

if (empty($courses)) {
    echo '<div class="alert alert-info">No courses found</div>';
    echo '</div>'; // close container
    echo $OUTPUT->footer();
    exit;
}

echo '<div class="course-grid">';

foreach ($courses as $course) {
    if ($course->id == SITEID) {
        continue;
    }
    $context = context_course::instance($course->id);
    $isenrolled = is_enrolled($context, $USER->id);
    $resourcecount = $DB->count_records(
        'local_inveniordm_course_resources',
        ['courseid' => $course->id]
    );
    $resourceurl = new moodle_url(
        '/local/inveniordm/student/course_resources.php',
        ['courseid' => $course->id]
    );
    $assignurl = new moodle_url(
        '/local/inveniordm/student/assignments.php',
        ['courseid' => $course->id]
    );
    $enrolurl = new moodle_url(
        '/local/inveniordm/student/enrol_course.php',
        [
            'courseid' => $course->id,
            'sesskey' => sesskey()
        ]
    );

    echo '
        <div class="course-card">
            <div class="course-card-header">
                <span class="course-title">' . format_string($course->fullname) . '</span>
                <span class="badge-teaching">' . ($isenrolled ? 'Enrolled' : 'Open') . '</span>
            </div>
            <div class="course-card-body">
                <div class="course-info-row">
                    <span class="course-info-label">Course ID</span>
                    <span class="course-info-value">' . $course->id . '</span>
                </div>
                <div class="course-info-row">
                    <span class="course-info-label">Short Name</span>
                    <span class="course-info-value">' . s($course->shortname) . '</span>
                </div>
                <div class="course-info-row">
                    <span class="course-info-label">Resources</span>
                    <span class="course-info-value">' . $resourcecount . '</span>
                </div>
            </div>
            <div class="course-card-actions">
    ';

    if (!$isenrolled) {
        echo '<a class="btn btn-primary" href="' . $enrolurl . '"><i class="fa fa-user-plus"></i> Join Course</a>';
    } else {
        echo '
            <a class="btn btn-primary" href="' . $resourceurl . '"><i class="fa fa-folder-open"></i> Open Resources</a>
            <a class="btn btn-outline-primary" href="' . $assignurl . '"><i class="fa fa-tasks"></i> Open Assignments</a>
        ';
    }

    echo '
        </div>
    </div>
    ';
}

echo '</div>';
echo '</div>';

echo $OUTPUT->footer();