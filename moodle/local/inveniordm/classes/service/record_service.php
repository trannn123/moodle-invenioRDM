<?php

use api\invenio_client;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../api/invenio_client.php');
class record_service {

    private invenio_client $client;

    public function __construct() {
        $this->client = new invenio_client();
    }

    public function search_records(string $query = ''): array {

        $response = $this->client->get_records($query);

        return $response['hits']['hits'] ?? [];
    }
}