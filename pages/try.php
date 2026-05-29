<?php
session_start();
require_once "../config/database.php";

// Guest city list stored in session
if (!isset($_SESSION['guest_places'])) {
    $_SESSION['guest_places'] = [];
}

$message = "";
$result  = $_SESSION['guest_result'] ?? null;

// ── Actions ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add a city
    if ($action === 'add') {
        $city = trim($_POST['city'] ?? '');
        if ($city === '') {
            $message = "Veuillez saisir une ville.";
        } else {
            $url = "https://nominatim.openstreetmap.org/search?format=json&q="
                   . urlencode($city) . "&limit=1";
            $ctx = stream_context_create([
                "http" => ["header" => "User-Agent: BoxCertificative2026/1.0\r\n"]
            ]);
            $resp = @file_get_contents($url, false, $ctx);
            $data = $resp ? json_decode($resp, true) : [];

            if ($data && isset($data[0]['lat'], $data[0]['lon'])) {
                $id = count($_SESSION['guest_places']) + 1;
                $_SESSION['guest_places'][] = [
                    "id"   => $id,
                    "name" => $data[0]['display_name']
                                ? explode(',', $data[0]['display_name'])[0]
                                : $city,
                    "lat"  => (float) $data[0]['lat'],
                    "lng"  => (float) $data[0]['lon'],
                ];
                $message = "Ville ajoutée.";
            } else {
                $message = "Ville introuvable. Essayez \"Lyon, France\" ou \"Tokyo, Japan\".";
            }
        }
    }

    // Remove a city
    if ($action === 'remove') {
        $idx = (int) ($_POST['idx'] ?? -1);
        if (isset($_SESSION['guest_places'][$idx])) {
            array_splice($_SESSION['guest_places'], $idx, 1);
            // Re-index ids
            foreach ($_SESSION['guest_places'] as $i => &$p) {
                $p['id'] = $i + 1;
            }
            unset($p);
        }
        $_SESSION['guest_result'] = null;
    }

    // Reset everything
    if ($action === 'reset') {
        $_SESSION['guest_places'] = [];
        $_SESSION['guest_result'] = null;
    }

    // Generate the tour
    if ($action === 'generate') {
        $places = $_SESSION['guest_places'];
        if (count($places) < 2) {
            $message = "Ajoutez au moins 2 villes avant de générer le tour.";
        } else {
            $data = ["places" => $places];
            if (!empty($_POST['max_hotels']) && ctype_digit($_POST['max_hotels'])) {
                $data['max_hotels'] = (int) $_POST['max_hotels'];
            }

            $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            $rootPath = preg_replace('#/pages$#', '', $basePath);
            $rootPathEncoded = str_replace(' ', '%20', $rootPath);
            $apiUrl   = $scheme . '://' . $_SERVER['HTTP_HOST'] . $rootPathEncoded . '/api/generate_trip.php';

            $ctx = stream_context_create([
                "http" => [
                    "method"        => "POST",
                    "header"        => "Content-Type: application/json\r\n",
                    "content"       => json_encode($data),
                    "ignore_errors" => true,
                ]
            ]);

            $response = @file_get_contents($apiUrl, false, $ctx);
            $res      = $response ? json_decode($response, true) : null;

            if (!$res || !isset($res['total_distance_km'])) {
                $message = $res['message'] ?? "Erreur lors de la génération.";
            } else {
                $_SESSION['guest_result'] = $res;
                $result = $res;
            }
        }
    }
}

$places  = $_SESSION['guest_places'];

