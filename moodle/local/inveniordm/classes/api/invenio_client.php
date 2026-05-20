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

        $this->apiurl = 'https://ctu-it-rdm-frontend-1/api/';

        $this->hostheader = 'localhost';

        $this->token = get_config(
            'local_inveniordm',
            'apitoken'
        );
    }

    private function make_request(
        string $url,
        string $method = 'GET'
    ): array {

        $ch = curl_init();

        $headers = [
            'Accept: application/json',
            'Host: localhost'
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

        if ($httpcode !== 200) {

            debugging(
                'HTTP Error ' . $httpcode
            );

            return [];
        }

        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
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
}