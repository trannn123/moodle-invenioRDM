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

    public function get_all_assignments(int $userid, string $search = ''): array
    {
        global $DB;
        $courses = enrol_get_users_courses($userid, true);
        $assignments = [];

        foreach ($courses as $course) {
            if ($course->id == SITEID) {
                continue;
            }
            $localassignments = $DB->get_records(
                'local_inveniordm_assignments',
                ['courseid' => $course->id]
            );

            foreach ($localassignments as $assignment) {
                if (!empty($search)) {
                    if (stripos($assignment->name, $search) === false &&
                        stripos($course->fullname, $search) === false) {
                        continue;
                    }
                }
                $submission = $DB->get_record(
                    'local_inveniordm_submissions',
                    [
                        'assignmentid' => $assignment->id,
                        'studentid' => $userid
                    ]
                );
                $submitted = !empty($submission);
                $assignments[] = [
                    'id' => $assignment->id,
                    'name' => format_string($assignment->name),
                    'coursename' => format_string($course->fullname),
                    'duedate' => $assignment->duedate
                        ? userdate($assignment->duedate, get_string('strftimedate', 'langconfig'))
                        : 'No due date',
                    'submitted' => $submitted,
                    'filename' => $submitted ? s($submission->filename) : '',
                    'badge' => $submitted
                        ? '<span class="badge-status status-active">Submitted</span>'
                        : '<span class="badge-status status-overdue">Not Submitted</span>',
                    'submiturl' => (new \moodle_url(
                        '/local/inveniordm/student/submit_assignment.php',
                        ['assignmentid' => $assignment->id]
                    ))->out(false),
                    'submitbtnclass' => $submitted ? 'btn-outline-primary' : 'btn-primary',
                    'submiticon' => $submitted ? 'fa-eye' : 'fa-upload',
                    'submitlabel' => $submitted ? 'View Submission' : 'Submit Assignment',
                ];
            }
        }

        return [
            'assignments' => $assignments,
            'totalassignments' => count($assignments),
            'totalcourses' => count($courses),
            'hasassignments' => !empty($assignments),
        ];
    }

    public function get_course_assignments(int $courseid, int $userid): array
    {
        global $DB;
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $context = \context_course::instance($courseid);

        if (!is_enrolled($context, $userid)) {
            throw new \moodle_exception('notenrolled', 'enrol');
        }

        $assignments = $DB->get_records(
            'local_inveniordm_assignments',
            ['courseid' => $courseid],
            'duedate ASC'
        );

        $result = [];

        foreach ($assignments as $a) {
            $submission = $DB->get_record(
                'local_inveniordm_submissions',
                [
                    'assignmentid' => $a->id,
                    'studentid' => $userid
                ]
            );

            $submitted = !empty($submission);

            $result[] = [
                'id' => $a->id,
                'name' => s($a->name),
                'duedate' => $a->duedate
                    ? userdate($a->duedate, get_string('strftimedate', 'langconfig'))
                    : 'No due date',
                'description' => !empty($a->description) ? s($a->description) : '',
                'submitted' => $submitted,
                'filename' => $submitted ? s($submission->filename) : '',
                'badge' => $submitted
                    ? '<span class="badge-status status-active">Submitted</span>'
                    : '<span class="badge-status status-overdue">Not Submitted</span>',
                'btnclass' => $submitted ? 'btn-outline-primary' : 'btn-primary',
                'icon' => $submitted ? 'fa-eye' : 'fa-upload',
                'buttonlabel' => $submitted ? 'View Submission' : 'Submit Assignment',
                'submiturl' => (new \moodle_url(
                    '/local/inveniordm/student/submit_assignment.php',
                    ['assignmentid' => $a->id]
                ))->out(false),
            ];
        }

        return [
            'assignments' => $result,
            'hasassignments' => !empty($result),
        ];
    }

    public function get_course_resources(int $courseid): array
    {
        global $DB;
        $resources = $DB->get_records(
            'local_inveniordm_course_resources',
            ['courseid' => $courseid],
            'timecreated DESC'
        );

        $result = [];

        foreach ($resources as $res) {
            $result[] = [
                'id' => $res->id,
                'title' => s($res->title),
                'recordid' => s($res->recordid),
                'timecreated' => userdate($res->timecreated),
                'viewurl' => (new \moodle_url(
                    '/local/inveniordm/resource/view.php',
                    [
                        'id' => $res->recordid,
                        'returnurl' => qualified_me()
                    ]
                ))->out(false),
            ];
        }

        return [
            'resources' => $result,
            'hasresources' => !empty($result),
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

    public function get_lecturer_assignments(int $userid, array $courses, string $search = ''): array
    {
        global $DB;
        $items = [];
        $totalcourses = 0;

        foreach ($courses as $course) {
            if ($course->id == SITEID) {
                continue;
            }
            $context = \context_course::instance($course->id);

            if (!has_capability('local/inveniordm:upload', $context)) {
                continue;
            }
            $totalcourses++;
            $assignments = $DB->get_records(
                'local_inveniordm_assignments',
                ['courseid' => $course->id]
            );

            foreach ($assignments as $a) {
                if (!empty($search)) {
                    if (stripos($a->name, $search) === false &&
                        stripos($course->fullname, $search) === false) {
                        continue;
                    }
                }

                $resourcecount = $DB->count_records(
                    'local_inveniordm_assignment_resources',
                    ['assignmentid' => $a->id]
                );

                $submissioncount = $DB->count_records(
                    'local_inveniordm_submissions',
                    ['assignmentid' => $a->id]
                );

                $items[] = [
                    'id' => $a->id,
                    'name' => format_string($a->name),
                    'coursename' => format_string($course->fullname),
                    'course' => $course,
                    'duedate' => $a->duedate
                        ? date('d/m/Y H:i', $a->duedate)
                        : 'No due date',
                    'resourcecount' => $resourcecount,
                    'submissioncount' => $submissioncount,
                    'status' => ($a->duedate > 0 && $a->duedate < time())
                        ? 'Overdue'
                        : 'Active',
                    'statusclass' => ($a->duedate > 0 && $a->duedate < time())
                        ? 'status-overdue'
                        : 'status-active',
                    'submissionsurl' => (new \moodle_url(
                        '/local/inveniordm/lecturer/view_submissions.php',
                        ['assignmentid' => $a->id]
                    ))->out(false),
                ];
            }
        }

        return [
            'items' => $items,
            'totalassignments' => count($items),
            'totalcourses' => $totalcourses
        ];
    }

    public function get_lecturer_course_assignments(int $courseid, int $userid, string $search = ''): array
    {
        global $DB;
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $context = \context_course::instance($courseid);

        if (!has_capability('local/inveniordm:upload', $context)) {
            throw new \required_capability_exception($context, 'local/inveniordm:upload', 'nopermission', '');
        }

        $assignments = $DB->get_records(
            'local_inveniordm_assignments',
            ['courseid' => $courseid],
            'duedate ASC'
        );

        $items = [];

        foreach ($assignments as $a) {
            if (!empty($search)) {
                if (stripos($a->name, $search) === false &&
                    stripos((string)$a->id, $search) === false) {
                    continue;
                }
            }

            $resources = $DB->get_records(
                'local_inveniordm_assignment_resources',
                ['assignmentid' => $a->id]
            );

            $isoverdue = ($a->duedate > 0 && $a->duedate < time());

            $daysleft = null;
            $remainingtext = '';

            if (!$isoverdue && $a->duedate > 0) {
                $daysleft = ceil(($a->duedate - time()) / 86400);
                $remainingtext = $daysleft . ' day(s) remaining';
            } else {
                $remainingtext = 'Deadline passed';
            }

            $items[] = [
                'id' => $a->id,
                'name' => format_string($a->name),
                'duedate' => $a->duedate ? date('d/m/Y H:i', $a->duedate) : 'No due date',
                'timeline' => $remainingtext,

                'resourcecount' => count($resources),
                'resources' => array_map(function ($r) {
                    return ['title' => s($r->title)];
                }, $resources),

                'hasresources' => !empty($resources),

                'instructions' => !empty($a->instructions)
                    ? format_text($a->instructions, FORMAT_HTML)
                    : '',

                'status' => $isoverdue ? 'Overdue' : 'Active',
                'statusclass' => $isoverdue ? 'status-overdue' : 'status-active',

                'submissionsurl' => (new \moodle_url(
                    '/local/inveniordm/lecturer/view_submissions.php',
                    ['assignmentid' => $a->id]
                ))->out(false),
            ];
        }

        return [
            'course' => [
                'id' => $course->id,
                'fullname' => format_string($course->fullname),
            ],
            'assignments' => $items,
            'totalassignments' => count($items),
            'hasassignments' => !empty($items),
        ];
    }

    public function get_lecturer_course_resources(int $courseid): array
    {
        global $DB;
        $resources = $DB->get_records(
            'local_inveniordm_course_resources',
            ['courseid' => $courseid],
            'timecreated DESC'
        );
        $result = [];

        foreach ($resources as $res) {
            $result[] = [
                'title' => s($res->title),
                'recordid' => s($res->recordid),
                'timecreated' => userdate($res->timecreated, '%d/%m/%Y'),
                'viewurl' => (new moodle_url(
                    '/local/inveniordm/resource/view.php',
                    [
                        'id' => $res->recordid,
                        'returnurl' => qualified_me()
                    ]
                ))->out(false),
                'downloadurl' => (new moodle_url(
                    '/local/inveniordm/resource/download.php',
                    ['recordid' => $res->recordid]
                ))->out(false),
            ];
        }

        return [
            'resources' => $result,
            'hasresources' => !empty($result),
            'totalresources' => count($result),
        ];
    }

    public function create_assignment(int $courseid, array $post): array
    {
        global $DB, $USER;
        $assignment = (object)[
            'courseid' => $courseid,
            'name' => required_param('name', PARAM_TEXT),
            'instructions' => optional_param('instructions', '', PARAM_TEXT),
            'duedate' => strtotime(required_param('duedate', PARAM_TEXT)),
            'createdby' => $USER->id,
            'timecreated' => time()
        ];

        $assignmentid = $DB->insert_record('local_inveniordm_assignments', $assignment);
        $resources = $_POST['resources'] ?? [];

        foreach ($resources as $recordid => $title) {
            $DB->insert_record('local_inveniordm_assignment_resources', (object)[
                'assignmentid' => $assignmentid,
                'recordid' => $recordid,
                'title' => $title
            ]);
        }

        redirect(
            new moodle_url('/local/inveniordm/lecturer/assignments.php', [
                'courseid' => $courseid
            ]),
            'Assignment created successfully'
        );
    }

    public function get_create_assignment_form_context(int $courseid): array
    {
        global $DB;
        $client = new \local_inveniordm\api\invenio_client();

        $search = optional_param('searchresource', '', PARAM_TEXT);
        $page = optional_param('page', 1, PARAM_INT);
        $pagesize = 25;

        $records = $client->get_records($search, [
            'page' => $page,
            'size' => $pagesize
        ]);

        $hits = $records['hits']['hits'] ?? [];
        $totalrecords = $records['hits']['total'] ?? count($hits);
        $totalpages = max(1, ceil($totalrecords / $pagesize));

        return [
            'courseid' => $courseid,
            'search' => $search,
            'page' => $page,
            'backurl' => (new moodle_url(
                '/local/inveniordm/lecturer/assignments.php',
                ['courseid' => $courseid]
            ))->out(false),
            'hits' => array_map(function ($hit) {
                return [
                    'id' => $hit['id'] ?? '',
                    'title' => $hit['metadata']['title'] ?? 'Untitled'
                ];
            }, $hits),
            'totalrecords' => $totalrecords,
            'totalpages' => $totalpages,
            'hasresources' => !empty($hits),
        ];
    }
}