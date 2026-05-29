<?php

session_start();
require("../config/database.php");
require_once("../models/PlaceManager.php");

use App\Models\PlaceManager;

if (!isset($_SESSION['id'])) {
    header("Location: auth/login.php");
    exit();
}

$travelId = isset($_GET['travel_id']) ? (int) $_GET['travel_id'] : 0;
$manager  = new PlaceManager($bdd);
$places   = $travelId > 0 ? $manager->getPlacesByTravel($travelId) : [];
$message  = "";

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if ($travelId <= 0) {
        $message = "Voyage introuvable.";
    } elseif (count($places) < 2) {
        $message = "Ajoutez au moins 2 lieux avant de generer le tour.";
    } else {
        $payloadPlaces = array_map(function ($place) {
            return [
                "id"   => (int)   $place["id"],
                "name" => $place["nom"],
                "lat"  => (float) $place["latitude"],
                "lng"  => (float) $place["longitude"],
            ];
        }, $places);

        $data = ["places" => $payloadPlaces];

        // Optional max hotels constraint
        if (!empty($_POST['max_hotels']) && ctype_digit($_POST['max_hotels']) && (int)$_POST['max_hotels'] > 0) {
            $data['max_hotels'] = (int) $_POST['max_hotels'];
        }

        $payload  = json_encode($data);
        $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $rootPath = preg_replace('#/pages$#', '', $basePath);
        $apiUrl   = $scheme . '://' . $_SERVER['HTTP_HOST'] . $rootPath . '/api/generate_trip.php';

        $context = stream_context_create([
            "http" => [
                "method"  => "POST",
                "header"  => "Content-Type: application/json\r\n",
                "content" => $payload,
            ]
        ]);

        $response = @file_get_contents($apiUrl, false, $context);
        $result   = $response ? json_decode($response, true) : null;

        if (!$result || !isset($result["total_distance_km"])) {
            $message = $result["message"] ?? "Erreur lors de la generation du tour.";
        } else {
            $_SESSION["trip_result"]          = $result;
            $_SESSION["trip_result_travel_id"] = $travelId;
            header("Location: results.php?travel_id=" . $travelId);
            exit();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Generer le tour</title>
</head>
<body>

    <h1>Generer le tour</h1>

    <?php if ($message): ?>
        <p style="color:red"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <h2>Mes lieux (<?= count($places) ?>)</h2>
    <ul>
        <?php foreach ($places as $place): ?>
            <li><?= htmlspecialchars($place['nom']) ?> (<?= $place['latitude'] ?>, <?= $place['longitude'] ?>)</li>
        <?php endforeach; ?>
    </ul>

    <form action="" method="POST">
        <p>
            <label for="max_hotels">Nombre maximum d'hotels (laisser vide = automatique) :</label><br>
            <input type="number" id="max_hotels" name="max_hotels" min="1" max="<?= count($places) ?>" placeholder="auto">
        </p>
        <button type="submit">Generer le tour optimise</button>
    </form>

    <a href="dashboard.php">Retour au dashboard</a>

</body>
</html>
