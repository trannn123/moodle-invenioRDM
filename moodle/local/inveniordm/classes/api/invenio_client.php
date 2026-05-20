<?php

namespace local_inveniordm\api;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

class invenio_client
{
    private string $apiurl;
    private string $token;
    private string $host_header;

    public function __construct()
    {
        // Dùng IP của frontend và thêm host header
        $this->apiurl = 'https://172.18.0.2/api/';
        $this->host_header = 'localhost';
        $this->token = get_config('local_inveniordm', 'apitoken');
    }

    private function make_request(string $url, string $method = 'GET'): array
    {
        $ch = curl_init();

        $headers = [
            'Accept: application/json',
            'Host: ' . $this->host_header
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

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            debugging('cURL Error: ' . curl_error($ch));
            curl_close($ch);
            return [];
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            debugging("HTTP Error {$httpCode}");
            return [];
        }

        return json_decode($response, true) ?? [];
    }

    public function get_records(string $query = ''): array
    {
        $url = $this->apiurl . 'records';

        if (!empty($query)) {
            $url .= '?q=' . urlencode($query);
        } else {
            $url .= '?q=*';
        }

        return $this->make_request($url);
    }

    public function get_record(string $id): array
    {
        $url = $this->apiurl . 'records/' . $id;
        return $this->make_request($url);
    }
}