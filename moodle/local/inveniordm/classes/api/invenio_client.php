<?php

namespace local_inveniordm\api;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/filelib.php');

class invenio_client {

    private string $apiurl;

    private string $token;

    private string $hostheader;

    public function __construct() {

        $this->apiurl = 'https://host.docker.internal:5000/api/';
        $this->hostheader = 'localhost';

        $this->token = 'Bro0sVoQ6RcKrJNX5vAnsoxcP1l5Q9XuZy2TZuTzDQBps8quo7lvz0NSG7J8';
    }

    private function make_request(
        string $url,
        string $method = 'GET'
    ): array {

        $ch = curl_init();

        $headers = [
            'Accept: application/json'
        ];
        if (!empty($this->token)) {

            $headers[] =
                'Authorization: Bearer ' .
                $this->token;
        }


        curl_setopt_array($ch, [

            CURLOPT_URL => $url,

            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_HTTPHEADER => $headers,

            CURLOPT_TIMEOUT => 30,

            CURLOPT_SSL_VERIFYPEER => false,

            CURLOPT_SSL_VERIFYHOST => false,

            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response = curl_exec($ch);

        $httpcode = curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

        if ($response === false) {

            debugging(
                'cURL Error: ' .
                curl_error($ch)
            );

            curl_close($ch);

            return [];
        }

        curl_close($ch);

        if ($httpcode < 200 || $httpcode >= 300) {
            return [
                'error' => true,
                'status' => $httpcode,
                'response' => $response,
                'url' => $url,
                'payload' => $payload ?? null
            ];
        }

        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    private function make_post_request(string $url, string $payload): array {

        $ch = curl_init();

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];

        if (!empty($this->token)) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            debugging('cURL Error: ' . curl_error($ch));
            curl_close($ch);
            return [];
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $decoded = json_decode($response, true);

        if ($httpcode < 200 || $httpcode >= 300) {
            debugging('HTTP Error: ' . $httpcode . ' Response: ' . $response);
            return [];
        }

        if (!is_array($decoded)) {
            debugging('Invalid JSON Response: ' . $response);
            return [];
        }

        return $decoded;
    }

    public function get_records(
        string $query = ''
    ): array {

        $jsonpath =
            __DIR__ .
            '/../../mock_records.json';
        if (!file_exists($jsonpath)) {
            return [];
        }

        $json = file_get_contents($jsonpath);

        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    public function get_record(
        string $id
    ): array {

        $jsonpath =
            __DIR__ .
            '/../../mock_records.json';

        if (!file_exists($jsonpath)) {
            return [];
        }

        $json = file_get_contents($jsonpath);

        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return [];
        }

        $hits = $decoded['hits']['hits'] ?? [];

        foreach ($hits as $record) {

            if (($record['id'] ?? '') === $id) {
                return $record;
            }
        }

        return [];
    }

    public function create_mock_record(array $metadata): bool {

        $jsonpath = __DIR__ . '/../../mock_records.json';

        if (!file_exists($jsonpath)) {
            return false;
        }

        $json = file_get_contents($jsonpath);

        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return false;
        }

        if (!isset($decoded['hits']['hits'])) {
            $decoded['hits']['hits'] = [];
        }

        $record = [
            'id' => $metadata['identifier'],
            'metadata' => $metadata
        ];

        $decoded['hits']['hits'][] = $record;

        $jsonEncoded = json_encode(
            $decoded,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        if ($jsonEncoded === false) {
            return false;
        }

        $result = file_put_contents($jsonpath, $jsonEncoded);

        return $result !== false;
    }

    public function create_record(array $metadata): array {

        $url = $this->apiurl . 'records';

        $payload = json_encode([
            'metadata' => $metadata
        ]);

        $result = $this->make_post_request($url, $payload);

        if (empty($result) || !isset($result['id'])) {
            debugging('Create record failed');
            return [];
        }

        return $result;
    }

    public function upload_file($record_id, $file): array {

        $filename = $file['name'];
        $filepath = $file['tmp_name'];
        $filedata = file_get_contents($filepath);

        $key = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        // STEP 1: create file entry
        $url1 = $this->apiurl . "records/$record_id/draft/files";

        $res1 = $this->make_post_request($url1, json_encode([
            "key" => $key
        ]));

        if (empty($res1)) {
            return [
                'step' => 'create_file_entry',
                'error' => 'failed',
                'debug_url' => $url1,
                'debug_key' => $key
            ];
        }

        // STEP 2: upload content
        $url2 = $this->apiurl . "records/$record_id/draft/files/$key/content";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url2,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => $filedata,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/octet-stream',
                'Authorization: Bearer ' . $this->token
            ],
        ]);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 300) {
            return [
                'step' => 'upload_content',
                'error' => 'failed',
                'http_code' => $code,
                'response' => $response
            ];
        }

        // STEP 3: commit (ĐÚNG API)
        $commitUrl = $this->apiurl . "records/$record_id/draft/files/commit";

        $commit = $this->make_post_request($commitUrl, "{}");

        return [
            'step' => 'upload_complete',
            'upload_code' => $code,
            'commit' => $commit
        ];
    }
    public function init_file($record_id, $filename): array {

        $url = $this->apiurl . "records/$record_id/draft/files/$filename";

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $this->token
            ]
        ]);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($code < 200 || $code >= 300) {
            return [
                'step' => 'upload_content',
                'error' => 'failed',
                'http_code' => $code,
                'response' => $response
            ];
        }
        curl_close($ch);

        return [
            'code' => $code,
            'body' => json_decode($response, true)
        ];
    }
}