<?php
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed."]);
    exit;
}

// Get the raw input JSON data from JS
$inputData = file_get_contents("php://input");
$decoded = json_decode($inputData, true);

if (!isset($decoded['places']) || !is_array($decoded['places'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid or empty places list."]);
    exit;
}

// Escape the JSON to pass safely via command line argument
$escapedData = escapeshellarg(json_encode($decoded['places']));

// Execute Python script (Make sure the path to python script is correct)
$command = "python3 " . __DIR__ . "/../scripts/tsp_solver.py " . $escapedData;
$output = shell_exec($command);

if ($output === null) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to execute optimization algorithm."]);
    exit;
}

// Return the optimized tour calculated by the Python backend
echo $output;