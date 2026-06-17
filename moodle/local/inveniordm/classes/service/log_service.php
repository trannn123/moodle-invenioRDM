<?php

namespace local_inveniordm\service;

defined('MOODLE_INTERNAL') || die();

class log_service {

    public static function add(
        int $userid,
        string $action,
        ?string $resourceid = null,
        ?int $courseid = null
    ): void {

        global $DB;

        $record = new \stdClass();

        $record->userid = $userid;
        $record->action = $action;
        $record->resourceid = $resourceid;
        $record->courseid = $courseid;
        $record->timecreated = time();

        $DB->insert_record(
            'local_inveniordm_logs',
            $record
        );
    }
}