// Build ordered clusters for display
$orderedClusters = [];
if ($result) {
    $clusters = $result['clusters']       ?? [];
    $hotels   = $result['hotels_circuit'] ?? [];
    $byId = []; $byName = [];
    foreach ($clusters as $c) {
        $h = $c['hotel'];
        if (isset($h['id']))   $byId[(int)$h['id']]  = $c;
        if (isset($h['name'])) $byName[$h['name']]   = $c;
    }
    foreach ($hotels as $hotel) {
        if (isset($hotel['id']) && isset($byId[(int)$hotel['id']])) {
            $orderedClusters[] = $byId[(int)$hotel['id']];
        } elseif (isset($hotel['name']) && isset($byName[$hotel['name']])) {
            $orderedClusters[] = $byName[$hotel['name']];
        }
    }
    if (empty($orderedClusters) && !empty($clusters)) {
        $orderedClusters = $clusters;
        $hotels = array_column($clusters, 'hotel');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Essayer — Planification de voyages</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>

<div class="topbar">
    <span class="brand">Planification de voyages</span>
    <span>
        <a href="auth/login.php" style="font-size:.85rem;color:var(--muted)">Se connecter</a>
        &nbsp;&middot;&nbsp;
        <a href="auth/register.php" style="font-size:.85rem;color:var(--muted)">Créer un compte</a>
    </span>
</div>

<h1>Essayer sans compte</h1>
<p style="color:var(--muted);font-size:.9rem;margin-bottom:1.5rem">
    Votre itinéraire n'est pas sauvegardé.
    <a href="auth/register.php">Créez un compte</a> pour conserver vos voyages et les partager.
</p>

<?php if ($message): ?>
    <p class="msg <?= str_contains($message, 'ajoutée') ? 'ok' : 'error' ?>"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<!-- ── Ajouter une ville ────────────────────────────────────────────────── -->
<form method="POST" action="">
    <input type="hidden" name="action" value="add">
    <label>Ajouter une ville</label>
    <input type="text" name="city" placeholder="Ex: Paris, Tokyo, New York...">
    <button type="submit">Ajouter</button>
</form>

<!-- ── Liste des villes ────────────────────────────────────────────────── -->
<?php if (!empty($places)): ?>
<h2>Villes (<?= count($places) ?>)</h2>
<ul style="list-style:none;padding:0;margin-bottom:1rem">
    <?php foreach ($places as $idx => $place): ?>
    <li style="display:flex;align-items:center;gap:.8rem;padding:.4rem 0;border-bottom:1px solid var(--green-light)">
        <span style="flex:1"><?= htmlspecialchars($place['name']) ?></span>
        <span style="font-size:.8rem;color:var(--muted)"><?= round($place['lat'],4) ?>, <?= round($place['lng'],4) ?></span>
        <form method="POST" action="" style="margin:0">
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="idx" value="<?= $idx ?>">
            <button type="submit" class="danger">Supprimer</button>
        </form>
    </li>
    <?php endforeach; ?>
</ul>

<!-- ── Générer ─────────────────────────────────────────────────────────── -->
<form method="POST" action="">
    <input type="hidden" name="action" value="generate">
    <label for="max_hotels">Nombre max d'hôtels <span style="color:var(--muted)">(vide = auto)</span></label>
    <input type="number" id="max_hotels" name="max_hotels" min="1" max="<?= count($places) ?>" placeholder="auto" style="max-width:200px">
    <button type="submit">Générer le tour optimisé</button>
</form>
<?php endif; ?>

<!-- ── Résultats ────────────────────────────────────────────────────────── -->
<?php if ($result && !empty($orderedClusters)): ?>

<?php
$k         = $result['optimal_k']             ?? count($orderedClusters);
$totalKm   = $result['total_distance_km']     ?? 0;
$circuitKm = $result['circuit_distance_km']   ?? 0;
$dayKm     = $result['day_trips_distance_km'] ?? 0;
$hotels    = $result['hotels_circuit']        ?? [];

function guestSphericalKm(float $latA, float $lngA, float $latB, float $lngB): float {
    $PI = 3.141592; $R = 6378.197;
    $v = sin($latA*$PI/180)*sin($latB*$PI/180)
       + cos($latA*$PI/180)*cos($latB*$PI/180)*cos(($lngB-$lngA)*$PI/180);
    return $R * acos(max(-1.0, min(1.0, $v)));
}
?>

<h2 style="margin-top:2rem">Résultats</h2>
<div class="summary-box">
    <?= $k ?> hôtel<?= $k > 1 ? 's' : '' ?> &middot; <?= $totalKm ?> km au total
    <span style="margin-left:.8rem;color:var(--muted);font-size:.85rem">
        circuit <?= $circuitKm ?> km &middot; excursions <?= $dayKm ?> km
    </span>
</div>

<?php foreach ($orderedClusters as $idx => $cluster):
    $hotel       = $cluster['hotel'];
    $dayTrips    = $cluster['day_trips'] ?? [];
    $isLast      = ($idx === count($orderedClusters) - 1);
    $nextHotel   = !$isLast ? ($hotels[$idx + 1] ?? $hotels[0]) : $hotels[0];
    $travelToNext = round(guestSphericalKm($hotel['lat'], $hotel['lng'], $nextHotel['lat'], $nextHotel['lng']), 1);
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
        <?php foreach ($dayTrips as $city):
            $dist = round(guestSphericalKm($hotel['lat'], $hotel['lng'], $city['lat'], $city['lng']), 1);
        ?>
            <li>
                <span class="day-name"><?= htmlspecialchars($city['name']) ?></span>
                <span class="day-dist"><?= round($dist*2,1) ?> km aller-retour</span>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <p class="leg">
        <?= $isLast ? 'retour à' : 'vers' ?> <?= htmlspecialchars($nextHotel['name'] ?? '') ?> &mdash; <?= $travelToNext ?> km
    </p>
</div>
<?php endforeach; ?>

<p style="margin-top:1rem;font-size:.88rem;color:var(--muted)">
    Pour sauvegarder et partager cet itinéraire :
    <a href="auth/register.php">Créer un compte</a>
</p>

<?php endif; ?>

<!-- ── Réinitialiser ───────────────────────────────────────────────────── -->
<?php if (!empty($places) || $result): ?>
<form method="POST" action="" style="margin-top:1rem">
    <input type="hidden" name="action" value="reset">
    <button type="submit" class="secondary" style="font-size:.82rem">Tout effacer</button>
</form>
<?php endif; ?>

<div class="nav">
    <a href="auth/login.php">Connexion</a> &middot;
    <a href="auth/register.php">Créer un compte</a>
</div>

</body>
</html>
