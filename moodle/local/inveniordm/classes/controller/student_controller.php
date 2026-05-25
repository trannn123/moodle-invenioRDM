<?php

namespace local_inveniordm\controller;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../api/invenio_client.php');

use local_inveniordm\api\invenio_client;

class student_controller {

    public function search() {

        global $OUTPUT;

        $client = new invenio_client();

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

        $response = $client->get_records($query);

        $records = [];

        $hits = $response['hits']['hits'] ?? [];

        foreach ($hits as $record) {

            $title =
                $record['metadata']['title'] ?? '';

            $description =
                $record['metadata']['description'] ?? '';

            $subject =
                $record['metadata']['taxon_entry'] ?? '';

            // FILTER SEARCH
            $matchquery =
                $query === '' ||
                stripos($title, $query) !== false ||
                stripos($description, $query) !== false ||
                stripos($subject, $query) !== false;

            $matchformat =
                $format === '' ||
                (
                    ($record['metadata']['format'] ?? '')
                    === $format
                );

            $matchdiscipline =
                $discipline === '' ||
                (
                    ($record['metadata']['taxon_entry'] ?? '')
                    === $discipline
                );

            $matchlevel =
                $level === '' ||
                (
                    ($record['metadata']['educational_level'] ?? '')
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
                        $record['metadata']['entity']
                        ?? 'Unknown',

                    'format' =>
                        $record['metadata']['format']
                        ?? 'Unknown',

                    'discipline' =>
                        $record['metadata']['taxon_entry']
                        ?? 'Unknown',

                    'educationallevel' =>
                        $record['metadata']['educational_level']
                        ?? 'Unknown',

                    'learningresourcetype' =>
                        $record['metadata']['learning_resource_type']
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

            $author =
                $metadata['creators'][0]['person_or_org']['name'];
        }

        $publicationdate =
            $metadata['publication_date']
            ?? 'Not specified';

        $resourcetype = 'Unknown';

        if (isset($metadata['resource_type']['title']['en'])) {

            $resourcetype =
                $metadata['resource_type']['title']['en'];

        } else if (isset($metadata['resource_type']['title'])) {

            $resourcetype =
                $metadata['resource_type']['title'];
        }

        $publisher =
            $metadata['publisher']
            ?? 'Not specified';

        $description =
            $metadata['description']
            ?? 'No description';

        $format =
            $metadata['format']
            ?? 'Unknown';

        $documenttype =
            $metadata['documentary_type']
            ?? 'Unknown';

        $educationallevel =
            $metadata['educational_level']
            ?? 'Unknown';

        $targetaudience =
            $metadata['target_audience']
            ?? 'Unknown';

        $discipline =
            $metadata['taxon_entry']
            ?? 'Unknown';

        $location =
            $metadata['location']
            ?? '#';

        $copyright =
            $metadata['copyright']
            ?? 'Unknown';

        $relation =
            $metadata['relation']
            ?? 'Not specified';

        $learningresourcetype =
            $metadata['learning_resource_type']
            ?? 'Unknown';

        $language =
            $metadata['language']
            ?? 'Unknown';

        $keywords = [];

        if (!empty($metadata['subjects'])) {

            foreach ($metadata['subjects'] as $subject) {

                if (isset($subject['subject'])) {

                    $keywords[] = [
                        'name' => $subject['subject']
                    ];
                }
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

            'location' => $location,

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