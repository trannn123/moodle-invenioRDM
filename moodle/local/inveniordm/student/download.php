<?php

require_once(__DIR__ . '/../../../config.php');
use local_inveniordm\api\invenio_client;
require_login();
global $CFG;

require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/api/invenio_client.php'
);

$recordid = required_param(
    'recordid',
    PARAM_TEXT
);

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
    'http://host.docker.internal:5001',
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
        'Authorization: Bearer ' . INVENIO_TOKEN
    ]
]);
$content = curl_exec($ch);
curl_close($ch);
header(
    'Content-Type: application/octet-stream'
);
header(
    'Content-Disposition: attachment; filename="' .
    $file['key'] .
    '"'
);
echo $content;
exit;