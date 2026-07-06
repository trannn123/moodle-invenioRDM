<?php

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../api/invenio_client.php');

class student_controller
{
    public function get_all_courses_context(): array
    {
        $search = optional_param('search', '', PARAM_TEXT);
        $search = trim($search);

        $page = optional_param('page', 1, PARAM_INT);

        $service = new course_service();
        $data = $service->get_all_courses($search, $GLOBALS['USER']->id, $page);

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

    public function get_all_assignments_context(): array
    {
        $search = optional_param('search', '', PARAM_TEXT);
        $page = optional_param('page', 1, PARAM_INT);
        $search = trim($search);
        $service = new assignment_service();
        $data = $service->get_all_assignments($GLOBALS['USER']->id, $search, $page);

        return array_merge($data, [
            'search' => $search,
            'backurl' => (new \moodle_url('/local/inveniordm/index.php'))->out(false),
            'currenturl' => (new \moodle_url('/local/inveniordm/student/all_assignments.php'))->out(false),
        ]);
    }

    public function get_course_assignments_context(): array
    {
        $courseid = required_param('courseid', PARAM_INT);
        $page = optional_param('page', 1, PARAM_INT);
        $service = new assignment_service();
        $data = $service->get_course_assignments(
            $courseid,
            $GLOBALS['USER']->id,
            $page
        );

        return array_merge($data, [
            'backurl' => (new \moodle_url(
                '/local/inveniordm/student/all_courses.php'
            ))->out(false),
        ]);
    }

    public function get_course_resources_context(): array
    {
        $courseid = required_param('courseid', PARAM_INT);
        $page = optional_param('page', 1, PARAM_INT);
        $service = new resource_service();
        $data = $service->get_course_resources($courseid, $page);

        return array_merge($data, [
            'backurl' => (new \moodle_url(
                '/local/inveniordm/student/all_courses.php'
            ))->out(false),
        ]);
    }

    public function enrol_course(): void
    {
        require_sesskey();
        $courseid = required_param('courseid', PARAM_INT);
        $service = new course_service();
        $service->enrol_self($courseid, $GLOBALS['USER']->id);
        \core\notification::success('Enrolled successfully');

        redirect(
            new \moodle_url('/local/inveniordm/student/my_courses.php')
        );
    }

    public function submit_assignment(array $post, array $files): void
    {
        global $USER;
        $assignmentid = $post['assignmentid'] ?? 0;

        if (!$assignmentid) {
            throw new moodle_exception('missingassignmentid');
        }

        if (empty($files['submission']) || empty($files['submission']['name'])) {
            throw new moodle_exception('nofile');
        }

        $service = new submission_service();

        $courseid = $service->handle_submission(
            (int)$assignmentid,
            $USER->id,
            $files['submission']
        );

        redirect(
            new moodle_url('/local/inveniordm/student/assignments.php', [
                'courseid' => $courseid
            ]),
            'Submitted successfully'
        );
    }
}