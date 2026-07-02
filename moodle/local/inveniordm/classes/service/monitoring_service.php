<?php

namespace local_inveniordm\service;

defined('MOODLE_INTERNAL') || die();

class monitoring_service
{
    public function check_database_status(): array
    {
        global $DB;

        try {
            $DB->count_records('user');

            return [
                'status' => true,
                'message' => 'Connected'
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function check_api_status(): array
    {
        $start = microtime(true);

        try {
            $client = new \local_inveniordm\api\invenio_client();

            $result = $client->get_records();

            $latency = round(
                (microtime(true) - $start) * 1000
            );

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

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'httpcode' => 500,
                'latency' => round(
                    (microtime(true) - $start) * 1000
                ),
                'result' => [
                    'error' => true
                ]
            ];
        }
    }

    public function calculate_health_score(bool $dbstatus, bool $apistatus, int $latency, array $result): int
    {
        $score = 0;
        if ($dbstatus) {
            $score += 25;
        }
        if ($apistatus) {
            $score += 25;
        }
        if ($latency < 1000) {
            $score += 25;
        } elseif ($latency < 2000) {
            $score += 15;
        }
        if (empty($result['error'])) {
            $score += 25;
        }
        return $score;
    }

    public function get_system_information(): array
    {
        global $CFG;

        return [
            'moodleversion' => $CFG->release,
            'phpversion' => PHP_VERSION,
            'serveros' => PHP_OS,
            'memorylimit' => ini_get('memory_limit'),
            'timezone' => date_default_timezone_get(),
            'servertime' => userdate(time())
        ];
    }
}