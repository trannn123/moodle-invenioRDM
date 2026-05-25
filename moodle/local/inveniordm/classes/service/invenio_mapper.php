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

            'identifier' => uniqid('record-'),

            'title' => $data->title ?? '',

            'description' => $data->description ?? '',

            'free_keyword' =>
                array_map(
                    'trim',
                    explode(',', $data->keywords ?? '')
                ),

            'language' => $data->language ?? 'en',

            'documentary_type' =>
                $data->documenttype ?? '',

            'format' =>
                $data->format ?? '',

            'location' => '#',

            'learning_resource_type' =>
                $data->learningresourcetype ?? '',

            'target_audience' =>
                $data->targetaudience ?? '',

            'educational_level' =>
                $data->educationallevel ?? '',

            'copyright' =>
                $data->copyright ?? '',

            'objective' => 'discipline',

            'taxon_entry' =>
                $data->discipline ?? '',

            'role' => 'author',

            'entity' =>
                trim(
                    $USER->firstname .
                    ' ' .
                    $USER->lastname
                ),

            'date' =>
                date('Y-m-d'),

            'relation' =>
                $data->relation ?? ''
        ];
    }
}