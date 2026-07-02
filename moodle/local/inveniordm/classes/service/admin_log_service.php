<?php

namespace local_inveniordm\service;

defined('MOODLE_INTERNAL') || die();

class admin_log_service
{

    public function get_logs(array $filters = []): array
    {
        global $DB;

        $actionlabels = [
            'VIEW_RESOURCE' => 'View Resource',
            'DOWNLOAD_RESOURCE' => 'Download Resource',
            'UPLOAD_RESOURCE' => 'Upload Resource',
            'ATTACH_RESOURCE' => 'Attach Resource',
            'SUBMIT_ASSIGNMENT' => 'Submit Assignment',
            'SEARCH_RESOURCE' => 'Search Resource',
        ];

        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(
                u.firstname LIKE :search
                OR u.lastname LIKE :search
                OR l.action LIKE :search
                OR l.resourceid LIKE :search
            )";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['action'])) {
            $where[] = "l.action = :action";
            $params['action'] = $filters['action'];
        }

        if (!empty($filters['userid'])) {
            $where[] = "l.userid = :userid";
            $params['userid'] = $filters['userid'];
        }

        if (!empty($filters['courseid'])) {
            $where[] = "l.courseid = :courseid";
            $params['courseid'] = $filters['courseid'];
        }

        if (!empty($filters['range'])) {

            switch ($filters['range']) {

                case '7days':
                    $time = time() - 7 * DAYSECS;
                    break;

                case '30days':
                    $time = time() - 30 * DAYSECS;
                    break;

                case '90days':
                    $time = time() - 90 * DAYSECS;
                    break;

                default:
                    $time = 0;
            }

            if ($time > 0) {
                $where[] = "l.timecreated >= :time";
                $params['time'] = $time;
            }
        }

        $sql = "
            SELECT
                l.*,
                u.firstname,
                u.lastname,
                u.firstnamephonetic,
                u.lastnamephonetic,
                u.middlename,
                u.alternatename
            FROM {local_inveniordm_logs} l
            JOIN {user} u
            ON u.id = l.userid
            ";

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY l.timecreated DESC";

        $logs = $DB->get_records_sql($sql, $params);

        foreach ($logs as $log) {
            $log->username = fullname($log);
            $log->time = userdate($log->timecreated);
            $log->actionlabel = $actionlabels[$log->action] ?? $log->action;
        }

        return array_values($logs);
    }
}