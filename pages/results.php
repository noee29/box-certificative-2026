<?php

session_start();

if (!isset($_SESSION['id'])) {
    header("Location: auth/login.php");
    exit();
}

$travelId = isset($_GET['travel_id']) ? (int) $_GET['travel_id'] : 0;
$result   = $_SESSION["trip_result"] ?? null;
$places   = $result["ordered_places"] ?? [];
$total    = $result["total_distance_km"] ?? null;
$algo     = $result["algorithm"] ?? null;

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Resultats du tour</title>
</head>
<body>

    <h1>Resultats du tour</h1>

    <?php if (!$result || empty($places)): ?>

        <p>Aucun resultat disponible. Veuillez generer un tour.</p>
        <?php if ($travelId > 0): ?>
            <a href="generate.php?travel_id=<?= $travelId ?>">Generer le tour</a>
        <?php endif; ?>

    <?php else: ?>

        <p><strong>Algorithme :</strong> <?= htmlspecialchars($algo ?? 'N/A') ?></p>
        <p><strong>Distance totale :</strong> <?= htmlspecialchars((string) $total) ?> km</p>
        <p><strong>Nombre de lieux :</strong> <?= count($places) ?></p>

        <h2>Itineraire optimise</h2>
        <ol>
            <?php foreach ($places as $i => $place): ?>
                <li>
                    <strong><?= htmlspecialchars($place['name']) ?></strong>
                    &mdash; Lat : <?= htmlspecialchars((string) $place['lat']) ?>,
                    Lng : <?= htmlspecialchars((string) $place['lng']) ?>
                </li>
            <?php endforeach; ?>
            <li><em>Retour a : <strong><?= htmlspecialchars($places[0]['name']) ?></strong></em></li>
        </ol>

        <h2>Recapitulatif des etapes</h2>
        <table border="1" cellpadding="6" cellspacing="0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Lieu</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($places as $i => $place): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($place['name']) ?></td>
                        <td><?= htmlspecialchars((string) $place['lat']) ?></td>
                        <td><?= htmlspecialchars((string) $place['lng']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="2"><em>Retour a <?= htmlspecialchars($places[0]['name']) ?></em></td>
                    <td><?= htmlspecialchars((string) $places[0]['lat']) ?></td>
                    <td><?= htmlspecialchars((string) $places[0]['lng']) ?></td>
                </tr>
            </tbody>
        </table>

        <?php if ($travelId > 0): ?>
            <p>
                <a href="generate.php?travel_id=<?= $travelId ?>">Regenerer le tour</a>
                &nbsp;|&nbsp;
                <a href="dashboard.php">Retour au dashboard</a>
            </p>
        <?php else: ?>
            <a href="dashboard.php">Retour au dashboard</a>
        <?php endif; ?>

    <?php endif; ?>

</body>
</html>
