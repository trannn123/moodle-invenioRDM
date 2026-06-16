<?php

namespace local_inveniordm\controller;
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../api/invenio_client.php');
use local_inveniordm\api\invenio_client;

class resource_controller {
    public function view($id, $returnurl = '') {
        global $OUTPUT;
        $client = new invenio_client();
        $record = $client->get_record($id);

        if (!$record || !isset($record['metadata'])) {
            return $OUTPUT->notification('Record not found or API error', 'error');
        }

        $metadata = $record['metadata'] ?? [];
        $customfields = $record['custom_fields'] ?? [];
        $title = $metadata['title'] ?? 'No title';
        $description = strip_tags($metadata['description'] ?? '');
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
        $format = $customfields['moodle:format'] ?? 'Unknown';
        $documenttype = $customfields['moodle:documentary_type'] ?? 'Unknown';
        $educationallevel = $customfields['moodle:educational_level'] ?? 'Unknown';
        $targetaudience = $customfields['moodle:target_audience'] ?? 'Unknown';
        $discipline = $customfields['moodle:taxon_entry'] ?? 'Unknown';
        $objective = $customfields['moodle:objective'] ?? 'Unknown';
        $copyright = $customfields['moodle:copyright'] ?? 'Unknown';
        $relation = $customfields['moodle:relation'] ?? 'Not specified';
        $learningresourcetype = $customfields['moodle:learning_resource_type'] ?? 'Unknown';
        $language = $customfields['moodle:language'] ?? 'Unknown';
        $role = $customfields['moodle:role'] ?? 'Unknown';
        $identifier = $customfields['moodle:identifier'] ?? '';
        $locationfield = $customfields['moodle:location'] ?? '';

        $keywords = [];

        if (!empty($customfields['moodle:free_keyword'])
            && is_array($customfields['moodle:free_keyword'])) {
            foreach ($customfields['moodle:free_keyword'] as $keyword) {
                $keywords[] = [
                    'name' => $keyword
                ];
            }
        }

        $context = [
            'title' => $title,
            'description' => $description,
            'author' => $author,
            'identifier' => $identifier,
            'publicationdate' => $publicationdate,
            'format' => $format,
            'documenttype' => $documenttype,
            'educationallevel' => $educationallevel,
            'targetaudience' => $targetaudience,
            'discipline' => $discipline,
            'objective' => $objective,
            'copyright' => $copyright,
            'relation' => $relation,
            'learningresourcetype' => $learningresourcetype,
            'language' => $language,
            'role' => $role,
            'locationfield' => $locationfield,
            'keywords' => $keywords,
            'location' => (
                new \moodle_url(
                    '/local/inveniordm/student/download.php',
                    [
                        'recordid' => $id
                    ]
                )
            )->out(false),
        ];

        if (!empty($returnurl)) {
            $backurl = $returnurl;
        } else {
            $backurl = (
                new \moodle_url(
                    '/local/inveniordm/student/search.php'
                )
            )->out(false);
        }
        $context['backurl'] = $backurl;

        return $OUTPUT->render_from_template(
            'local_inveniordm/student/view',
            $context
        );
    }
}