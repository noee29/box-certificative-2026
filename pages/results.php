<?php

session_start();

if (!isset($_SESSION['id'])) {
    header("Location: auth/login.php");
    exit();
}

$travelId = isset($_GET['travel_id']) ? (int) $_GET['travel_id'] : 0;
$result = $_SESSION["trip_result"] ?? null;

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Resultats</title>
</head>
<body>

    <h1>Resultats</h1>

    <?php if (!$result): ?>
        <p>Aucun resultat disponible. Veuillez generer un tour.</p>
        <a href="generate.php?travel_id=<?= $travelId ?>">Generer le tour</a>
    <?php else: ?>
        <p>Distance totale (km) : <?= $result['total_distance_km'] ?? 'N/A' ?></p>

        <h2>Ordre des lieux</h2>
        <ol>
            <?php foreach (($result['ordered_places'] ?? []) as $place): ?>
                <li><?= $place['name'] ?> (<?= $place['lat'] ?>, <?= $place['lng'] ?>)</li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>

    <a href="dashboard.php">Retour au dashboard</a>

</body>
</html>
