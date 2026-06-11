<?php

defined('MOODLE_INTERNAL') || die();

class block_inveniordm extends block_base {

    public function init() {
        $this->title = get_string(
            'pluginname',
            'block_inveniordm'
        );
    }

    public function applicable_formats() {
        return [
            'all' => true
        ];
    }

    public function get_content() {

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        $url = new moodle_url(
            '/local/inveniordm/index.php'
        );

        $this->content->text = html_writer::link(
            $url,
            'Open Repository',
            ['class' => 'btn btn-primary']
        );

        return $this->content;
    }
}