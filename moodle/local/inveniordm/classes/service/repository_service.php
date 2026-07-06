<?php

namespace local_inveniordm\service;

defined('MOODLE_INTERNAL') || die();

class repository_service
{
    private const COURSE_PAGE_SIZE = 4;

    public function get_repository_resources(string $search = '', int $page = 1): array
    {
        $pagesize = self::COURSE_PAGE_SIZE;

        $client = new \local_inveniordm\api\invenio_client();
        $result = $client->get_records(
            $search !== '' ? $search : '*'
        );

        $records = $result['hits']['hits'] ?? [];
        $total = $result['hits']['total'] ?? count($records);

        $resources = [];

        foreach ($records as $record) {

            $resources[] = [
                'id' => $record['id'] ?? '',
                'title' => $record['metadata']['title'] ?? 'No title',
                'status' => ucfirst($record['status'] ?? 'Unknown'),
                'date' => $record['metadata']['publication_date'] ?? '-',
                'filecount' => $record['files']['count'] ?? 0,
                'viewurl' => (
                new \moodle_url(
                    '/local/inveniordm/resource/view.php',
                    [
                        'id' => $record['id'],
                        'returnurl' => qualified_me()
                    ]
                )
                )->out(false)
            ];
        }

        $baseurl = new \moodle_url(
            '/local/inveniordm/admin/repository.php',
            [
                'search' => $search
            ]
        );

        $pagination_service = new pagination_service();
        $pagination = $pagination_service->paginate(
            $resources,
            $page,
            self::COURSE_PAGE_SIZE,
            $baseurl
        );

        return [
            'resources' => $pagination['items'],
            'hasresources' => !empty($pagination['items']),
            'totalresources' => count($resources),
            'pagination' => [
                'pages' => $pagination['pages'],
                'previous' => $pagination['previous'],
                'next' => $pagination['next']
            ]
        ];
    }
}