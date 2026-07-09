<?php

require_once(__DIR__ . '/../../../config.php');

use local_inveniordm\api\invenio_client;

require_login();
global $CFG;

require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/api/invenio_client.php'
);

require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/service/log_service.php'
);

$recordid = required_param('recordid', PARAM_TEXT);

global $USER;
log_service::add($USER->id, 'DOWNLOAD_RESOURCE', $recordid);

$client = new invenio_client();
$record = $client->get_record($recordid);
$files = $record['files']['entries'] ?? [];
if (empty($files)) {
    throw new moodle_exception('No file found');
}
$file = array_values($files)[0];
$contenturl = $file['links']['content'];
$contenturl = str_replace(
    'http://localhost',
    'https://host.docker.internal',
    $contenturl
);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $contenturl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER => [
        'Host: localhost',
        'Authorization: Bearer ' . $client->get_token()
    ]
]);

$content = curl_exec($ch);
curl_close($ch);
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file['key'] . '"');
echo $content;
exit;