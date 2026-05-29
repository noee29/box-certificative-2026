<?php
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed."]);
    exit;
}

$inputData = file_get_contents("php://input");
$decoded   = json_decode($inputData, true);

if (!isset($decoded['places']) || !is_array($decoded['places']) || count($decoded['places']) < 2) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid or empty places list (minimum 2 places required)."]);
    exit;
}

$algoPath = realpath(__DIR__ . '/../algo/algo.py');
if ($algoPath === false) {
    http_response_code(500);
    echo json_encode(["message" => "Algorithm file not found."]);
    exit;
}

// On Windows (Laragon), prefer the project venv; fall back to system python
$venvPython = realpath(__DIR__ . '/../.venv/Scripts/python.exe');
$pythonBin  = ($venvPython !== false) ? $venvPython : 'python';

$command = escapeshellarg($pythonBin)
    . ' ' . escapeshellarg($algoPath)
    . ' ' . escapeshellarg(json_encode($decoded['places']))
    . ' 2>&1';

$output = shell_exec($command);

if ($output === null || trim($output) === '') {
    http_response_code(500);
    echo json_encode(["message" => "Algorithm execution failed (no output). Check that Python is installed."]);
    exit;
}

$result = json_decode(trim($output), true);
if ($result === null) {
    http_response_code(500);
    // Surface the raw output to help debug path/Python issues
    echo json_encode(["message" => "Algorithm returned invalid JSON.", "debug" => substr(trim($output), 0, 500)]);
    exit;
}

echo json_encode($result);
