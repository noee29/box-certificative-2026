<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/TravelManager.php';

$travelManager = new \App\Models\TravelManager($bdd);
$token = $_GET['token'] ?? '';

$trip = $travelManager->getTripByToken($token);

if (!$trip || empty($trip['ordered_route'])) {
    http_response_code(404);
    include __DIR__ . '/../public/assets/css/style.css';
    echo '<p style="font-family:sans-serif;padding:2rem">Itinéraire introuvable ou pas encore généré.</p>';
    exit;
}

// Privacy check: private trips require the owner to be logged in
if ($trip['statut'] === 'private') {
    if (!isset($_SESSION['id'])) {
        header("Location: ../pages/auth/login.php");
        exit;
    }
    if ((int)$trip['user_id'] !== (int)$_SESSION['id']) {
        http_response_code(403);
        echo '<p style="font-family:sans-serif;padding:2rem">Accès refusé. Ce voyage est privé.</p>';
        exit;
    }
}

$result    = json_decode($trip['ordered_route'], true) ?? [];
$clusters  = $result['clusters']             ?? [];
$hotels    = $result['hotels_circuit']       ?? [];
$totalKm   = $result['total_distance_km']    ?? 0;
$circuitKm = $result['circuit_distance_km']  ?? 0;
$dayKm     = $result['day_trips_distance_km'] ?? 0;
$k         = $result['optimal_k']            ?? count($clusters);

// Match clusters to hotels_circuit order
$orderedClusters = [];
$byId   = [];
$byName = [];
foreach ($clusters as $cluster) {
    $h = $cluster['hotel'];
    if (isset($h['id']))   $byId[(int)$h['id']]  = $cluster;
    if (isset($h['name'])) $byName[$h['name']]   = $cluster;
}
foreach ($hotels as $hotel) {
    if (isset($hotel['id']) && isset($byId[(int)$hotel['id']])) {
        $orderedClusters[] = $byId[(int)$hotel['id']];
    } elseif (isset($hotel['name']) && isset($byName[$hotel['name']])) {
        $orderedClusters[] = $byName[$hotel['name']];
    }
}

// Fallback: if matching failed, show clusters in original order
if (empty($orderedClusters) && !empty($clusters)) {
    $orderedClusters = $clusters;
    $hotels = array_column($clusters, 'hotel');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($trip['titre']) ?> — Itinéraire partagé</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>

<h1><?= htmlspecialchars($trip['titre']) ?></h1>
<p class="meta">
    Itinéraire partagé &middot;
    <span class="badge <?= $trip['statut'] === 'public' ? 'public' : '' ?>"><?= ucfirst($trip['statut']) ?></span>
</p>

<?php if (empty($orderedClusters)): ?>
    <p style="color:var(--muted)">Aucune donnée d'itinéraire disponible. Régénérez le tour depuis votre compte.</p>
<?php else: ?>

<div class="summary-box">
    <?= $k ?> hôtel<?= $k > 1 ? 's' : '' ?> &middot; <?= $totalKm ?> km au total
    <span style="margin-left:.8rem;color:var(--muted);font-size:.85rem">
        circuit <?= $circuitKm ?> km &middot; excursions <?= $dayKm ?> km
    </span>
</div>

<h2>Itinéraire</h2>

<?php foreach ($orderedClusters as $idx => $cluster):
    $hotel     = $cluster['hotel'];
    $dayTrips  = $cluster['day_trips'] ?? [];
    $isLast    = ($idx === count($orderedClusters) - 1);
    $nextHotel = !$isLast ? ($hotels[$idx + 1] ?? $hotels[0]) : $hotels[0];
?>
<div class="hotel-block">
    <div class="hotel-name">
        <?= htmlspecialchars($hotel['name']) ?>
        <span class="hotel-tag">hôtel</span>
    </div>

    <?php if (empty($dayTrips)): ?>
        <p style="font-size:.85rem;color:var(--muted)">Pas d'excursion depuis cet hôtel.</p>
    <?php else: ?>
        <ul class="day-list">
        <?php foreach ($dayTrips as $city): ?>
            <li><span class="day-name"><?= htmlspecialchars($city['name']) ?></span></li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <p class="leg">
        <?php if (!$isLast): ?>
            vers <?= htmlspecialchars($nextHotel['name'] ?? '') ?>
        <?php else: ?>
            retour à <?= htmlspecialchars($hotels[0]['name'] ?? '') ?>
        <?php endif; ?>
    </p>
</div>
<?php endforeach; ?>

<?php endif; ?>

<div class="nav">
    <?php if (isset($_SESSION['id'])): ?>
        <a href="dashboard.php">Dashboard</a>
    <?php else: ?>
        <a href="auth/login.php">Se connecter</a>
    <?php endif; ?>
</div>

</body>
</html>
