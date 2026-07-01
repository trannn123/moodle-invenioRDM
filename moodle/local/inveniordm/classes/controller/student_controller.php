<?php

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../api/invenio_client.php');

class student_controller
{
    public function get_all_courses_context(): array
    {
        $search = optional_param('search', '', PARAM_TEXT);
        $search = trim($search);

        $service = new course_service();
        $data = $service->get_all_courses($search, $GLOBALS['USER']->id);

        return array_merge($data, [
            'search' => $search,
            'backurl' => (new \moodle_url('/local/inveniordm/index.php'))->out(false),
            'reseturl' => (new \moodle_url('/local/inveniordm/student/all_courses.php'))->out(false),
            'hascourses' => !empty($data['courses'])
        ]);
    }

    public function get_my_courses_context(): array
    {
        global $USER;
        $service = new course_service();
        $data = $service->get_my_courses($USER->id);

        return array_merge($data, [
            'backurl' => (new \moodle_url('/local/inveniordm/index.php'))->out(false),
        ]);
    }
}