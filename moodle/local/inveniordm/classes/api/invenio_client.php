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

        $this->apiurl = 'https://host.docker.internal/api/';
        $this->hostheader = 'localhost';

        $this->token = 'tw1PudMrXcbIjKQRRUk2etQ3gaMW2j2J22fbkwUEWZ02sSBwWjQ6kdRiIEvJ';
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

        $url = $this->apiurl . 'records';

        if (!empty($query)) {

            $url .= '?q=' .
                urlencode($query);

        } else {

            $url .= '?q=*';
        }

        return $this->make_request($url);
    }

    public function get_record(
        string $id
    ): array {

        $url =
            $this->apiurl .
            'records/' .
            urlencode($id);

        return $this->make_request($url);
    }
    public function create_record(array $metadata): array {

        $url = $this->apiurl . 'records';

        $payload = json_encode([
            'metadata' => $metadata,
            'access' => [
                'record' => 'public',
                'files' => 'public'
            ]
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

        // STEP 1: CREATE ENTRY
        $url1 = $this->apiurl . "records/$record_id/draft/files";
        $res1 = $this->make_post_request($url1, json_encode(["key" => $key]));

        if (empty($res1)) {
            return [
                'step' => 'create_file_entry',
                'error' => 'failed',
                'debug_url' => $url1,
                'debug_payload' => ["key" => $key]
            ];
        }

        // STEP 2: INIT FILE (QUAN TRỌNG)
        $this->init_file($record_id, $key);

        // STEP 3: UPLOAD CONTENT
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

        // STEP 4: COMMIT
        $commitUrl = $this->apiurl . "records/$record_id/draft/files/$key/commit";
        $this->make_post_request($commitUrl, "{}");

        return [
            'step' => 'upload_complete',
            'code' => $code,
            'response' => $response
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

        curl_close($ch);

        return [
            'code' => $code,
            'body' => json_decode($response, true)
        ];
    }
}