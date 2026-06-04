<?php

namespace local_inveniordm\service;

defined('MOODLE_INTERNAL') || die();

class invenio_mapper {

    public static function map($data, $USER): array {

        $fullname = fullname($USER);

        return [

            'files' => [
                'enabled' => true
            ],

            /*
             * STANDARD INVENTIO METADATA
             */
            'metadata' => [

                'title' => trim($data->title),

                'description' => trim($data->description),

                'publication_date' => date('Y-m-d'),

                'resource_type' => [
                    'id' => 'publication-article'
                ],

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

            /*
             * YOUR CUSTOM LOM METADATA
             */
            'custom_fields' => [

                'moodle:identifier' =>
                    uniqid('record-'),

                'moodle:free_keyword' =>
                    array_values(
                        array_filter(
                            array_map(
                                'trim',
                                explode(',', $data->keywords ?? '')
                            )
                        )
                    ),

                'moodle:language' =>
                    $data->language ?? '',

                'moodle:documentary_type' =>
                    $data->documenttype ?? '',

                'moodle:format' =>
                    $data->format ?? '',

                'moodle:location' =>
                    '#',

                'moodle:learning_resource_type' =>
                    $data->learningresourcetype ?? '',

                'moodle:target_audience' =>
                    $data->targetaudience ?? '',

                'moodle:educational_level' =>
                    $data->educationallevel ?? '',

                'moodle:copyright' =>
                    $data->copyright ?? '',

                'moodle:objective' =>
                    'discipline',

                'moodle:taxon_entry' =>
                    $data->discipline ?? '',

                'moodle:role' =>
                    'author',

                'moodle:entity' =>
                    $fullname,

                'moodle:date' =>
                    date('Y-m-d'),

                'moodle:relation' =>
                    $data->relation ?? ''
            ]
        ];
    }
}