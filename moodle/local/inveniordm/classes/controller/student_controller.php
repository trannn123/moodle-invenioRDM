<?php

namespace local_inveniordm\controller;
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../api/invenio_client.php');
use local_inveniordm\api\invenio_client;

class student_controller {
    public function search() {

        global $OUTPUT;

        $client = new invenio_client();

        $query =
            optional_param(
                'q',
                '',
                PARAM_TEXT
            );

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

        $response =
            $client->get_records(
                $query
            );

        $records = [];

        $hits =
            $response['hits']['hits']
            ?? [];

        foreach ($hits as $record) {

            $metadata =
                $record['metadata']
                ?? [];

            $customfields =
                $record['custom_fields']
                ?? [];

            $title =
                $metadata['title']
                ?? '';

            $description =
                $metadata['description']
                ?? '';

            $subject =
                $customfields['moodle:taxon_entry']
                ?? '';

            $matchquery =
                $query === '' ||
                stripos(
                    $title,
                    $query
                ) !== false ||
                stripos(
                    $description,
                    $query
                ) !== false ||
                stripos(
                    $subject,
                    $query
                ) !== false;

            $matchformat =
                $format === '' ||
                strcasecmp(
                    $customfields['moodle:format']
                    ?? '',
                    $format
                ) === 0;

            $matchdiscipline =
                $discipline === '' ||
                strcasecmp(
                    $customfields['moodle:taxon_entry']
                    ?? '',
                    $discipline
                ) === 0;

            $matchlevel =
                $level === '' ||
                strcasecmp(
                    $customfields['moodle:educational_level']
                    ?? '',
                    $level
                ) === 0;

            if (
                $matchquery &&
                $matchformat &&
                $matchdiscipline &&
                $matchlevel
            ) {

                $records[] = [

                    'id' =>
                        $record['id']
                        ?? '',

                    'title' =>
                        $title,

                    'author' =>
                        $customfields['moodle:entity']
                        ??
                        (
                            $metadata['creators'][0]['person_or_org']['name']
                            ?? 'Unknown'
                        ),

                    'format' =>
                        $customfields['moodle:format']
                        ?? 'Unknown',

                    'discipline' =>
                        $customfields['moodle:taxon_entry']
                        ?? 'Unknown',

                    'educationallevel' =>
                        $customfields['moodle:educational_level']
                        ?? 'Unknown'
                ];
            }
        }

        $context = [

            'query' =>
                $query,

            'records' =>
                $records,

            'selected_pdf' =>
                $format === 'pdf',

            'selected_doc' =>
                $format === 'doc',

            'selected_bachelor' =>
                $level === "bachelor's degree",

            'selected_master' =>
                $level === "master's degree"
        ];

        return $OUTPUT->render_from_template(
            'local_inveniordm/student/search',
            $context
        );
    }

    public function view($id) {

        global $OUTPUT;

        $client =
            new invenio_client();

        $record =
            $client->get_record($id);

        if (
            !$record ||
            !isset(
                $record['metadata']
            )
        ) {

            return $OUTPUT->notification(
                'Record not found or API error',
                'error'
            );
        }

        $metadata =
            $record['metadata']
            ?? [];

        $customfields =
            $record['custom_fields']
            ?? [];

        $title =
            $metadata['title']
            ?? 'No title';

        $description =
            strip_tags(
                $metadata['description']
                ?? ''
            );

        $author =
            $customfields['moodle:entity']
            ??
            (
                $metadata['creators'][0]['person_or_org']['name']
                ?? 'Unknown'
            );

        $publicationdate =
            $customfields['moodle:date']
            ??
            (
                $metadata['publication_date']
                ?? 'Not specified'
            );

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

        $objective =
            $customfields['moodle:objective']
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

        $role =
            $customfields['moodle:role']
            ?? 'Unknown';

        $identifier =
            $customfields['moodle:identifier']
            ?? '';

        $locationfield =
            $customfields['moodle:location']
            ?? '';

        $keywords = [];

        if (
            !empty(
            $customfields['moodle:free_keyword']
            ) &&
            is_array(
                $customfields['moodle:free_keyword']
            )
        ) {

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

            'title' =>
                $title,

            'description' =>
                $description,

            'author' =>
                $author,

            'identifier' =>
                $identifier,

            'publicationdate' =>
                $publicationdate,

            'format' =>
                $format,

            'documenttype' =>
                $documenttype,

            'educationallevel' =>
                $educationallevel,

            'targetaudience' =>
                $targetaudience,

            'discipline' =>
                $discipline,

            'objective' =>
                $objective,

            'copyright' =>
                $copyright,

            'relation' =>
                $relation,

            'learningresourcetype' =>
                $learningresourcetype,

            'language' =>
                $language,

            'role' =>
                $role,

            'locationfield' =>
                $locationfield,

            'keywords' =>
                $keywords,

            'location' => (
            new \moodle_url(
                '/local/inveniordm/student/download.php',
                [
                    'recordid' => $id
                ]
            )
            )->out(false),

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