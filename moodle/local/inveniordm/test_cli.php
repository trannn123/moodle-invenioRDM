<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
use local_inveniordm\api\invenio_client;
$client = new invenio_client();
$result = $client->get_records();
print_r($result);