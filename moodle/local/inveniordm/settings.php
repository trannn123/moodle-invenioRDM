<?php

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_inveniordm',
        'InvenioRDM'
    );

    $settings->add(new admin_setting_configtext(
        'local_inveniordm/apiurl',
        'API URL',
        'Invenio API URL',
        'http://host.docker.internal:5001/api'
    ));

    $settings->add(new admin_setting_configtext(
        'local_inveniordm/apitoken',
        'API Token',
        'Invenio API Token',
        ''
    ));
    $ADMIN->add('localplugins', $settings);
}