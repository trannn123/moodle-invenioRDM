<?php

defined('MOODLE_INTERNAL') || die();

class lecturer_controller
{
    public function get_all_assignments_context(): array
    {
        global $USER;
        $search = optional_param('search', '', PARAM_TEXT);
        $search = trim($search);
        $courses = enrol_get_my_courses();
        $service = new course_service();

        $assignments = $service->get_lecturer_assignments(
            $USER->id,
            $courses,
            $search
        );

        return [
            'assignments' => $assignments['items'],
            'totalassignments' => $assignments['totalassignments'],
            'totalcourses' => $assignments['totalcourses'],
            'search' => $search,
            'backurl' => (new moodle_url('/local/inveniordm/index.php'))->out(false),
            'reseturl' => (new moodle_url('/local/inveniordm/lecturer/all_assignments.php'))->out(false),
            'hasassignments' => !empty($assignments['items']),
        ];
    }
}