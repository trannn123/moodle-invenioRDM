<?php

defined('MOODLE_INTERNAL') || die();

class submission_service
{
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
            throw new \moodle_exception('deadlinepassed');
        }

        if (!$file || empty($file['name'])) {
            throw new \moodle_exception('nofile');
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

        \local_inveniordm\service\log_service::add(
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
            'http://ctu-it-rdm-web-api-1:5000/api/records/' .
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
    }
}