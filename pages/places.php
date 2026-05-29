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
    <title>Gérer les lieux</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>

    <h1>Gérer les lieux</h1>

    <?php if ($message): ?>
        <p class="msg <?= str_contains($message, 'trouvable') || str_contains($message, 'introuvable') || str_contains($message, 'Erreur') ? 'error' : 'ok' ?>">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <form action="" method="POST">
        <label>Ajouter une ville</label>
        <input type="text" name="city" placeholder="Ex: Lyon, Tokyo, New York...">
        <button type="submit">Ajouter</button>
    </form>

    <h2>Lieux ajoutés (<?= count($places) ?>)</h2>

    <?php if (empty($places)): ?>
        <p style="color:var(--muted)">Aucun lieu ajouté pour l'instant.</p>
    <?php else: ?>
    <ul style="list-style:none;padding:0">
        <?php foreach ($places as $place): ?>
            <li style="display:flex;align-items:center;gap:.8rem;padding:.4rem 0;border-bottom:1px solid var(--green-light)">
                <span style="flex:1"><?= htmlspecialchars($place['nom']) ?></span>
                <span style="font-size:.8rem;color:var(--muted)"><?= round($place['latitude'],4) ?>, <?= round($place['longitude'],4) ?></span>
                <form action="" method="POST" style="margin:0">
                    <input type="hidden" name="delete_id" value="<?= (int)$place['id'] ?>">
                    <button type="submit" class="danger" onclick="return confirm('Supprimer ce lieu ?')">Supprimer</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <div class="nav" style="margin-top:1.2rem">
        <a href="generate.php?travel_id=<?= $travelId ?>">Générer le tour</a> &middot;
        <a href="dashboard.php">Dashboard</a>
    </div>

</body>
</html>
