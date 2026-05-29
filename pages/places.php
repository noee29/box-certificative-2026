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
$manager = new PlaceManager($bdd);
$message = "";

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $city = trim($_POST['city'] ?? "");
    $deleteId = isset($_POST['delete_id']) ? (int) $_POST['delete_id'] : 0;
    if ($travelId <= 0) {
        $message = "Voyage introuvable.";
    } elseif ($deleteId > 0) {
        $deleted = $manager->deletePlace($deleteId);
        $message = $deleted ? "Lieu supprime." : "Erreur lors de la suppression.";
    } elseif ($city === "") {
        $message = "Veuillez saisir une ville.";
    } else {
        $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($city) . "&limit=1";
        $context = stream_context_create([
            "http" => ["header" => "User-Agent: BoxCertificative2026/1.0\r\n"]
        ]);
        $response = @file_get_contents($url, false, $context);
        $data = $response ? json_decode($response, true) : [];

        if ($data && isset($data[0]["lat"], $data[0]["lon"])) {
            $manager->savePlace($travelId, $city, (float) $data[0]["lat"], (float) $data[0]["lon"]);
            $message = "Ville enregistree.";
        } else {
            $message = "Ville introuvable.";
        }
    }
}

$places = $travelId > 0 ? $manager->getPlacesByTravel($travelId) : [];

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gerer les lieux</title>
</head>
<body>

    <h1>Gerer les lieux</h1>

    <?php if ($travelId > 0): ?>
        <p>Voyage ID : <?= $travelId ?></p>
    <?php endif; ?>

    <?php if ($message): ?>
        <p><?= $message ?></p>
    <?php endif; ?>

    <form action="" method="POST">
        <input type="text" name="city" placeholder="Nom de la ville">
        <button type="submit">Ajouter</button>
    </form>

    <h2>Mes lieux</h2>
    <ul>
        <?php foreach ($places as $place): ?>
            <li>
                <?= htmlspecialchars($place['nom']) ?> (<?= htmlspecialchars($place['latitude']) ?>, <?= htmlspecialchars($place['longitude']) ?>)
                <form action="" method="POST" style="display:inline">
                    <input type="hidden" name="delete_id" value="<?= (int)$place['id'] ?>">
                    <button type="submit" onclick="return confirm('Supprimer ce lieu ?')">Supprimer</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>

    <a href="dashboard.php">Retour au dashboard</a>

</body>
</html>
