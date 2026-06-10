<?php

require_once(__DIR__.'/../../config.php');
require_login();
use local_inveniordm\api\invenio_client;
$client = new invenio_client();
$data = $client->get_record('hdmd0-6cp75');
echo '<pre>';
print_r($data);
echo '</pre>';