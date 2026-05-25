<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
$urls = [
    'http://host.docker.internal:5000/api/records?q=*',
    'http://172.17.0.1:5000/api/records?q=*',
    'http://localhost:5000/api/records?q=*',
];

foreach ($urls as $url) {
    echo "Testing: $url\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "HTTP Code: $httpcode\n";
    if ($httpcode == 200) {
        echo "✓ WORKING!\n";
        $data = json_decode($response, true);
        print_r(array_keys($data));
        break;
    } else {
        echo "✗ NOT WORKING\n";
    }
    echo "\n";
}