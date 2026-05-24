<?php

namespace local_inveniordm\service;

defined('MOODLE_INTERNAL') || die();

class invenio_mapper {

    public static function map($data, $USER) {

        // SAFE USER fallback
        $username = 'Unknown User';
        if ($USER) {
            $username = trim($USER->firstname . ' ' . $USER->lastname);
        }

        return [
            "metadata" => [
                "title" => $data->title ?? '',
                "description" => $data->description ?? '',
                "language" => $data->language ?? 'en',

                "resource_type" => [
                    "id" => $data->learningresourcetype ?? 'lesson'
                ],

                "creators" => [
                    [
                        "person_or_org" => [
                            "type" => "personal",
                            "name" => $username
                        ]
                    ]
                ],

                "subjects" => [
                    [
                        "subject" => $data->discipline ?? 'General'
                    ]
                ],

                "publication_date" => date('Y-m-d')
            ],

            "custom_fields" => [
                "keywords" => $data->keywords ?? '',
                "format" => $data->format ?? '',
                "document_type" => $data->documenttype ?? '',
                "target_audience" => $data->targetaudience ?? '',
                "educational_level" => $data->educationallevel ?? '',
                "copyright" => $data->copyright ?? 'no'
            ]
        ];
    }
}