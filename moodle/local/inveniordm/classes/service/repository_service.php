<?php

namespace local_inveniordm\service;

defined('MOODLE_INTERNAL') || die();

class repository_service
{
    public function get_repository_resources(string $search = ''): array
    {
        $client = new \local_inveniordm\api\invenio_client();
        $result = $client->get_records(
            $search !== '' ? $search : '*'
        );

        $records = $result['hits']['hits'] ?? [];

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

        return [
            'resources' => $resources,
            'hasresources' => !empty($resources),
            'totalresources' => count($resources)
        ];
    }
}