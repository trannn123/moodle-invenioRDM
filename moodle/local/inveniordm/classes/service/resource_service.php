<?php

defined('MOODLE_INTERNAL') || die();

class resource_service
{
    public function get_course_resources(int $courseid): array
    {
        global $DB;
        $resources = $DB->get_records(
            'local_inveniordm_course_resources',
            ['courseid' => $courseid],
            'timecreated DESC'
        );

        $result = [];

        foreach ($resources as $res) {
            $result[] = [
                'id' => $res->id,
                'title' => s($res->title),
                'recordid' => s($res->recordid),
                'timecreated' => userdate($res->timecreated),
                'viewurl' => (new \moodle_url(
                    '/local/inveniordm/resource/view.php',
                    [
                        'id' => $res->recordid,
                        'returnurl' => qualified_me()
                    ]
                ))->out(false),
            ];
        }

        return [
            'resources' => $result,
            'hasresources' => !empty($result),
        ];
    }

    public function get_lecturer_course_resources(int $courseid): array
    {
        global $DB;
        $resources = $DB->get_records(
            'local_inveniordm_course_resources',
            ['courseid' => $courseid],
            'timecreated DESC'
        );
        $result = [];

        foreach ($resources as $res) {
            $result[] = [
                'title' => s($res->title),
                'recordid' => s($res->recordid),
                'timecreated' => userdate($res->timecreated, '%d/%m/%Y'),
                'viewurl' => (new moodle_url(
                    '/local/inveniordm/resource/view.php',
                    [
                        'id' => $res->recordid,
                        'returnurl' => qualified_me()
                    ]
                ))->out(false),
                'downloadurl' => (new moodle_url(
                    '/local/inveniordm/resource/download.php',
                    ['recordid' => $res->recordid]
                ))->out(false),
            ];
        }

        return [
            'resources' => $result,
            'hasresources' => !empty($result),
            'totalresources' => count($result),
        ];
    }

    public function get_lecturer_my_resources(): array
    {
        $client = new \local_inveniordm\api\invenio_client();
        $result = $client->get_records();
        $records = $result['hits']['hits'] ?? [];
        $items = [];

        foreach ($records as $record) {
            $items[] = [
                'id' => $record['id'] ?? '',
                'title' => $record['metadata']['title'] ?? 'No title',
                'date' => $record['metadata']['publication_date'] ?? '',
                'status' => $record['status'] ?? '',
                'filecount' => $record['files']['count'] ?? 0,
                'viewurl' => (new moodle_url(
                    '/local/inveniordm/resource/view.php',
                    [
                        'id' => $record['id'],
                        'returnurl' => qualified_me()
                    ]
                ))->out(false),
            ];
        }

        return [
            'resources' => $items,
            'totalresources' => count($items),
            'hasresources' => !empty($items),
        ];
    }
}