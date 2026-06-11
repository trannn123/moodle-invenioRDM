<?php

defined('MOODLE_INTERNAL') || die();

class dashboard_controller {
    public function index() {
        global $PAGE, $CFG, $OUTPUT;
        require_login();
        $context = context_system::instance();

        $PAGE->set_url(new moodle_url('/local/inveniordm/index.php'));
        $PAGE->set_context($context);
        $PAGE->set_title('InvenioRDM Dashboard');
        $PAGE->set_heading('InvenioRDM Integration');
        $PAGE->requires->css(
            new moodle_url(
                '/local/inveniordm/styles/dashboard.css'
            )
        );
        $role = 'student';

        if (is_siteadmin()) {
            $role = 'admin';

        } else if (has_capability('local/inveniordm:upload', $context)) {
            $role = 'lecturer';
        }

        $data = [
            'role' => $role,
            'is_student' => ($role === 'student'),
            'is_lecturer' => ($role === 'lecturer'),
            'is_admin' => ($role === 'admin'),
            'wwwroot' => $CFG->wwwroot,
            'cancreatecourse' => has_capability(
                'local/inveniordm:createcourse',
                $context
            )
        ];

        echo $OUTPUT->header();
        echo $OUTPUT->render_from_template(
            'local_inveniordm/dashboard',
            $data
        );
        echo $OUTPUT->footer();
    }
}