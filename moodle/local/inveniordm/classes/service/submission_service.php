<?php

use core\exception\moodle_exception;
use local_inveniordm\service\pagination_service;

defined('MOODLE_INTERNAL') || die();

class submission_service
{
    private const COURSE_PAGE_SIZE = 1;

    public function handle_submission(int $assignmentid, int $userid, ?array $file): int
    {
        global $DB;

        $assignment = $DB->get_record(
            'local_inveniordm_assignments',
            ['id' => $assignmentid],
            '*',
            MUST_EXIST
        );

        if (time() > $assignment->duedate) {
            throw new moodle_exception('deadlinepassed');
        }

        if (!$file || empty($file['name'])) {
            throw new moodle_exception('nofile');
        }

        $existing = $DB->get_record('local_inveniordm_submissions', [
            'assignmentid' => $assignmentid,
            'studentid' => $userid
        ]);

        if ($existing) {
            $submissionid = $existing->id;
            $existing->filename = $file['name'];
            $existing->status = 'submitted';
            $existing->timemodified = time();
            $DB->update_record('local_inveniordm_submissions', $existing);
        } else {
            $submissionid = $DB->insert_record('local_inveniordm_submissions', [
                'assignmentid' => $assignmentid,
                'studentid' => $userid,
                'filename' => $file['name'],
                'status' => 'submitted',
                'timecreated' => time()
            ]);
        }

        $context = \context_course::instance($assignment->courseid);
        $fs = get_file_storage();

        $fs->create_file_from_pathname([
            'contextid' => $context->id,
            'component' => 'local_inveniordm',
            'filearea' => 'submission',
            'itemid' => $submissionid,
            'filepath' => '/',
            'filename' => $file['name'],
        ], $file['tmp_name']);

        \log_service::add(
            $userid,
            'SUBMIT_ASSIGNMENT',
            null,
            $assignment->courseid
        );

        return $assignment->courseid;
    }

