<?php
require_once('../../config.php');
require_login();
global $OUTPUT;
echo $OUTPUT->header();
echo "<h2>Testing different API endpoints</h2>";

$urls = [
    'http://host.docker.internal:5000/api/records?q=*',
    'http://host.docker.internal/api/records?q=*',
    'http://172.17.0.1:5000/api/records?q=*',
    'http://localhost:5000/api/records?q=*',
    'http://invenio:5000/api/records?q=*',  // Nếu cùng docker network
];

foreach ($urls as $url) {
    echo "<h3>Testing: " . htmlspecialchars($url) . "</h3>";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    echo "HTTP Code: " . $http_code . "<br>";
    if ($error) {
        echo "Error: " . $error . "<br>";
    }
    if ($http_code == 200) {
        echo "<span style='color:green'>✓ WORKING!</span><br>";
        $data = json_decode($response, true);
        $count = count($data['hits']['hits'] ?? []);
        echo "Found {$count} records<br>";
        break;
    } else {
        echo "<span style='color:red'>✗ NOT WORKING</span><br>";
    }
    echo "<hr>";
}
echo $OUTPUT->footer();