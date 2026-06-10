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
}