<?php
$file = __DIR__ . '/visits.json';

$data = array();
if (file_exists($file)) {
    $raw = file_get_contents($file);
    if ($raw !== false) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }
}

$today = date('Y-m-d');
$data[$today] = isset($data[$today]) ? $data[$today] + 1 : 1;

$result = file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
if ($result === false) {
    http_response_code(500);
    exit;
}

http_response_code(204);
