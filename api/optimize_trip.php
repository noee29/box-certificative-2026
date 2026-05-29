<?php
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed."]);
    exit;
}

$inputData = file_get_contents("php://input");
$decoded   = json_decode($inputData, true);

if (!isset($decoded['places']) || !is_array($decoded['places'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid or empty places list."]);
    exit;
}

$payload = ["places" => $decoded['places']];

if (isset($decoded['max_hotels']) && is_int($decoded['max_hotels']) && $decoded['max_hotels'] > 0) {
    $payload['max_hotels'] = $decoded['max_hotels'];
}

$ch = curl_init("http://localhost:5000/optimize");
curl_setopt($ch, CURLOPT_POST,           true);
curl_setopt($ch, CURLOPT_POSTFIELDS,     json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER,     ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT,        30);

$output   = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($output === false || $httpCode !== 200) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to connect to optimization engine."]);
    exit;
}

echo $output;
