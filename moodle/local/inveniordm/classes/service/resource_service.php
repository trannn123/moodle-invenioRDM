<?php

defined('MOODLE_INTERNAL') || die();

class resource_service
{
    public function get_course_resources(int $courseid): array
    {
        global $DB;
        $resources = $DB->get_records(
            'local_inveniordm_course_resources',
            ['courseid' => $courseid],
            'timecreated DESC'
        );

        $result = [];

        foreach ($resources as $res) {
            $result[] = [
                'id' => $res->id,
                'title' => s($res->title),
                'recordid' => s($res->recordid),
                'timecreated' => userdate($res->timecreated),
                'viewurl' => (new \moodle_url(
                    '/local/inveniordm/resource/view.php',
                    [
                        'id' => $res->recordid,
                        'returnurl' => qualified_me()
                    ]
                ))->out(false),
            ];
        }

        return [
            'resources' => $result,
            'hasresources' => !empty($result),
        ];
    }

    public function get_lecturer_course_resources(int $courseid): array
    {
        global $DB;
        $resources = $DB->get_records(
            'local_inveniordm_course_resources',
            ['courseid' => $courseid],
            'timecreated DESC'
        );
        $result = [];

        foreach ($resources as $res) {
            $result[] = [
                'title' => s($res->title),
                'recordid' => s($res->recordid),
                'timecreated' => userdate($res->timecreated, '%d/%m/%Y'),
                'viewurl' => (new moodle_url(
                    '/local/inveniordm/resource/view.php',
                    [
                        'id' => $res->recordid,
                        'returnurl' => qualified_me()
                    ]
                ))->out(false),
                'downloadurl' => (new moodle_url(
                    '/local/inveniordm/resource/download.php',
                    ['recordid' => $res->recordid]
                ))->out(false),
            ];
        }

        return [
            'resources' => $result,
            'hasresources' => !empty($result),
            'totalresources' => count($result),
        ];
    }
}