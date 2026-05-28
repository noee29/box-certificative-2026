<?php
header("Content-Type: application/json");
require_once __DIR__ . 'Services/GeocodingService.php';

use App\Services\GeocodingService;

if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing query parameter 'q'."]);
    exit;
}

$service = new GeocodingService();
$result = $service->searchCoordinates($_GET['q']);

if ($result === false) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Location not found."]); // IHM clear error message
} else {
    echo json_encode(["status" => "success", "data" => $result]);
}