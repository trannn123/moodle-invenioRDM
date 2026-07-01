<?php

namespace local_inveniordm\service;
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
    
}