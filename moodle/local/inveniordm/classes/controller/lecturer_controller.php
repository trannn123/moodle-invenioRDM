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
        $service = new assignment_service();

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

    public function get_course_assignments_context(): array
    {
        global $USER;
        $courseid = required_param('courseid', PARAM_INT);
        $search = trim(optional_param('search', '', PARAM_TEXT));
        $service = new assignment_service();

        $data = $service->get_lecturer_course_assignments(
            $courseid,
            $USER->id,
            $search
        );

        return array_merge($data, [
            'courseid' => $courseid,
            'search' => $search,
            'backurl' => (new moodle_url('/local/inveniordm/lecturer/my_courses.php'))->out(false),
            'reseturl' => (new moodle_url('/local/inveniordm/lecturer/assignments.php', ['courseid' => $courseid]))->out(false),
            'createurl' => (new moodle_url('/local/inveniordm/lecturer/create_assignment.php', ['courseid' => $courseid]))->out(false),
        ]);
    }

    public function get_course_resources_context(int $courseid): array
    {
        $service = new resource_service();
        $data = $service->get_lecturer_course_resources($courseid);

        return array_merge($data, [
            'courseid' => $courseid,
            'backurl' => (new moodle_url(
                '/local/inveniordm/lecturer/my_courses.php'
            ))->out(false),
            'searchurl' => (new moodle_url(
                '/local/inveniordm/lecturer/search_resources_to_attach.php',
                ['courseid' => $courseid]
            ))->out(false),
        ]);
    }

    public function get_create_assignment_context(int $courseid, array $post): array
    {
        $service = new assignment_service();
        if (!empty($post)) {
            return $service->create_assignment($courseid, $post);
        }

        return $service->get_create_assignment_form_context($courseid);
    }

    public function get_my_courses_context(): array
    {
        global $USER;
        $search = optional_param('search', '', PARAM_TEXT);
        $search = trim($search);

        $service = new course_service();
        $data = $service->get_lecturer_my_courses(
            $USER->id,
            $search
        );

        return array_merge($data, [
            'search' => $search,
            'backurl' => (new moodle_url(
                '/local/inveniordm/index.php'
            ))->out(false),
            'reseturl' => (new moodle_url(
                '/local/inveniordm/lecturer/my_courses.php'
            ))->out(false),
        ]);
    }
}