<?php

namespace local_inveniordm\api;
defined('MOODLE_INTERNAL') || die();
global $CFG;
// Load thư viện xử lý file của Moodle
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/local/inveniordm/locallib.php');

class invenio_client {
    private string $apiurl;
    private string $token;
    private string $hostheader;
    public function __construct() {
        $this->apiurl = 'http://host.docker.internal:5001/api/';
        $this->hostheader = 'localhost';
        $this->token = \INVENIO_TOKEN;
    }

    private function make_request(string $url, string $method = 'GET'): array {
        $ch = curl_init();
        $headers = [
            'Accept: application/json',
            'Host: localhost'
        ];
        if (!empty($this->token)) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
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

        // Gửi request
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch,CURLINFO_HTTP_CODE);

        if ($response === false) {
            debugging('cURL Error: ' . curl_error($ch));
            curl_close($ch);
            return [];
        }

        curl_close($ch);

        if ($httpcode < 200 || $httpcode >= 300) {
            return [
                'error' => true,
                'status' => $httpcode,
                'response' => $response,
                'url' => $url
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
            'Host: localhost',
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
            return [
                'error' => true,
                'http_code' => $httpcode,
                'response' => $response,
                'url' => $url
            ];
        }

        if (!is_array($decoded)) {
            debugging('Invalid JSON Response: ' . $response);
            return [];
        }
        return $decoded;
    }

    public function get_records(string $query = ''): array {
        // Không tìm kiếm, vd: GET /api/records
        $url = $this->apiurl . 'records';
        if (!empty($query)) {
            // Có tìm kiếm, vd: GET /api/records?q=vlan
            $url .= '?q=' . urlencode($query);
        }
        return $this->make_request($url);
    }

    public function get_record(string $id): array {
        // Lấy chi tiết 1 record, vd: GET /api/records/abcd123
        $url = $this->apiurl . 'records/' . $id;
        return $this->make_request($url);
    }

    public function create_record(array $metadata): array {
        $url = $this->apiurl . 'records';

        $payload = json_encode(
            $metadata,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Host: localhost',
                'Content-Type: application/json',
                'Accept: application/json',
                'Content-Length: ' . strlen($payload),
                'Authorization: Bearer ' . $this->token
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        // Gửi POST, vd: POST /api/records
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return [
            'http_code' => $code,
            'curl_error' => $error,
            'sent_payload' => $metadata,
            'raw' => $response,
            'data' => json_decode($response, true)
        ];
    }

    public function upload_file($record_id, $file): array {
        $filename = $file['name'];
        $filepath = $file['tmp_name'];
        $filedata = file_get_contents($filepath);

        $key = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        // Đăng ký file draft, vd: POST /api/records/{id}/draft/files
        $url1 = $this->apiurl . "records/$record_id/draft/files";
        $res1 = $this->make_post_request($url1, json_encode([
                    [
                        "key" => $key
                    ]
        ]));

        if (empty($res1)) {
            return [
                'step' => 'create_file_entry',
                'error' => 'failed',
                'debug_url' => $url1,
                'debug_key' => $key
            ];
        }

        // Upload nội dung file, vd: PUT /api/records/{id}/draft/files/{key}/content
        $url2 = $this->apiurl . "records/$record_id/draft/files/$key/content";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url2,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => $filedata,
            CURLOPT_HTTPHEADER => [
                'Host: localhost',
                'Content-Type: application/octet-stream',
                'Authorization: Bearer ' . $this->token
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
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

        // Commit file, vd: POST /api/records/{id}/draft/files/{key}/commit
        $commitUrl = $this->apiurl . "records/$record_id/draft/files/$key/commit";
        $commit = $this->make_post_request($commitUrl, "{}");

        return [
            'step' => 'upload_complete',
            'upload_code' => $code,
            'commit' => $commit
        ];
    }

    public function get_token(): string {
        return $this->token;
    }
}