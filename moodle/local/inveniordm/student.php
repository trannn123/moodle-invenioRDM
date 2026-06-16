<?php

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/controller/student_controller.php');
$controller = new student_controller();
$controller->search();