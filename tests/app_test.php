<?php

// Main app functional tests (excluding auth).
session_start();
require("../config/database.php");

if (!isset($_SESSION['id'])) {
    header("Location: ../pages/auth/login.php");
    exit();
}

$userId = (int) $_SESSION['id'];
$message = "";
$travelId = 0;

if (isset($_POST['travel_id'])) {
    $travelId = (int) $_POST['travel_id'];
} elseif (isset($_SESSION['test_travel_id'])) {
    $travelId = (int) $_SESSION['test_travel_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_travel') {
        $title = trim($_POST['title'] ?? '');
        $visibility = $_POST['visibility'] ?? 'private';
        if ($title === '') {
            $message = "Title is required.";
        } else {
            $stmt = $bdd->prepare("INSERT INTO travels (user_id, titre, statut) VALUES (?, ?, ?)");
            $ok = $stmt->execute([$userId, $title, $visibility]);
            if ($ok) {
                $travelId = (int) $bdd->lastInsertId();
                $_SESSION['test_travel_id'] = $travelId;
                $message = "Travel created: ID " . $travelId;
            } else {
                $message = "Failed to create travel.";
            }
        }
    }

    if ($action === 'add_place') {
        $city = trim($_POST['city'] ?? '');
        if ($travelId <= 0) {
            $message = "Travel ID is required.";
        } elseif ($city === '') {
            $message = "City is required.";
        } else {
            $check = $bdd->prepare("SELECT id FROM travels WHERE id = ? AND user_id = ?");
            $check->execute([$travelId, $userId]);
            $travel = $check->fetch();
            if (!$travel) {
                $message = "Travel ID not found for this user.";
                return;
            }
            $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($city) . "&limit=1";
            $context = stream_context_create([
                "http" => ["header" => "User-Agent: BoxCertificative2026/1.0\r\n"]
            ]);
            $response = @file_get_contents($url, false, $context);
            $data = $response ? json_decode($response, true) : [];

            if (!$data || !isset($data[0]["lat"], $data[0]["lon"])) {
                $message = "City not found by Nominatim.";
            } else {
                $stmt = $bdd->prepare("INSERT INTO places (travel_id, nom, latitude, longitude) VALUES (?, ?, ?, ?)");
                $ok = $stmt->execute([$travelId, $city, $data[0]["lat"], $data[0]["lon"]]);
                if ($ok) {
                    $message = "Place added.";
                } else {
                    $message = "Failed to add place.";
                }
            }
        }
    }

    if ($action === 'generate_and_save') {
        if ($travelId <= 0) {
            $message = "Travel ID is required.";
        } else {
            $stmt = $bdd->prepare("SELECT id, nom, latitude, longitude FROM places WHERE travel_id = ?");
            $stmt->execute([$travelId]);
            $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($places) < 2) {
                $message = "Add at least 2 places.";
            } else {
                $payloadPlaces = [];
                foreach ($places as $place) {
                    $payloadPlaces[] = [
                        "id" => (int) $place['id'],
                        "name" => $place['nom'],
                        "lat" => (float) $place['latitude'],
                        "lng" => (float) $place['longitude'],
                    ];
                    }
                $payload = json_encode(["places" => $payloadPlaces]);
                $context = stream_context_create([
                    "http" => [
                        "method" => "POST",
                        "header" => "Content-Type: application/json\r\n",
                        "content" => $payload,
                        "ignore_errors" => true,
                    ]
                ]);

                $scheme = 'http';
                if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
                    $scheme = 'https';
                }
                $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
                $rootPath = preg_replace('#/tests$#', '', $basePath);
                 $rootPath = str_replace(' ', '%20', $rootPath);
                 $apiUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . $rootPath . '/api/generate_trip.php';

                 $response = @file_get_contents($apiUrl, false, $context);
                 $result = $response ? json_decode($response, true) : null;

                 if (!$result || !isset($result['total_distance_km'])) {
                     if ($result && isset($result['message'])) {
                         $message = $result['message'];
                     } elseif ($response) {
                         $message = $response;
                     } else {
                         $message = 'Generation failed.';
                     }
                 } else {
                     $stmt = $bdd->prepare("INSERT INTO results (travel_id, distance_totale) VALUES (?, ?)");
                     $ok = $stmt->execute([$travelId, $result['total_distance_km']]);
                     if ($ok) {
                         $message = "Generated and saved to results.";
                     } else {
                         $message = "Generated but failed to save.";
                    }
                }
            }
        }
     }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Main Feature Tests</title>
</head>
<body>

    <h1>Main Feature Tests</h1>

    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <h2>Create Travel</h2>
    <form method="POST">
        <input type="hidden" name="action" value="create_travel">
        <input type="text" name="title" placeholder="Travel title">
        <select name="visibility">
            <option value="private">Private</option>
            <option value="public">Public</option>
        </select>
        <button type="submit">Create</button>
    </form>

    <h2>Add Place</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add_place">
        <input type="number" name="travel_id" placeholder="Travel ID" value="<?php echo $travelId; ?>">
        <input type="text" name="city" placeholder="City name">
        <button type="submit">Add Place</button>
    </form>

    <h2>Generate Tour</h2>
    <form method="POST">
        <input type="hidden" name="action" value="generate_and_save">
        <input type="number" name="travel_id" placeholder="Travel ID" value="<?php echo $travelId; ?>">
        <button type="submit">Generate + Save</button>
    </form>

</body>
</html>
