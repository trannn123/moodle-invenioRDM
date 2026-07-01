<?php

global $CFG;
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/controller/lecturer_controller.php');
require_once(__DIR__ . '/../classes/service/submission_service.php');

require_login();

$controller = new lecturer_controller();

$controller->publish_submission();