<?php


defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/api/invenio_client.php'
);

require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/service/invenio_mapper.php'
);

require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/service/log_service.php'
);

class upload_service
{
    public function upload($data, $user): array
    {
        global $CFG;

        $result = [
            'success' => false,
            'message' => '',
            'recordid' => null
        ];

        $fs = get_file_storage();
        $usercontext = context_user::instance($user->id);

        $files = $fs->get_area_files(
            $usercontext->id,
            'user',
            'draft',
            $data->resourcefile,
            'id',
            false
        );

        $filepath = '';
        $filename = '';

        foreach ($files as $file) {
            $filename = $file->get_filename();

            $fullpath =
                $CFG->dirroot .
                '/local/inveniordm/repository/' .
                time() .
                '_' .
                $filename;

            $file->copy_content_to($fullpath);

            $filepath = $fullpath;
            break;
        }

        if (empty($filepath)) {
            $result['message'] = 'No uploaded file found';
            return $result;
        }

        $client = new \local_inveniordm\api\invenio_client();

        $recordpayload =
            \invenio_mapper::map(
                $data,
                $user
            );

        $record = $client->create_record(
            $recordpayload
        );

        $recordid = $record['data']['id'] ?? null;

        $client->upload_file(
            $recordid,
            [
                'name' => $filename,
                'tmp_name' => $filepath
            ]
        );

        $publishresult =
            $client->publish_record(
                $recordid
            );

        $publishcode =
            $publishresult['httpcode'];

        if ($publishcode >= 200 && $publishcode < 300) {

            \log_service::add(
                $user->id,
                'UPLOAD_RESOURCE',
                $recordid
            );

            $result['success'] = true;
            $result['message'] = 'Upload resource successfully!';
            $result['recordid'] = $recordid;

            return $result;
        }

        $result['message'] = 'Upload or publish failed!';

        return $result;
    }
}