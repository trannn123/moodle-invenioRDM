<?php

namespace local_inveniordm\controller;
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../api/invenio_client.php');

use local_inveniordm\api\invenio_client;
use local_inveniordm\service\pagination_service;
use log_service;

class resource_controller
{
    private const SEARCH_PAGE_SIZE = 5;

    public function view($id, $returnurl = '')
    {
        global $OUTPUT;
        $client = new invenio_client();
        $record = $client->get_record($id);

        if (!$record || !isset($record['metadata'])) {
            return $OUTPUT->notification('Record not found or API error', 'error');
        }

        global $USER;
        log_service::add($USER->id, 'VIEW_RESOURCE', $id);

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
                '/local/inveniordm/resource/download.php',
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
                '/local/inveniordm/resource/search.php'
            )
            )->out(false);
        }
        $context['backurl'] = $backurl;

        return $OUTPUT->render_from_template(
            'local_inveniordm/resource/view',
            $context
        );
    }

    public function search($backurl = '')
    {
        if (empty($backurl)) {
            $backurl = optional_param('backurl', '', PARAM_URL);
            if (empty($backurl)) {
                $backurl = (new \moodle_url('/local/inveniordm/dashboard.php'))->out(false);
            }
        }

        global $OUTPUT, $USER;
        $client = new invenio_client();

        $query = optional_param('q', '', PARAM_TEXT);
        $page = optional_param('page', 1, PARAM_INT);

        if (!empty($query)) {
            log_service::add($USER->id, 'SEARCH_RESOURCE');
        }

        $format = optional_param('format', '', PARAM_TEXT);
        $level = optional_param('level', '', PARAM_TEXT);

        $response = $client->get_records($query);

        $returnurl = new \moodle_url(
            '/local/inveniordm/resource/search.php',
            [
                'q' => $query,
                'format' => $format,
                'level' => $level,
                'backurl' => $backurl
            ]
        );

        $baseurl = new \moodle_url(
            '/local/inveniordm/resource/search.php',

            [
                'q' => $query,
                'format' => $format,
                'level' => $level,
                'backurl' => $backurl

            ]

        );

        $records = [];
        $hits = $response['hits']['hits'] ?? [];

        foreach ($hits as $record) {
            $metadata = $record['metadata'] ?? [];
            $customfields = $record['custom_fields'] ?? [];
            $title = $metadata['title'] ?? '';
            $description = $metadata['description'] ?? '';
            $subject = $customfields['moodle:taxon_entry'] ?? '';
            $matchquery =
                $query === '' || stripos($title, $query) !== false || stripos($description, $query) !== false ||
                stripos($subject, $query) !== false;
            $matchformat =
                $format === '' || strcasecmp($customfields['moodle:format'] ?? '', $format) === 0;
            $matchlevel =
                $level === '' || strcasecmp($customfields['moodle:educational_level'] ?? '', $level) === 0;
            if ($matchquery && $matchformat && $matchlevel) {
                $records[] = [
                    'id' => $record['id'] ?? '',
                    'title' => $title,
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
                        ?? 'Unknown',
                    'viewurl' => (
                    new \moodle_url(
                        '/local/inveniordm/resource/view.php',
                        [
                            'id' => $record['id'],
                            'returnurl' => $returnurl->out(false)
                        ]
                    )
                    )->out(false),
                ];
            }
        }

        $pagination_service = new pagination_service();

        $pagination = $pagination_service->paginate(
            $records,
            $page,
            self::SEARCH_PAGE_SIZE,
            $baseurl
        );

        $records = $pagination['items'];

        $reseturl = (
        new \moodle_url(
            '/local/inveniordm/resource/search.php',
            [
                'backurl' => $backurl
            ]
        )
        )->out(false);


        $context = [
            'query' => $query,
            'records' => $records,

            'selected_pdf' => $format === 'pdf',
            'selected_doc' => $format === 'doc',
            'selected_docx' => $format === 'docx',
            'selected_ppt' => $format === 'ppt',
            'selected_pptx' => $format === 'pptx',
            'selected_xls' => $format === 'xls',
            'selected_xlsx' => $format === 'xlsx',

            'selected_school' => $level === 'school education',
            'selected_higher' => $level === 'higher education',
            'selected_bachelor' => $level === "bachelor's degree",
            'selected_master' => $level === "master's degree",
            'selected_doctorate' => $level === 'doctorate',

            'backurl' => $backurl,
            'reseturl' => $reseturl,

            'currentpage' => $pagination['page'],
            'totalpages' => $pagination['totalpages'],
            'totalitems' => $pagination['totalitems'],

            'pages' => $pagination['pages'],
            'previous' => $pagination['previous'],
            'next' => $pagination['next'],
        ];

        return $OUTPUT->render_from_template(
            'local_inveniordm/resource/search',
            $context
        );
    }
}