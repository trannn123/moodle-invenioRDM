<?php

defined('MOODLE_INTERNAL') || die();
/**
 * Moodle file serving callback
 */
function local_inveniordm_pluginfile(
    $course,
    $cm,
    $context,
    $filearea,
    $args,
    $forcedownload,
    array $options = []
) {
    global $CFG;

    // Chỉ cho phép system context
    if ($context->contextlevel != CONTEXT_SYSTEM &&
        $context->contextlevel != CONTEXT_COURSE) {
        return false;
    }

    // Lấy itemid + filename
    $itemid = array_shift($args);
    $filename = array_pop($args);

    $filepath = $CFG->dataroot . "/inveniordm/$itemid/$filename";

    if (!file_exists($filepath)) {
        return false;
    }

    return send_file($filepath, $filename);
}

function local_inveniordm_extend_navigation_user(
    $navigation,
    $user,
    $usercontext,
    $course,
    $context
) {
    $navigation->add(
        'InvenioRDM',
        new moodle_url(
            '/local/inveniordm/index.php'
        )
    );
}