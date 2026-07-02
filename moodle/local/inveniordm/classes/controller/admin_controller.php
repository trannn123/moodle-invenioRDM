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

    public function get_analytics_context(): array
    {
        $range = optional_param('range', '30days', PARAM_ALPHANUMEXT);

        $service = new analytics_service();

        $activitycounts = $service->get_activity_counts($range);

        $breakdown = $service->get_activity_breakdown($range);

        $conicgradient = '';

        foreach ($breakdown['pieData'] as $index => $item) {
            if (!empty($item['angle'])) {
                $conicgradient .=
                    $item['color'] . ' ' .
                    $item['startAngle'] . 'deg ' .
                    $item['endAngle'] . 'deg';

                if ($index < count($breakdown['pieData']) - 1) {
                    $conicgradient .= ', ';
                }
            }
        }

        return [
            'backurl' => (
            new moodle_url('/local/inveniordm/index.php')
            )->out(false),

            'exporturl' => (
            new moodle_url('/local/inveniordm/admin/export_logs.php')
            )->out(false),

            'todayselected' => ($range === 'today'),
            'weekselected' => ($range === '7days'),
            'monthselected' => ($range === '30days'),

            'stats' => [
                [
                    'icon' => 'upload',
                    'label' => 'Uploads',
                    'value' => $activitycounts['uploads']
                ],
                [
                    'icon' => 'eye',
                    'label' => 'Views',
                    'value' => $activitycounts['views']
                ],
                [
                    'icon' => 'download',
                    'label' => 'Downloads',
                    'value' => $activitycounts['downloads']
                ],
                [
                    'icon' => 'search',
                    'label' => 'Searches',
                    'value' => $activitycounts['searches']
                ],
                [
                    'icon' => 'paperclip',
                    'label' => 'Attachments',
                    'value' => $activitycounts['attachments']
                ],
                [
                    'icon' => 'check-circle',
                    'label' => 'Submissions',
                    'value' => $activitycounts['submissions']
                ]
            ],

            'topresources' => $service->get_top_viewed_resources($range),
            'topdownloads' => $service->get_top_downloaded_resources($range),
            'topusers' => $service->get_top_active_users($range),
            'topcourses' => $service->get_top_courses($range),
            'recentactivities' => $service->get_recent_activity_data(),

            'activitydata' => $breakdown['activityData'],
            'totalactivities' => $breakdown['totalActivities'],
            'conicgradient' => $conicgradient
        ];
    }
}