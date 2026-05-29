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

if (!function_exists('proc_open')) {
    http_response_code(500);
    echo json_encode(["message" => "proc_open is disabled in PHP."]);
    exit;
}

// On Windows (Laragon), prefer the project venv; fall back to py launcher
$venvPython = realpath(__DIR__ . '/../.venv/Scripts/python.exe');
if ($venvPython !== false) {
    $pythonBin = $venvPython;
} else {
    $pythonBin = (PHP_OS_FAMILY === 'Windows') ? 'py' : 'python3';
}

// Pass places via stdin to avoid shell-escaping issues on Windows
$proc = proc_open(
    escapeshellarg($pythonBin) . ' ' . escapeshellarg($algoPath),
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes
);

if (!is_resource($proc)) {
    http_response_code(500);
    echo json_encode(["message" => "Failed to start Python process."]);
    exit;
}

$payload = ["places" => $decoded['places']];
if (isset($decoded['max_hotels']) && is_numeric($decoded['max_hotels']) && $decoded['max_hotels'] > 0) {
    $payload['max_hotels'] = (int) $decoded['max_hotels'];
}
fwrite($pipes[0], json_encode($payload));
fclose($pipes[0]);

$output = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
proc_close($proc);

if (trim($output) === '') {
    http_response_code(500);
    echo json_encode(["message" => "Algorithm returned no output.", "debug" => substr($stderr, 0, 500)]);
    exit;
}

$result = json_decode(trim($output), true);
if ($result === null) {
    http_response_code(500);
    echo json_encode(["message" => "Algorithm returned invalid JSON.", "debug" => substr($output, 0, 500)]);
    exit;
}

echo json_encode($result);
