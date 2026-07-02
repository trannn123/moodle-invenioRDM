<?php

defined('MOODLE_INTERNAL') || die();

class invenio_mapper
{
    public static function map($data, $USER): array
    {
        $fullname = fullname($USER);

        return [
            'files' => ['enabled' => true],
            'metadata' => [
                'title' => trim($data->title ?? ''),
                'description' => trim($data->description ?? ''),
                'publication_date' => date('Y-m-d'),
                'resource_type' => ['id' => 'publication-article'],
                'creators' => [
                    [
                        'person_or_org' => [
                            'type' => 'personal',
                            'name' => $USER->lastname . ', ' . $USER->firstname,
                            'family_name' => $USER->lastname,
                            'given_name' => $USER->firstname
                        ]
                    ]
                ]
            ],
            'custom_fields' => [
                'moodle:identifier' =>
                    !empty($data->identifier)
                        ? trim($data->identifier)
                        : uniqid('record-'),
                'moodle:free_keyword' =>
                    array_values(
                        array_filter(
                            array_map(
                                'trim',
                                explode(
                                    ',',
                                    $data->free_keyword ?? ''
                                )
                            )
                        )
                    ),
                'moodle:language' => $data->language ?? '',
                'moodle:documentary_type' => $data->documentary_type ?? '',
                'moodle:format' => $data->format ?? '',
                'moodle:location' => $data->location ?? '',
                'moodle:learning_resource_type' => $data->learning_resource_type ?? '',
                'moodle:target_audience' => $data->target_audience ?? '',
                'moodle:educational_level' => $data->educational_level ?? '',
                'moodle:induced_activity' => $data->induced_activity ?? '',
                'moodle:copyright' => $data->copyright ?? '',
                'moodle:objective' => $data->objective ?? '',
                'moodle:taxon_entry' => $data->taxon_entry ?? '',
                'moodle:role' => $data->role ?? 'author',
                'moodle:entity' =>
                    !empty($data->entity)
                        ? $data->entity
                        : $fullname,
                'moodle:date' =>
                    !empty($data->date)
                        ? date('Y-m-d', $data->date)
                        : date('Y-m-d'),
                'moodle:relation' => $data->relation ?? '',
                'moodle:metadata_accessibility' => $data->metadata_accessibility ?? ''
            ]
        ];
    }
}