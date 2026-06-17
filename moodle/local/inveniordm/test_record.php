<?php

require_once(__DIR__ . '/../../config.php');

require_login();

require_once(
    $CFG->dirroot .
    '/local/inveniordm/classes/api/invenio_client.php'
);

$client = new \local_inveniordm\api\invenio_client();

echo '<pre>';

$response = $client->get_records();

echo '<pre>';
print_r($response);
echo '</pre>';

echo '</pre>';