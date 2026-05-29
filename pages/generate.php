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
        $rootPathEncoded = str_replace(' ', '%20', $rootPath);
        $apiUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . $rootPathEncoded . '/api/generate_trip.php';

        $context = stream_context_create([
            "http" => [
                "method"        => "POST",
                "header"        => "Content-Type: application/json\r\n",
                "content"       => $payload,
                "ignore_errors" => true,
            ]
        ]);

        $response   = @file_get_contents($apiUrl, false, $context);
        $result     = $response ? json_decode($response, true) : null;
        $statusLine = $http_response_header[0] ?? '';

        if (!$result || !isset($result["total_distance_km"])) {
            if ($result && isset($result["message"])) {
                $message = $result["message"];
                if (isset($result["debug"])) $message .= " — " . $result["debug"];
            } elseif ($response) {
                $message = $response;
            } else {
                $error   = error_get_last();
                $message = $error["message"] ?? "Erreur lors de la generation du tour.";
            }
            if ($statusLine !== '') $message .= " (" . $statusLine . ")";
        } else {
            $insert = $bdd->prepare("INSERT INTO results (travel_id, distance_totale) VALUES (?, ?)");
            $saved = $insert->execute([$travelId, $result["total_distance_km"]]);
            if (!$saved) {
                $message = "Erreur lors de l'enregistrement du tour.";
            } else {
            $_SESSION["trip_result"]          = $result;
            $_SESSION["trip_result_travel_id"] = $travelId;
            header("Location: results.php?travel_id=" . $travelId);
            exit();
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Générer le tour</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>

    <h1>Générer le tour</h1>

    <?php if ($message): ?>
        <p class="msg error"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <h2>Villes sélectionnées (<?= count($places) ?>)</h2>
    <ul style="list-style:none;padding:0;margin-bottom:1.2rem">
        <?php foreach ($places as $place): ?>
            <li style="padding:.3rem 0;border-bottom:1px solid var(--green-light);font-size:.93rem">
                <?= htmlspecialchars($place['nom']) ?>
                <span style="color:var(--muted);font-size:.8rem;margin-left:.5rem"><?= round($place['latitude'],4) ?>, <?= round($place['longitude'],4) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>

    <form action="" method="POST">
        <label for="max_hotels">Nombre maximum d'hôtels <span style="color:var(--muted)">(laisser vide = automatique)</span></label>
        <input type="number" id="max_hotels" name="max_hotels" min="1" max="<?= count($places) ?>" placeholder="auto" style="max-width:200px">
        <button type="submit">Générer le tour optimisé</button>
    </form>

    <div class="nav">
        <a href="places.php?travel_id=<?= $travelId ?>">Modifier les lieux</a> &middot;
        <a href="dashboard.php">Dashboard</a>
    </div>

</body>
</html>
