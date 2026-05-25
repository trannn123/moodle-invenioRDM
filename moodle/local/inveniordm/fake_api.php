<?php

header('Content-Type: application/json');

$jsonpath = __DIR__ . '/mock_records.json';

if (!file_exists($jsonpath)) {

    echo json_encode([
        'error' => 'mock_records.json not found'
    ]);

    exit;
}

echo file_get_contents($jsonpath);