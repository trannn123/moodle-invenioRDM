<?php


defined('MOODLE_INTERNAL') || die();

class analytics_service
{
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
            $percentage = $totalActivities > 0
                ? ($item['value'] / $totalActivities) * 100
                : 0;

            $angle = $totalActivities > 0
                ? ($item['value'] / $totalActivities) * 360
                : 0;

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

        $conicGradient = '';
        $startAngle = 0;

        foreach ($pieData as $index => $item) {
            if (!empty($item['angle'])) {

                $conicGradient .=
                    $item['color'] . ' ' .
                    $startAngle . 'deg ' .
                    ($startAngle + $item['angle']) . 'deg';

                if ($index < count($pieData) - 1) {
                    $conicGradient .= ', ';
                }

                $startAngle += $item['angle'];
            }
        }

        if ($conicGradient === '') {
            $conicGradient = '#e9ecef 0deg 360deg';
        }

        return [
            'totalActivities' => $totalActivities,
            'activityData' => $activityData,
            'pieData' => $pieData,
            'conicGradient' => $conicGradient
        ];
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
                'timecreated' => userdate($log->timecreated),
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
            $sql = "
                SELECT recordid, MAX(title) AS title
                FROM {local_inveniordm_course_resources}
                WHERE recordid $sqlin
                GROUP BY recordid
            ";
            $resourcerecords = $DB->get_records_sql($sql, $params);
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
}