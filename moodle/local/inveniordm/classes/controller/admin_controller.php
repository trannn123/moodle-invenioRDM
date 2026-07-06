<?php

use local_inveniordm\service\admin_log_service;
use local_inveniordm\service\analytics_service;
use local_inveniordm\service\monitoring_service;
use local_inveniordm\service\repository_service;

defined('MOODLE_INTERNAL') || die();

class admin_controller
{
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
        $page = optional_param('page', 1, PARAM_INT);

        $service = new analytics_service();

        $activitycounts = $service->get_activity_counts($range);
        $breakdown = $service->get_activity_breakdown($range);
        $recentactivities = $service->get_recent_activity_data($range, $page);

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
            'recentactivities' => $recentactivities['items'],
            'pagination' => $recentactivities['pagination'],
            'activitydata' => $breakdown['activityData'],
            'totalactivities' => $breakdown['totalActivities'],
            'conicgradient' => $conicgradient
        ];
    }

    public function get_monitoring_context(): array
    {
        $service = new monitoring_service();
        $database = $service->check_database_status();
        $api = $service->check_api_status();

        $healthscore = $service->calculate_health_score(
            $database['status'],
            $api['status'],
            $api['latency'],
            $api['result']
        );

        $dbclass = $database['status']
            ? 'success'
            : 'danger';

        $dbtext = $database['status']
            ? 'Online'
            : 'Offline';

        $apiclass = $api['status']
            ? 'success'
            : 'danger';

        $apitext = $api['status']
            ? 'Online'
            : 'Offline';

        $healthclass = 'success';

        if ($healthscore < 50) {
            $healthclass = 'danger';
        } elseif ($healthscore < 80) {
            $healthclass = 'warning';
        }

        return [
            'database' => [
                'message' => $database['message'],
                'badgeclass' => $dbclass,
                'badgetext' => $dbtext
            ],

            'api' => [
                'message' => $api['message'],
                'httpcode' => $api['httpcode'],
                'latency' => $api['latency'],
                'badgeclass' => $apiclass,
                'badgetext' => $apitext
            ],

            'health' => [
                'score' => $healthscore,
                'badgeclass' => $healthclass
            ],

            'systeminfo' => $service->get_system_information(),

            'backurl' => (
            new moodle_url('/local/inveniordm/index.php')
            )->out(false)
        ];
    }

    public function get_repository_context(): array
    {
        $search = optional_param('search', '', PARAM_TEXT);

        $service = new repository_service();

        $data = $service->get_repository_resources($search);

        return [
            'search' => $search,
            'resources' => $data['resources'],
            'hasresources' => $data['hasresources'],
            'totalresources' => $data['totalresources'],
            'backurl' => (
            new moodle_url('/local/inveniordm/index.php')
            )->out(false)
        ];
    }

    public function get_logs_context(): array
    {
        $search = optional_param('search', '', PARAM_TEXT);
        $action = optional_param('action', '', PARAM_ALPHANUMEXT);
        $userid = optional_param('userid', 0, PARAM_INT);
        $courseid = optional_param('courseid', 0, PARAM_INT);
        $range = optional_param('range', '30days', PARAM_ALPHANUMEXT);

        $filters = [
            'search' => $search,
            'action' => $action,
            'userid' => $userid,
            'courseid' => $courseid,
            'range' => $range,
        ];

        $service = new admin_log_service();
        $logs = $service->get_logs($filters);

        return [
            'filters' => [
                'search' => $search,
                'action' => $action,
                'userid' => $userid ?: '',
                'courseid' => $courseid ?: '',
            ],

            'actions' => [
                [
                    'value' => '',
                    'label' => 'All actions',
                    'selected' => $action === ''
                ],
                [
                    'value' => 'VIEW_RESOURCE',
                    'label' => 'View Resource',
                    'selected' => $action === 'VIEW_RESOURCE'
                ],
                [
                    'value' => 'DOWNLOAD_RESOURCE',
                    'label' => 'Download Resource',
                    'selected' => $action === 'DOWNLOAD_RESOURCE'
                ],
                [
                    'value' => 'UPLOAD_RESOURCE',
                    'label' => 'Upload Resource',
                    'selected' => $action === 'UPLOAD_RESOURCE'
                ],
                [
                    'value' => 'ATTACH_RESOURCE',
                    'label' => 'Attach Resource',
                    'selected' => $action === 'ATTACH_RESOURCE'
                ],
                [
                    'value' => 'SUBMIT_ASSIGNMENT',
                    'label' => 'Submit Assignment',
                    'selected' => $action === 'SUBMIT_ASSIGNMENT'
                ],
                [
                    'value' => 'SEARCH_RESOURCE',
                    'label' => 'Search Resource',
                    'selected' => $action === 'SEARCH_RESOURCE'
                ]
            ],

            'ranges' => [
                [
                    'value' => '7days',
                    'label' => 'Last 7 days',
                    'selected' => $range === '7days'
                ],
                [
                    'value' => '30days',
                    'label' => 'Last 30 days',
                    'selected' => $range === '30days'
                ],
                [
                    'value' => '90days',
                    'label' => 'Last 90 days',
                    'selected' => $range === '90days'
                ],
                [
                    'value' => 'all',
                    'label' => 'All',
                    'selected' => $range === 'all'
                ]
            ],

            'logs' => $logs,

            'haslogs' => !empty($logs),

            'exporturl' => (new \moodle_url(
                '/local/inveniordm/admin/export_logs.php',
                $filters
            ))->out(false),

            'backurl' => (new \moodle_url(
                '/local/inveniordm/index.php'
            ))->out(false),
        ];
    }
}