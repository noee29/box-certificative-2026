<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/TravelManager.php';

$travelManager = new \App\Models\TravelManager($bdd);
$token = $_GET['token'] ?? '';

$trip = $travelManager->getTripByToken($token);

if (!$trip) {
    http_response_code(404);
    echo "<h1>Itinerary not found.</h1>";
    exit;
}

// Privacy Rule Handling
if ($trip['privacy'] === 'private') {
    if (!isset($_SESSION['user_id'])) {
        // Redirect to login or block access
        http_response_code(401);
        echo "<h1>Authentication Required</h1><p>This trip is private. Please log in to view it.</p>"; // Privacy implementation
        exit;
    } elseif ($trip['user_id'] !== $_SESSION['user_id']) {
        http_response_code(403);
        echo "<h1>Unauthorized Access</h1>";
        exit;
    }
}

$route = json_decode($trip['ordered_route'], true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shared Trip — <?php echo htmlspecialchars($trip['title']); ?></title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>
    <h1><?php echo htmlspecialchars($trip['title']); ?></h1>
    <p class="meta">Itinéraire partagé &middot; <?= ucfirst($trip['privacy']) ?></p>
    <h2>Ordre des étapes</h2>
    <ul>
        <?php foreach ($route as $place): ?>
            <li><?php echo htmlspecialchars($place['name']); ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>