    public function publish_to_invenio(int $submissionid): void
    {
        global $DB;
        $submission = $DB->get_record(
            'local_inveniordm_submissions',
            [
                'id' => $submissionid
            ],
            '*',
            MUST_EXIST
        );

        $assignment = $DB->get_record(
            'local_inveniordm_assignments',
            [
                'id' => $submission->assignmentid
            ],
            '*',
            MUST_EXIST
        );

        $context = context_course::instance(
            $assignment->courseid
        );

        require_capability(
            'local/inveniordm:upload',
            $context
        );

        $fs = get_file_storage();

        $files = $fs->get_area_files(
            $context->id,
            'local_inveniordm',
            'submission',
            $submission->id,
            'id',
            false
        );
        if (empty($files)) {
            throw new moodle_exception(
                'Submission file not found'
            );
        }

        $file = reset($files);
        $tempfile = tempnam(sys_get_temp_dir(), 'inv_');
        $file->copy_content_to($tempfile);
        $client = new \local_inveniordm\api\invenio_client();

        $student = $DB->get_record(
            'user',
            [
                'id' => $submission->studentid
            ],
            '*',
            MUST_EXIST
        );

        $course = $DB->get_record(
            'course',
            [
                'id' => $assignment->courseid
            ],
            '*',
            MUST_EXIST
        );

        $recordpayload = [
            'files' => ['enabled' => true],
            'metadata' => [
                'title' => $assignment->name . ' - ' . fullname($student),
                'description' =>
                    "Student assignment submission\n\n" .
                    "Course: " . $course->fullname . "\n" .
                    "Assignment: " . $assignment->name . "\n" .
                    "Student: " . fullname($student) . "\n" .
                    "Grade: " . ($submission->grade ?: 'Not graded') . "\n" .
                    "Feedback: " . ($submission->feedback ?: 'No feedback'),
                'publication_date' => date('Y-m-d'),
                'resource_type' => ['id' => 'publication-article'],
                'creators' => [[
                    'person_or_org' => [
                        'type' => 'personal',
                        'name' => $student->lastname . ', ' . $student->firstname,
                        'given_name' => $student->firstname,
                        'family_name' => $student->lastname
                    ]
                ]]
            ],
            'custom_fields' => [
                'moodle:identifier' => 'submission-' . $submission->id,
                'moodle:free_keyword' => [
                    'assignment',
                    'submission',
                    $course->fullname
                ],
                'moodle:language' => 'vi',
                'moodle:documentary_type' => 'text',
                'moodle:format' => strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION)),
                'moodle:location' => '#',
                'moodle:learning_resource_type' => 'assessment',
                'moodle:target_audience' => 'learner',
                'moodle:educational_level' => "bachelor’s degree",
                'moodle:induced_activity' => 'assess',
                'moodle:copyright' => 'yes',
                'moodle:objective' => 'discipline',
                'moodle:taxon_entry' => $course->fullname,
                'moodle:role' => 'author',
                'moodle:entity' => fullname($student),
                'moodle:date' => date('Y-m-d'),
                'moodle:relation' => 'is based on',
                'moodle:metadata_accessibility' => 'public access'
            ]
        ];
        $result = $client->create_record($recordpayload);
        $recordid = $result['data']['id'] ?? null;
        $uploadresult = $client->upload_file(
            $recordid,
            [
                'name' => $file->get_filename(),
                'tmp_name' => $tempfile
            ]
        );

        $publishurl =
            'https://host.docker.internal/api/records/' .
            $recordid .
            '/draft/actions/publish';

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $publishurl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $client->get_token()
            ],
            CURLOPT_POSTFIELDS => '{}'
        ]);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if (!empty($submission->published_to_invenio)) {
            throw new moodle_exception(
                'Already published to Invenio'
            );
        }

        $DB->set_field(
            'local_inveniordm_submissions',
            'published_to_invenio',
            1,
            ['id' => $submissionid]
        );

        $DB->set_field(
            'local_inveniordm_submissions',
            'recordid',
            $recordid,
            ['id' => $submissionid]
        );

        global $USER;

        \log_service::add(
            $USER->id,
            'PUBLISH_SUBMISSION',
            $recordid,
            $assignment->courseid
        );
    }

    public function get_review_submission(int $submissionid): array
    {
        global $DB;
        $submission = $DB->get_record(
            'local_inveniordm_submissions',
            ['id' => $submissionid],
            '*',
            MUST_EXIST
        );

        $assignment = $DB->get_record(
            'local_inveniordm_assignments',
            ['id' => $submission->assignmentid],
            '*',
            MUST_EXIST
        );

        $student = $DB->get_record(
            'user',
            ['id' => $submission->studentid],
            '*',
            MUST_EXIST
        );

        return [
            'submissionid' => $submissionid,
            'assignmentid' => $assignment->id,
            'studentname' => fullname($student),
            'assignmentname' => s($assignment->name),
            'filename' => s($submission->filename),
            'grade' => s($submission->grade ?? ''),
            'feedback' => s($submission->feedback ?? ''),
            'published' => !empty($submission->published_to_invenio),
            'cansave' => empty($submission->published_to_invenio),
            'canpublish' =>
                !empty($submission->grade)
                && !empty($submission->feedback)
                && empty($submission->published_to_invenio),
        ];
    }

    public function save_review(
        int    $submissionid,
        string $grade,
        string $feedback
    ): array
    {

        global $DB;
        $errors = [];

        if ($grade === '') {
            $errors['grade'] = 'Grade is required.';
        }

        if ($feedback === '') {
            $errors['feedback'] = 'Feedback is required.';
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        $DB->set_field(
            'local_inveniordm_submissions',
            'grade',
            $grade,
            ['id' => $submissionid]
        );

        $DB->set_field(
            'local_inveniordm_submissions',
            'feedback',
            $feedback,
            ['id' => $submissionid]
        );

        $submission = $DB->get_record(
            'local_inveniordm_submissions',
            ['id' => $submissionid],
            '*',
            MUST_EXIST
        );

        $assignment = $DB->get_record(
            'local_inveniordm_assignments',
            ['id' => $submission->assignmentid],
            '*',
            MUST_EXIST
        );

        global $USER;

        \log_service::add(
            $USER->id,
            'REVIEW_SUBMISSION',
            null,
            $assignment->courseid
        );

        return [
            'success' => true
        ];
    }

    public function get_view_submissions(int $assignmentid, string $search = '', int $page = 1): array
    {
        global $DB;

        $assignment = $DB->get_record(
            'local_inveniordm_assignments',
            [
                'id' => $assignmentid
            ],
            '*',
            MUST_EXIST
        );

        $context = context_course::instance(
            $assignment->courseid
        );

        $submissions = $DB->get_records(
            'local_inveniordm_submissions',
            [
                'assignmentid' => $assignmentid
            ]
        );

        $studentrole = $DB->get_record(
            'role',
            [
                'shortname' => 'student'
            ],
            '*',
            MUST_EXIST
        );

        $students = get_role_users(
            $studentrole->id,
            $context
        );

        if (!empty($search)) {
            $students = array_filter(
                $students,
                function ($student) use ($search) {
                    return stripos(fullname($student), $search) !== false;
                }
            );
        }

        $submissionmap = [];

        foreach ($submissions as $submission) {
            $submissionmap[$submission->studentid] = $submission;
        }

        $items = [];

        foreach ($students as $student) {
            $submission = $submissionmap[$student->id] ?? null;
            $submitted = !empty($submission);

            $items[] = [
                'studentname' => fullname($student),
                'submitted' => $submitted,
                'status' => $submitted
                    ? 'Submitted'
                    : 'Not Submitted',
                'statusactive' => $submitted,
                'statusoverdue' => !$submitted,
                'filename' => $submitted
                    ? s($submission->filename)
                    : '-',
                'submittedat' => $submitted
                    ? userdate(
                        $submission->timecreated,
                        '%d/%m/%Y %H:%M'
                    )
                    : '-',
                'grade' => $submission->grade ?? '-',
                'feedback' => !empty($submission->feedback)
                    ? s($submission->feedback)
                    : '-',
                'hasactions' => $submitted,
                'downloadurl' => $submitted
                    ? (
                    new moodle_url(
                        '/local/inveniordm/lecturer/download_submission.php',
                        [
                            'submissionid' => $submission->id
                        ]
                    )
                    )->out(false)
                    : '',
                'reviewurl' => $submitted
                    ? (
                    new moodle_url(
                        '/local/inveniordm/lecturer/review_submission.php',
                        [
                            'submissionid' => $submission->id,
                            'page' => $page
                        ]
                    )
                    )->out(false)
                    : ''
            ];
        }

        $resources = $DB->get_records(
            'local_inveniordm_assignment_resources',
            [
                'assignmentid' => $assignmentid
            ]
        );

        $resourceitems = [];

        foreach ($resources as $resource) {
            $resourceitems[] = [
                'title' => $resource->title
            ];
        }

        $totalsubmissions =
            count($submissions);

        $totalstudents =
            count(get_role_users(
                $studentrole->id,
                $context
            ));

        $baseurl = new moodle_url(
            '/local/inveniordm/lecturer/view_submissions.php',
            [
                'assignmentid' => $assignmentid,
                'search' => $search
            ]
        );

        $pagination_service = new pagination_service();
        $pagination = $pagination_service->paginate(
            $items,
            $page,
            self::COURSE_PAGE_SIZE,
            $baseurl
        );

        return [
            'assignmentid' => $assignmentid,
            'assignmentname' => s(
                $assignment->name
            ),
            'instructions' => format_text(
                $assignment->instructions,
                FORMAT_HTML
            ),
            'resources' => $resourceitems,
            'hasresources' => !empty(
            $resourceitems
            ),
            'students' => $pagination['items'],
            'hasstudents' => !empty(
            $items
            ),
            'search' => $search,
            'totalstudents' => $totalstudents,
            'totalsubmissions' => $totalsubmissions,
            'totalnotsubmitted' =>
                $totalstudents -
                $totalsubmissions,
            'courseid' =>
                $assignment->courseid,
            'pagination' => [
                'pages' => $pagination['pages'],
                'previous' => $pagination['previous'],
                'next' => $pagination['next']
            ]
        ];
    }
}