<?php

namespace local_inveniordm\service;

defined('MOODLE_INTERNAL') || die();
class file_service {

    public static function get_storage_dir(): string {
        global $CFG;

        $dir = $CFG->dataroot . '/local_inveniordm';

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        return $dir;
    }

    public static function save_file(string $content, string $filename): string {

        $dir = self::get_storage_dir();

        $path = $dir . '/' . time() . '_' . $filename;

        file_put_contents($path, $content);

        return $path;
    }

    public static function download_from_url(string $url): string {

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Host: localhost',
                'Authorization: Bearer scPx1LLmZkoCjM4dkH3tDa3n1KzfZfvBxhwdHATFa8ZN2SO0Sm9Ds8D8VcjV'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $data = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($data === false || $http >= 400) {
            debugging("DOWNLOAD FAIL | HTTP=$http | ERROR=$error | URL=$url");
            return '';
        }

        if (empty($data)) {
            debugging("EMPTY RESPONSE | HTTP=$http | URL=$url");
            return '';
        }

        return $data;
    }
}