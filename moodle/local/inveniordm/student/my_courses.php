<?php

require_once(__DIR__ . '/../../../config.php');
require_login();
global $USER, $OUTPUT, $PAGE, $DB;

$PAGE->set_url(new moodle_url('/local/inveniordm/student/my_courses.php'));

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$backurl = !empty($returnurl) ? $returnurl : new moodle_url('/local/inveniordm/index.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_title('My Courses');
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

echo '<div class="container">';

echo '
<div class="courses-hero">
    <div class="courses-hero-content">
        <h1><i class="fa fa-book"></i> My Learning Resources</h1>
        <p>Access digital learning resources attached to your enrolled courses.</p>
    </div>
    <div class="courses-hero-actions">
        <a href="' . $backurl . '" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>
';

$courses = enrol_get_users_courses($USER->id, true);
$totalcourses = 0;
$totalresources = 0;

foreach ($courses as $course) {
    if ($course->id == SITEID) continue;
    $totalcourses++;
    $totalresources += $DB->count_records('local_inveniordm_course_resources', ['courseid' => $course->id]);
}

echo '
    <div class="stats-grid" style="margin-top: 24px; margin-bottom: 32px;">
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
    echo '
        <div class="alert-info-custom">
            <i class="fa fa-info-circle fa-3x"></i>
            <p>No courses found</p>
            <div class="text-muted">You are not enrolled in any courses.</div>
        </div>
    ';
} else {
    echo '<div class="course-grid">';
    foreach ($courses as $course) {
        if ($course->id == SITEID) continue;
        $resourcecount = $DB->count_records('local_inveniordm_course_resources', ['courseid' => $course->id]);
        $url = new moodle_url('/local/inveniordm/student/course_resources.php', ['courseid' => $course->id]);
        $assignurl = new moodle_url('/local/inveniordm/student/assignments.php', ['courseid' => $course->id]);

        echo '
            <div class="course-card">
                <div class="course-card-header">
                    <span class="course-title">' . format_string($course->fullname) . '</span>
                    <span class="badge-teaching">Enrolled</span>
                </div>
                <div class="course-card-body">
                    <div class="course-info-row">
                        <span class="course-info-label">Course ID</span>
                        <span class="course-info-value">' . $course->id . '</span>
                    </div>
                    <div class="course-info-row">
                        <span class="course-info-label">Resources</span>
                        <span class="course-info-value">' . $resourcecount . '</span>
                    </div>
                </div>
                <div class="course-card-actions">
                    <a class="btn btn-primary" href="' . $url . '"><i class="fa fa-folder-open"></i> Open Resources</a>
                    <a class="btn btn-outline-primary" href="' . $assignurl . '"><i class="fa fa-tasks"></i> Open Assignments</a>
                </div>
            </div>
        ';
    }
    echo '</div>';
}

echo '</div>';
echo $OUTPUT->footer();