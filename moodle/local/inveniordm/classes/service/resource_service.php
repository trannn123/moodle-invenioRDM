<?php

use local_inveniordm\service\pagination_service;

defined('MOODLE_INTERNAL') || die();

class resource_service
{
    private const COURSE_PAGE_SIZE = 5;

    public function get_course_resources(int $courseid, int $page = 1): array
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

        $baseurl = new moodle_url(
            '/local/inveniordm/student/course_resources.php',
            [
                'courseid' => $courseid
            ]
        );

        $pagination_service = new pagination_service();
        $pagination = $pagination_service->paginate(
            $result,
            $page,
            self::COURSE_PAGE_SIZE,
            $baseurl
        );

        return [
            'resources' => $pagination['items'],
            'hasresources' => !empty($pagination['items']),
            'pagination' => [
                'pages' => $pagination['pages'],
                'previous' => $pagination['previous'],
                'next' => $pagination['next']
            ]
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

    public function search_resources_to_attach(int $courseid, string $search = ''): array
    {
        global $DB;
        $client = new \local_inveniordm\api\invenio_client();
        $records = $client->get_records(
            $search !== '' ? $search : '*'
        );
        $hits = $records['hits']['hits'] ?? [];
        $items = [];

        foreach ($hits as $record) {
            $id = $record['id'];
            $title = $record['metadata']['title'] ?? 'No title';
            $attached = $DB->record_exists(
                'local_inveniordm_course_resources',
                [
                    'courseid' => $courseid,
                    'recordid' => $id
                ]
            );

            $items[] = [
                'id' => $id,
                'title' => s($title),
                'attached' => $attached,
                'notattached' => !$attached,

                'viewurl' => (
                new moodle_url(
                    '/local/inveniordm/resource/view.php',
                    [
                        'id' => $id,
                        'returnurl' => qualified_me()
                    ]
                )
                )->out(false),

                'attachurl' => (
                new moodle_url(
                    '/local/inveniordm/lecturer/search_resources_to_attach.php',
                    [
                        'courseid' => $courseid,
                        'attach' => $id
                    ]
                )
                )->out(false)
            ];
        }

        return [
            'resources' => $items,
            'hasresources' => !empty($items),
            'courseid' => $courseid
        ];
    }

    public function attach_resource(int $courseid, string $recordid, int $userid): void
    {
        global $DB;
        $client = new \local_inveniordm\api\invenio_client();
        $record = $client->get_record($recordid);

        if (empty($record)) {
            throw new moodle_exception('Invalid record');
        }

        $title = $record['metadata']['title'] ?? 'Unknown';
        $files = $record['files']['entries'] ?? [];

        if (empty($files)) {
            throw new moodle_exception('No file in record');
        }

        if ($DB->record_exists(
            'local_inveniordm_course_resources',
            [
                'courseid' => $courseid,
                'recordid' => $recordid
            ]
        )) {
            throw new moodle_exception('Resource already attached');
        }

        $DB->insert_record(
            'local_inveniordm_course_resources',
            [
                'courseid' => $courseid,
                'recordid' => $recordid,
                'title' => $title,
                'timecreated' => time()
            ]
        );

        \log_service::add(
            $userid,
            'ATTACH_RESOURCE',
            $recordid,
            $courseid
        );
    }
}