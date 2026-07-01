<?php

defined('MOODLE_INTERNAL') || die();

class course_service
{
    public function get_all_courses(string $search = '', int $userid = 0): array
    {
        global $DB;
        $courses = get_courses();

        if (!empty($search)) {
            $courses = array_filter($courses, function ($course) use ($search) {
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

        $result = [];
        $totalcourses = 0;
        $totalresources = 0;

        foreach ($courses as $course) {
            if ($course->id == SITEID) {
                continue;
            }
            $context = context_course::instance($course->id);
            $isenrolled = is_enrolled($context, $userid);
            $resourcecount = $DB->count_records(
                'local_inveniordm_course_resources',
                ['courseid' => $course->id]
            );

            $totalcourses++;
            $totalresources += $resourcecount;

            $result[] = [
                'id' => $course->id,
                'fullname' => format_string($course->fullname),
                'shortname' => s($course->shortname),
                'resourcecount' => $resourcecount,
                'isenrolled' => $isenrolled,
                'notenrolled' => !$isenrolled,
                'status' => $isenrolled ? 'Enrolled' : 'Open',

                'resourceurl' => (new moodle_url(
                    '/local/inveniordm/student/course_resources.php',
                    ['courseid' => $course->id]
                ))->out(false),

                'assignurl' => (new moodle_url(
                    '/local/inveniordm/student/assignments.php',
                    ['courseid' => $course->id]
                ))->out(false),

                'enrolurl' => (new moodle_url(
                    '/local/inveniordm/student/enrol_course.php',
                    [
                        'courseid' => $course->id,
                        'sesskey' => sesskey()
                    ]
                ))->out(false),
            ];
        }

        return [
            'courses' => $result,
            'totalcourses' => $totalcourses,
            'totalresources' => $totalresources
        ];
    }

    public function get_my_courses(int $userid): array
    {
        global $DB;
        $courses = enrol_get_users_courses($userid, true);

        $courseitems = [];
        $totalcourses = 0;
        $totalresources = 0;

        foreach ($courses as $course) {
            if ($course->id == SITEID) {
                continue;
            }
            $totalcourses++;
            $resourcecount = $DB->count_records(
                'local_inveniordm_course_resources',
                ['courseid' => $course->id]
            );
            $totalresources += $resourcecount;
            $courseitems[] = [
                'id' => $course->id,
                'fullname' => format_string($course->fullname),
                'resourcecount' => $resourcecount,
                'resourceurl' => (new \moodle_url(
                    '/local/inveniordm/student/course_resources.php',
                    ['courseid' => $course->id]
                ))->out(false),
                'assignurl' => (new \moodle_url(
                    '/local/inveniordm/student/assignments.php',
                    ['courseid' => $course->id]
                ))->out(false),
            ];
        }

        return [
            'courses' => $courseitems,
            'totalcourses' => $totalcourses,
            'totalresources' => $totalresources,
            'hascourses' => !empty($courseitems),
        ];
    }

    public function enrol_self(int $courseid, int $userid): void
    {
        global $DB;
        $course = get_course($courseid);
        $context = \context_course::instance($courseid);

        if (is_enrolled($context, $userid)) {
            throw new \moodle_exception('alreadyenrolled', 'enrol');
        }

        $instances = enrol_get_instances($courseid, true);

        $selfinstance = null;
        foreach ($instances as $instance) {
            if ($instance->enrol === 'self') {
                $selfinstance = $instance;
                break;
            }
        }

        if (!$selfinstance) {
            throw new \moodle_exception('selfenrolmentdisabled', 'enrol');
        }

        $plugin = enrol_get_plugin('self');
        $plugin->enrol_user($selfinstance, $userid, 5);
    }
}