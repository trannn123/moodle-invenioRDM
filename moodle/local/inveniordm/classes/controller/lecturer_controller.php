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
        $page = optional_param('page', 1, PARAM_INT);
        $search = trim($search);

        $service = new course_service();
        $data = $service->get_lecturer_my_courses(
            $USER->id,
            $search,
            $page
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

    public function get_my_resources_context(): array
    {
        $service = new resource_service();
        $data = $service->get_lecturer_my_resources();

        return array_merge($data, [
            'backurl' => (new moodle_url(
                '/local/inveniordm/index.php'
            ))->out(false),
        ]);
    }

    public function publish_submission(): void
    {
        $submissionid = required_param(
            'submissionid',
            PARAM_INT
        );

        $service = new submission_service();

        $service->publish_to_invenio(
            $submissionid
        );

        redirect(
            new moodle_url(
                '/local/inveniordm/lecturer/review_submission.php',
                [
                    'submissionid' => $submissionid
                ]
            ),
            'Published to Invenio successfully'
        );
    }

    public function get_review_submission_context(array $post): array
    {
        $submissionid = required_param(
            'submissionid',
            PARAM_INT
        );

        $service = new submission_service();

        if (!empty($post)) {
            $result = $service->save_review(
                $submissionid,
                trim(optional_param('grade', '', PARAM_TEXT)),
                trim(optional_param('feedback', '', PARAM_TEXT))
            );

            if ($result['success']) {
                redirect(
                    new moodle_url(
                        '/local/inveniordm/lecturer/review_submission.php',
                        [
                            'submissionid' => $submissionid
                        ]
                    ),
                    'Review saved successfully.'
                );
            }
        }

        $data = $service->get_review_submission(
            $submissionid
        );

        return array_merge($data, [
            'errors' => $result['errors'] ?? [],
            'backurl' => (new moodle_url(
                '/local/inveniordm/lecturer/view_submissions.php',
                [
                    'assignmentid' => $data['assignmentid']
                ]
            ))->out(false),
            'publishurl' => (new moodle_url(
                '/local/inveniordm/lecturer/publish_submission.php',
                [
                    'submissionid' => $submissionid
                ]
            ))->out(false),
        ]);
    }

    public function get_search_resources_to_attach_context(): array
    {
        $courseid = required_param(
            'courseid',
            PARAM_INT
        );

        $search = trim(
            optional_param(
                'q',
                '',
                PARAM_TEXT
            )
        );

        $service = new resource_service();

        $data = $service->search_resources_to_attach(
            $courseid,
            $search
        );

        return array_merge(
            $data,
            [
                'q' => $search,
                'backurl' => (
                new moodle_url(
                    '/local/inveniordm/lecturer/course_resources.php',
                    [
                        'courseid' => $courseid
                    ]
                )
                )->out(false),
                'searchurl' => (
                new moodle_url(
                    '/local/inveniordm/lecturer/search_resources_to_attach.php',
                    [
                        'courseid' => $courseid
                    ]
                )
                )->out(false)
            ]
        );
    }

    public function attach_resource(): void
    {
        global $USER;
        $courseid = required_param(
            'courseid',
            PARAM_INT
        );

        $recordid = required_param(
            'attach',
            PARAM_TEXT
        );

        $service = new resource_service();

        $service->attach_resource(
            $courseid,
            $recordid,
            $USER->id
        );

        redirect(
            new moodle_url(
                '/local/inveniordm/lecturer/search_resources_to_attach.php',
                [
                    'courseid' => $courseid
                ]
            ),
            'Attached successfully'
        );
    }

    public function process_upload($data, $user): array
    {
        $service = new upload_service();

        return $service->upload(
            $data,
            $user
        );
    }

    public function get_view_submissions_context(): array
    {
        $assignmentid = required_param(
            'assignmentid',
            PARAM_INT
        );

        $search = trim(
            optional_param(
                'search',
                '',
                PARAM_TEXT
            )
        );

        $service = new submission_service();

        $data = $service->get_view_submissions(
            $assignmentid,
            $search
        );

        return array_merge(
            $data,
            [
                'backurl' => (
                new moodle_url(
                    '/local/inveniordm/lecturer/assignments.php',
                    [
                        'courseid' => $data['courseid']
                    ]
                )
                )->out(false),

                'searchurl' => (
                new moodle_url(
                    '/local/inveniordm/lecturer/view_submissions.php',
                    [
                        'assignmentid' => $assignmentid
                    ]
                )
                )->out(false)
            ]
        );
    }
}