<?php

require_once(__DIR__.'/../../../config.php');
global $DB, $PAGE, $OUTPUT, $USER;
$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid]);
if (!$course) {
    throw new moodle_exception('invalidcourseid', 'error');
}
$context = context_course::instance($course->id);
require_login($course);
if (!is_enrolled($context, $USER)) {
    throw new moodle_exception(
        'notenrolled',
        'enrol'
    );
}
$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/student/assignments.php',
        [
            'courseid' => $courseid
        ]
    )
);
$PAGE->set_context($context);
$PAGE->set_title('Assignments');
$PAGE->set_heading('Assignments');
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/course_assignments.css'
    )
);
echo $OUTPUT->header();
$assignments = $DB->get_records(
    'local_inveniordm_assignments',
    [
        'courseid' => $courseid
    ],
    'duedate ASC'
);
echo '
<div class="hero-section">
    <h1>Assignments</h1>
    <p>
        View and submit course assignments.
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

if (!$assignments) {
    echo $OUTPUT->notification(
        'No assignments found',
        'info'
    );

} else {
    echo '<div class="assignment-grid">';
    foreach ($assignments as $a) {
        $submiturl = new moodle_url(
            '/local/inveniordm/student/submit_assignment.php',
            [
                'assignmentid' => $a->id
            ]
        );
        $downloadurl = new moodle_url(
            '/local/inveniordm/student/download.php',
            [
                'recordid' => $a->resource_recordid
            ]
        );

        echo '
        <div class="assignment-card">
        
            <div class="assignment-title">
                '.s($a->name).'
            </div>
        
            <div class="assignment-description">
                '.s($a->description).'
            </div>
        
            <div class="assignment-due">
                Due:
                '.date(
                        'd/m/Y',
                        $a->duedate
                    ).'
            </div>
        
            <a
                class="btn btn-primary"
                href="'.$downloadurl.'"
            >
                Download Assignment
            </a>
            
            <a
                class="btn btn-success"
                href="'.$submiturl.'"
            >
                Submit Assignment
            </a>
        
        </div>
        ';
    }
    echo '</div>';
}
echo $OUTPUT->footer();