<?php

namespace local_inveniordm\service;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../api/invenio_client.php');

use local_inveniordm\api\invenio_client;

class record_service {

    private invenio_client $client;

    public function __construct() {
        $this->client = new invenio_client();
    }

    // SEARCH
    public function search_records(string $query = ''): array {
        $response = $this->client->get_records($query);
        return $response['hits']['hits'] ?? [];
    }

    // CREATE
    public function create_record(array $metadata): array {
        return $this->client->create_record($metadata);
    }

    // GET ONE
    public function get_record(string $id): array {
        return $this->client->get_record($id);
    }

    // UPLOAD FILE
    public function upload_file($record_id, $file): array {
        return $this->client->upload_file($record_id, $file);
    }
}