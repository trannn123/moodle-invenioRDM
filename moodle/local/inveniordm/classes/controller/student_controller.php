<?php

namespace local_inveniordm\controller;
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../api/invenio_client.php');
use local_inveniordm\api\invenio_client;

class student_controller {
    public function search() {
        global $OUTPUT;
        $client = new invenio_client();
        $response = $client->get_records();

        $query = optional_param('q', '', PARAM_TEXT);
        $format =
            optional_param(
                'format',
                '',
                PARAM_TEXT
            );
        $discipline =
            optional_param(
                'discipline',
                '',
                PARAM_TEXT
            );
        $level =
            optional_param(
                'level',
                '',
                PARAM_TEXT
            );

        // Gọi Invenio API, vd: GET /api/records?q=vlan
        $response = $client->get_records($query);
        $records = [];
        $hits = $response['hits']['hits'] ?? [];
        foreach ($hits as $record) {
            $title =
                $record['metadata']['title'] ?? '';
            $description =
                $record['metadata']['description'] ?? '';
            $subject =
                $record['custom_fields']['moodle:taxon_entry'] ?? '';
            $matchquery =
                $query === '' ||
                stripos($title, $query) !== false ||
                stripos($description, $query) !== false ||
                stripos($subject, $query) !== false;
            $matchformat =
                $format === '' ||
                (
                    ($record['custom_fields']['moodle:format'] ?? '')
                    === $format
                );
            $matchdiscipline =
                $discipline === '' ||
                (
                    ($record['custom_fields']['moodle:taxon_entry'] ?? '')
                    === $discipline
                );
            $matchlevel =
                $level === '' ||
                (
                    ($record['custom_fields']['moodle:educational_level'] ?? '')
                    === $level
                );
            if (
                $matchquery &&
                $matchformat &&
                $matchdiscipline &&
                $matchlevel
            ){
                $records[] = [
                    'id' => $record['id'] ?? '',
                    'title' => $title,
                    'author' =>
                        $record['custom_fields']['moodle:entity']
                        ?? 'Unknown',
                    'format' =>
                        $record['custom_fields']['moodle:format']
                        ?? 'Unknown',
                    'discipline' =>
                        $record['custom_fields']['moodle:taxon_entry']
                        ?? 'Unknown',
                    'educationallevel' =>
                        $record['custom_fields']['moodle:educational_level']
                        ?? 'Unknown',
                    'learningresourcetype' =>
                        $record['custom_fields']['moodle:learning_resource_type']
                        ?? 'Unknown'
                ];
            }
        }

        $context = [
            'query' => $query,
            'records' => $records,
            'selected_pdf' =>
                $format === 'pdf',
            'selected_video' =>
                $format === 'video',
            'selected_doc' =>
                $format === 'doc',
            'selected_ai' =>
                $discipline === 'Artificial Intelligence',
            'selected_networking' =>
                $discipline === 'Computer Networking',
            'selected_bachelor' =>
                $level === 'bachelor',
            'selected_master' =>
                $level === 'master'
        ];

        return $OUTPUT->render_from_template(
            'local_inveniordm/student/search',
            $context
        );
    }

    public function view($id) {
        global $OUTPUT;
        $client = new invenio_client();

        // Lấy record, vd: GET /api/records?q=vlan
        $record = $client->get_record($id);

        if (!$record || !isset($record['metadata'])) {
            return $OUTPUT->notification(
                'Record not found or API error',
                'error'
            );
        }

        $metadata = $record['metadata'] ?? [];
        $title =
            $metadata['title']
            ?? 'No title';
        $author = 'Unknown';

        if (
            !empty($metadata['creators']) &&
            isset(
                $metadata['creators'][0]['person_or_org']['name']
            )
        ) {
            $author = $metadata['creators'][0]['person_or_org']['name'];
        }

        $publicationdate =
            $metadata['publication_date']
            ?? 'Not specified';
        $resourcetype = 'Unknown';

        if (isset($metadata['resource_type']['title']['en'])) {
            $resourcetype = $metadata['resource_type']['title']['en'];

        } else if (isset($metadata['resource_type']['title'])) {
            $resourcetype = $metadata['resource_type']['title'];
        }
        $publisher =
            $metadata['publisher']
            ?? 'Not specified';
        $description =
            $metadata['description']
            ?? 'No description';
        $customfields =
            $record['custom_fields'] ?? [];
        $format =
            $customfields['moodle:format']
            ?? 'Unknown';
        $documenttype =
            $customfields['moodle:documentary_type']
            ?? 'Unknown';
        $educationallevel =
            $customfields['moodle:educational_level']
            ?? 'Unknown';
        $targetaudience =
            $customfields['moodle:target_audience']
            ?? 'Unknown';
        $discipline =
            $customfields['moodle:taxon_entry']
            ?? 'Unknown';
        $copyright =
            $customfields['moodle:copyright']
            ?? 'Unknown';
        $relation =
            $customfields['moodle:relation']
            ?? 'Not specified';
        $learningresourcetype =
            $customfields['moodle:learning_resource_type']
            ?? 'Unknown';
        $language =
            $customfields['moodle:language']
            ?? 'Unknown';

        $keywords = [];

        if (!empty($customfields['moodle:free_keyword'])) {
            foreach (
                $customfields['moodle:free_keyword']
                as $keyword
            ) {
                $keywords[] = [
                    'name' => $keyword
                ];
            }
        }

        $context = [
            'title' => $title,
            'author' => $author,
            'publicationdate' => $publicationdate,
            'resourcetype' => $resourcetype,
            'publisher' => $publisher,
            'format' => $format,
            'documenttype' => $documenttype,
            'educationallevel' => $educationallevel,
            'targetaudience' => $targetaudience,
            'discipline' => $discipline,
            'location' => (
            new \moodle_url(
                '/local/inveniordm/student/download.php',
                [
                    'recordid' => $id
                ]
            )
            )->out(false),
            'copyright' => $copyright,
            'relation' => $relation,
            'learningresourcetype' => $learningresourcetype,
            'language' => $language,
            'description' => strip_tags($description),
            'keywords' => $keywords,
            'backurl' => (
            new \moodle_url(
                '/local/inveniordm/student/search.php'
            )
            )->out()
        ];

        return $OUTPUT->render_from_template(
            'local_inveniordm/student/view',
            $context
        );
    }
}