<?php

defined('MOODLE_INTERNAL') || die();

class admin_controller
{
    public function check_database_status()
    {
        global $DB;
        try {
            $DB->count_records('user');
            return [
                'status' => true,
                'message' => 'Connected'
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function check_api_status()
    {
        $start = microtime(true);
        try {
            $client = new \local_inveniordm\api\invenio_client();
            $result = $client->get_records();
            $latency = round((microtime(true) - $start) * 1000);
            if (is_array($result) && empty($result['error'])) {
                return [
                    'status' => true,
                    'message' => 'Connected',
                    'httpcode' => 200,
                    'latency' => $latency,
                    'result' => $result
                ];
            }
            return [
                'status' => false,
                'message' => 'API Error',
                'httpcode' => $result['status'] ?? 500,
                'latency' => $latency,
                'result' => $result
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'httpcode' => 500,
                'latency' => round((microtime(true) - $start) * 1000),
                'result' => ['error' => true]
            ];
        }
    }

    public function calculate_health_score(bool $dbstatus, bool $apistatus, int $latency, array $result): int
    {
        $healthscore = 0;
        if ($dbstatus) {
            $healthscore += 25;
        }
        if ($apistatus) {
            $healthscore += 25;
        }
        if ($latency < 1000) {
            $healthscore += 25;
        } elseif ($latency < 2000) {
            $healthscore += 15;
        }
        if (empty($result['error'])) {
            $healthscore += 25;
        }
        return $healthscore;
    }

    public function get_logs_for_export()
    {
        global $DB;
        return $DB->get_records(
            'local_inveniordm_logs',
            null,
            'timecreated DESC'
        );
    }

    public function get_activity_counts($range = '30days')
    {
        global $DB;

        [$condition, $params] = $this->get_time_condition($range);

        $actions = [
            'UPLOAD_RESOURCE',
            'VIEW_RESOURCE',
            'DOWNLOAD_RESOURCE',
            'SEARCH_RESOURCE',
            'ATTACH_RESOURCE',
            'SUBMIT_ASSIGNMENT'
        ];

        $result = [];

        foreach ($actions as $action) {
            $sql = "action = :action AND $condition";
            $mergedparams = array_merge(
                ['action' => $action],
                $params
            );
            $count = $DB->count_records_select(
                'local_inveniordm_logs',
                $sql,
                $mergedparams
            );
            $key = match ($action) {
                'UPLOAD_RESOURCE' => 'uploads',
                'VIEW_RESOURCE' => 'views',
                'DOWNLOAD_RESOURCE' => 'downloads',
                'SEARCH_RESOURCE' => 'searches',
                'ATTACH_RESOURCE' => 'attachments',
                'SUBMIT_ASSIGNMENT' => 'submissions',
                default => strtolower($action)
            };

            $result[$key] = $count;
        }
        return $result;
    }

    private function get_time_condition($range)
    {
        switch ($range) {
            case 'today':
                return [
                    "timecreated >= :time",
                    ['time' => strtotime('today 00:00:00')]
                ];
            case '7days':
                return [
                    "timecreated >= :time",
                    ['time' => time() - 7 * DAYSECS]
                ];
            case '30days':
                return [
                    "timecreated >= :time",
                    ['time' => time() - 30 * DAYSECS]
                ];
            default:
                return ["1=1", []];
        }
    }

    public function get_recent_activity_data()
    {
        global $DB;
        $logs = $DB->get_records_sql(
            "SELECT * FROM {local_inveniordm_logs}
                    ORDER BY timecreated DESC
                    LIMIT 10");

        $userids = [];

        $actionlabels = [
            'UPLOAD_RESOURCE' => 'Upload Resource',
            'VIEW_RESOURCE' => 'View Resource',
            'DOWNLOAD_RESOURCE' => 'Download Resource',
            'SEARCH_RESOURCE' => 'Search Resource',
            'ATTACH_RESOURCE' => 'Attach Resource',
            'SUBMIT_ASSIGNMENT' => 'Submit Assignment'
        ];

        foreach ($logs as $log) {
            if (!empty($log->userid)) {
                $userids[$log->userid] = $log->userid;
            }
        }

        $users = [];

        if ($userids) {
            list($sqlin, $params) = $DB->get_in_or_equal($userids);
            $users = $DB->get_records_select(
                'user',
                "id $sqlin",
                $params,
                '',
                'id, firstname, lastname, firstnamephonetic, lastnamephonetic, middlename, alternatename'
            );
        }

        $courseids = [];

        foreach ($logs as $log) {
            if (!empty($log->courseid)) {
                $courseids[$log->courseid] = $log->courseid;
            }
        }

        $courses = [];

        if ($courseids) {
            list($sqlin, $params) = $DB->get_in_or_equal($courseids);
            $courses = $DB->get_records_select(
                'course',
                "id $sqlin",
                $params,
                '',
                'id, fullname'
            );
        }

        $resourceids = [];

        foreach ($logs as $log) {
            if (!empty($log->resourceid)) {
                $resourceids[$log->resourceid] = $log->resourceid;
            }
        }

        $resourcerecords = [];

        if ($resourceids) {
            list($sqlin, $params) = $DB->get_in_or_equal($resourceids);
            $resourcerecords = $DB->get_records_select(
                'local_inveniordm_course_resources',
                "recordid $sqlin",
                $params,
                '',
                'recordid,title'
            );
        }

        $data = [];

        foreach ($logs as $log) {
            $data[] = [
                'log' => $log,
                'username' => isset($users[$log->userid])
                    ? fullname($users[$log->userid])
                    : '-',
                'coursename' => isset($courses[$log->courseid])
                    ? $courses[$log->courseid]->fullname
                    : '-',
                'resourcename' => (
                    !empty($log->resourceid)
                    && isset($resourcerecords[$log->resourceid])
                )
                    ? $resourcerecords[$log->resourceid]->title
                    : '-',
                'action' => $actionlabels[$log->action] ?? $log->action
            ];
        }
        return $data;
    }

    public function get_top_viewed_resources($range = '30days')
    {
        global $DB;
        [$condition, $params] = $this->get_time_condition($range);

        $sql = "
            SELECT resourceid, COUNT(*) AS totalviews
            FROM {local_inveniordm_logs}
            WHERE action = 'VIEW_RESOURCE'
            AND $condition
            AND resourceid <> ''
            GROUP BY resourceid
            ORDER BY totalviews DESC
            LIMIT 5
        ";

        $topresources = $DB->get_records_sql($sql, $params);

        $resourceids = [];
        foreach ($topresources as $resource) {
            if (!empty($resource->resourceid)) {
                $resourceids[$resource->resourceid] = $resource->resourceid;
            }
        }

        $resourcerecords = [];
        if ($resourceids) {
            list($sqlin, $params) = $DB->get_in_or_equal($resourceids);
            $resourcerecords = $DB->get_records_select(
                'local_inveniordm_course_resources',
                "recordid $sqlin",
                $params,
                '',
                'recordid,title'
            );
        }

        $data = [];
        foreach ($topresources as $resource) {
            $data[] = [
                'resourceid' => $resource->resourceid,
                'title' => isset($resourcerecords[$resource->resourceid])
                    ? $resourcerecords[$resource->resourceid]->title
                    : $resource->resourceid,
                'totalviews' => $resource->totalviews
            ];
        }
        return $data;
    }

    public function get_top_downloaded_resources($range = '30days')
    {
        global $DB;

        [$condition, $params] = $this->get_time_condition($range);

        $sql = "
            SELECT resourceid,
            COUNT(*) AS totaldownloads
            FROM {local_inveniordm_logs}
            WHERE action = 'DOWNLOAD_RESOURCE'
            AND $condition
            AND resourceid IS NOT NULL
            AND resourceid <> ''
            GROUP BY resourceid
            ORDER BY totaldownloads DESC LIMIT 5
        ";

        $topresources = $DB->get_records_sql($sql, $params);

        $resourceids = [];
        foreach ($topresources as $resource) {
            if (!empty($resource->resourceid)) {
                $resourceids[$resource->resourceid] = $resource->resourceid;
            }
        }

        $resourcerecords = [];
        if ($resourceids) {
            list($sqlin, $params) = $DB->get_in_or_equal($resourceids);
            $resourcerecords = $DB->get_records_select(
                'local_inveniordm_course_resources',
                "recordid $sqlin",
                $params,
                '',
                'recordid,title'
            );
        }

        $data = [];
        foreach ($topresources as $resource) {
            $data[] = [
                'resourceid' => $resource->resourceid,
                'title' => isset($resourcerecords[$resource->resourceid])
                    ? $resourcerecords[$resource->resourceid]->title
                    : $resource->resourceid,
                'totaldownloads' => $resource->totaldownloads
            ];
        }
        return $data;
    }

    public function get_top_active_users($range = '30days')
    {
        global $DB;
        [$condition, $params] = $this->get_time_condition($range);

        $sql = "
            SELECT userid, COUNT(*) AS activitycount
            FROM {local_inveniordm_logs}
            WHERE $condition
            GROUP BY userid
            ORDER BY activitycount DESC
            LIMIT 5
        ";

        $topusers = $DB->get_records_sql($sql, $params);

        $userids = [];

        foreach ($topusers as $user) {
            $userids[$user->userid] = $user->userid;
        }

        $users = [];

        if ($userids) {
            list($sqlin, $params) = $DB->get_in_or_equal($userids);
            $users = $DB->get_records_select(
                'user',
                "id $sqlin",
                $params,
                '',
                'id, firstname, lastname, firstnamephonetic,
         lastnamephonetic, middlename, alternatename'
            );
        }

        $data = [];

        foreach ($topusers as $user) {
            $data[] = [
                'username' => isset($users[$user->userid])
                    ? fullname($users[$user->userid])
                    : '-',

                'activitycount' => $user->activitycount
            ];
        }
        return $data;
    }

    public function get_top_courses($range = '30days')
    {
        global $DB;

        [$condition, $params] = $this->get_time_condition($range);

        $sql = "
            SELECT courseid, COUNT(*) AS totalactivities
            FROM {local_inveniordm_logs}
            WHERE $condition
            GROUP BY courseid
            ORDER BY totalactivities DESC
            LIMIT 5
        ";

        $topcourses = $DB->get_records_sql($sql, $params);

        $courseids = [];

        foreach ($topcourses as $course) {
            $courseids[$course->courseid] = $course->courseid;
        }

        $courses = [];

        if ($courseids) {
            list($sqlin, $params) = $DB->get_in_or_equal($courseids);
            $courses = $DB->get_records_select(
                'course',
                "id $sqlin",
                $params,
                '',
                'id, fullname'
            );
        }

        $data = [];

        foreach ($topcourses as $course) {
            $data[] = [
                'coursename' => isset($courses[$course->courseid])
                    ? $courses[$course->courseid]->fullname
                    : '-',
                'totalactivities' => $course->totalactivities
            ];
        }
        return $data;
    }

    public function get_activity_breakdown($range = '30days')
    {
        global $DB;

        [$condition, $params] = $this->get_time_condition($range);

        $sql = "
            SELECT action, COUNT(*) AS total
            FROM {local_inveniordm_logs}
            WHERE $condition
            GROUP BY action
            ORDER BY total DESC
        ";

        $activitystats = $DB->get_records_sql($sql, $params);

        $actionlabels = [
            'UPLOAD_RESOURCE' => 'Upload Resource',
            'VIEW_RESOURCE' => 'View Resource',
            'DOWNLOAD_RESOURCE' => 'Download Resource',
            'SEARCH_RESOURCE' => 'Search Resource',
            'ATTACH_RESOURCE' => 'Attach Resource',
            'SUBMIT_ASSIGNMENT' => 'Submit Assignment'
        ];

        $colorMap = [
            'UPLOAD_RESOURCE' => '#3b7bc9',
            'VIEW_RESOURCE' => '#35a77c',
            'DOWNLOAD_RESOURCE' => '#d97747',
            'SEARCH_RESOURCE' => '#b48ad9',
            'ATTACH_RESOURCE' => '#d45a7a',
            'SUBMIT_ASSIGNMENT' => '#4e9fcf'
        ];

        $totalActivities = 0;
        $activityData = [];
        foreach ($activitystats as $item) {
            $totalActivities += (int)$item->total;
            $activityData[] = [
                'label' => $actionlabels[$item->action] ?? $item->action,
                'value' => (int)$item->total,
                'color' => $colorMap[$item->action] ?? '#6c757d',
                'action' => $item->action
            ];
        }

        $pieData = [];
        $currentAngle = 0;
        foreach ($activityData as $item) {
            $percentage = $totalActivities > 0 ? ($item['value'] / $totalActivities) * 100 : 0;
            $angle = $totalActivities > 0 ? ($item['value'] / $totalActivities) * 360 : 0;
            $pieData[] = [
                'label' => $item['label'],
                'value' => $item['value'],
                'color' => $item['color'],
                'percentage' => $percentage,
                'angle' => $angle,
                'startAngle' => $currentAngle,
                'endAngle' => $currentAngle + $angle
            ];
            $currentAngle += $angle;
        }
        return [
            'totalActivities' => $totalActivities,
            'activityData' => $activityData,
            'pieData' => $pieData
        ];
    }
}