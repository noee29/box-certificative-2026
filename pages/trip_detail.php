<?php
// Assume session_start() and authentication check are done before
require_once __DIR__ . '/../src/Models/TravelManager.php';
// $db must be defined via a connection script

$travelManager = new \App\Models\TravelManager($db);
// Assuming trip ID or token is passed via GET
$token = $_GET['token'] ?? '';

$trip = $travelManager->getTripByToken($token);

if (!$trip) {
    echo "<h1>Trip not found</h1>";
    exit;
}

// Security Check: If it is private, ensure it belongs to the logged-in user
if ($trip['privacy'] === 'private' && $trip['user_id'] !== $_SESSION['user_id']) {
    http_response_code(403);
    echo "<h1>Access Denied. This trip is private.</h1>"; // Secure Check
    exit;
}

$route = json_decode($trip['ordered_route'], true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trip Details - <?php echo htmlspecialchars($trip['title']); ?></title>
</head>
<body>
    <h1>✈️ <?php echo htmlspecialchars($trip['title']); ?></h1>
    <p>Privacy status: <strong><?php echo ucfirst($trip['privacy']); ?></strong></p>
    <p>Share Link: <code>http://localhost/pages/view_trip.php?token=<?php echo $trip['share_token']; ?></code></p>
    
    <h2>Optimized Itinerary Route:</h2>
    <ol>
        <?php foreach ($route as $index => $place): ?>
            <li><?php echo htmlspecialchars($place['name']); ?> (Lat: <?php echo $place['lat']; ?>, Lon: <?php echo $place['lon']; ?>)</li>
        <?php endforeach; ?>
        <li>↩️ Back to: <?php echo htmlspecialchars($route[0]['name']); ?></li>
    </ol>
</body>
</html>