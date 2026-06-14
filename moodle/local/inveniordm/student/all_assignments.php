<?php

require_once(__DIR__ . '/../../../config.php');
require_login();

global $DB, $PAGE, $OUTPUT, $USER;

$PAGE->set_url(new moodle_url('/local/inveniordm/student/all_assignments.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('All Assignments');

$PAGE->requires->css(
    new moodle_url('/local/inveniordm/styles/mycourses.css')
);

echo $OUTPUT->header();

$courses = enrol_get_my_courses();

$search = optional_param('search', '', PARAM_TEXT);
$search = trim($search);

echo '
<div class="hero-section">
    <h1>All Assignments</h1>
    <p>Browse all assignments across your enrolled courses.</p>
</div>
';

echo '
<form method="get" class="mb-4">
    <div class="input-group">

        <input type="text"
               name="search"
               class="form-control"
               placeholder="Search by assignment name or course..."
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

$assignments = [];

/**
 * Duyệt từng course để lấy assignment
 */
foreach ($courses as $course) {

    if ($course->id == SITEID) {
        continue;
    }

    $modinfo = get_fast_modinfo($course);

    foreach ($modinfo->instances['assign'] ?? [] as $cm) {

        $instance = $cm->instance;

        $assign = $DB->get_record('assign', ['id' => $instance]);

        if (!$assign) {
            continue;
        }

        /**
         * Search filter
         */
        if (!empty($search)) {
            if (stripos($assign->name, $search) === false &&
                stripos($course->fullname, $search) === false) {
                continue;
            }
        }

        $assignments[] = [
            'course' => $course,
            'cm' => $cm,
            'assign' => $assign
        ];
    }
}

/**
 * Stats
 */
$totalassignments = count($assignments);

echo '
<div class="row mb-4">

    <div class="col-md-4">
        <div class="stats-card">
            <h2>'.$totalassignments.'</h2>
            <p>Assignments</p>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stats-card">
            <h2>'.count($courses).'</h2>
            <p>Courses</p>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stats-card">
            <h2>Available</h2>
            <p>Status</p>
        </div>
    </div>

</div>
';

if (empty($assignments)) {
    echo $OUTPUT->notification('No assignments found', 'info');
    echo $OUTPUT->footer();
    exit;
}


echo '<div class="course-grid">';

foreach ($assignments as $item) {

    $course = $item['course'];
    $assign = $item['assign'];
    $cm = $item['cm'];

    $coursecontext = context_course::instance($course->id);
    $isenrolled = is_enrolled($coursecontext, $USER->id);

    $assignurl = new moodle_url(
        '/mod/assign/view.php',
        ['id' => $cm->id]
    );

    echo '
    <div class="course-card">

        <div class="course-title">
            '.format_string($assign->name).'
        </div>

        <div class="course-info-row">
            <strong>Course</strong>
            <span>'.format_string($course->fullname).'</span>
        </div>

        <div class="course-info-row">
            <strong>Course ID</strong>
            <span>'.$course->id.'</span>
        </div>

        <div class="course-info-row">
            <strong>Due Date</strong>
            <span>'.($assign->duedate ? date('Y-m-d H:i', $assign->duedate) : 'No due date').'</span>
        </div>

        <div class="course-info-row">
            <strong>Status</strong>
            <span>'.($isenrolled ? 'Enrolled' : 'Not enrolled').'</span>
        </div>

        <a class="btn btn-primary"
           href="'.$assignurl.'">
            Open Assignment
        </a>

    </div>
    ';
}

echo '</div>';

echo $OUTPUT->footer();