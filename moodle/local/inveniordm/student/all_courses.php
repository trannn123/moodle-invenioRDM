<?php

require_once(__DIR__ . '/../../../config.php');
require_login();

global $DB, $PAGE, $OUTPUT, $USER;

$PAGE->set_url(new moodle_url('/local/inveniordm/student/all_courses.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('All Courses');

$PAGE->requires->css(
    new moodle_url('/local/inveniordm/styles/mycourses.css')
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

/**
 * =========================
 * HERO SECTION
 * =========================
 */
echo '
<div class="hero-section">
    <h1>All Courses</h1>
    <p>Browse all available courses and their associated learning resources.</p>
</div>
';

/**
 * =========================
 * SEARCH BOX
 * =========================
 */
echo '
<form method="get" class="mb-4">
    <div class="input-group">

        <input type="text"
               name="search"
               class="form-control"
               placeholder="Search by course name, short name, or ID..."
               value="'.s($search).'">
        <div class="input-group-append">
             <button class="btn btn-primary" type="submit">
                Search
            </button>
    
            <a href="'.$PAGE->url.'" class="btn btn-secondary">
                Reset
            </a>
        </div>
       

    </div>
</form>
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
            <h2>Available</h2>
            <p>Repository</p>
        </div>
    </div>

</div>
';

/**
 * =========================
 * EMPTY STATE
 * =========================
 */
if (empty($courses)) {

    echo $OUTPUT->notification('No courses found', 'info');

    echo $OUTPUT->footer();
    exit;
}

/**
 * =========================
 * COURSE GRID
 * =========================
 */
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
    </div>';

    if (!$isenrolled) {

        echo '
    <a class="btn btn-info text-white"
       href="'.$enrolurl.'">
        Enrol Course
    </a>';

    } else {

        echo '
    <div class="mb-2">
        <span class="badge bg-primary-light p-2">
            ✓ Enrolled
        </span>
    </div>

    <a class="btn btn-primary"
       href="'.$resourceurl.'">
        Open Resources
    </a>

    <a class="btn btn-success"
       href="'.$assignurl.'">
        Open Assignments
    </a>';
    }

    echo '
</div>';
}

echo '</div>';

echo $OUTPUT->footer();