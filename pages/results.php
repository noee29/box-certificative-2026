<?php

session_start();

if (!isset($_SESSION['id'])) {
    header("Location: auth/login.php");
    exit();
}

$travelId = isset($_GET['travel_id']) ? (int) $_GET['travel_id'] : 0;

// ── Helper: spherical distance (same formula as algo.py) ─────────────────────
function sphericalKm(float $latA, float $lngA, float $latB, float $lngB): float {
    $PI  = 3.141592;
    $R   = 6378.197;
    $laA = $latA * $PI / 180;
    $loA = $lngA * $PI / 180;
    $laB = $latB * $PI / 180;
    $loB = $lngB * $PI / 180;
    $v   = sin($laA)*sin($laB) + cos($laA)*cos($laB)*cos($loB - $loA);
    return $R * acos(max(-1.0, min(1.0, $v)));
}

// ── Helper: recalculate circuit + total km after hotel reordering ────────────
function recalcDistances(): void {
    $circuit  = $_SESSION['trip_result']['hotels_circuit'];
    $n        = count($circuit);
    $circuitKm = 0.0;
    for ($i = 0; $i < $n; $i++) {
        $a = $circuit[$i];
        $b = $circuit[($i + 1) % $n];
        $circuitKm += sphericalKm($a['lat'], $a['lng'], $b['lat'], $b['lng']);
    }
    $dayTripsKm = $_SESSION['trip_result']['day_trips_distance_km'] ?? 0.0;
    $_SESSION['trip_result']['circuit_distance_km'] = round($circuitKm, 3);
    $_SESSION['trip_result']['total_distance_km']   = round($circuitKm + $dayTripsKm, 3);
}

// ── Helper: reorder clusters to match current hotels_circuit order ────────────
function resyncClusters(): void {
    $byKey = [];
    foreach ($_SESSION['trip_result']['clusters'] as $cluster) {
        $key = $cluster['hotel']['id'] ?? $cluster['hotel']['name'];
        $byKey[$key] = $cluster;
    }
    $ordered = [];
    foreach ($_SESSION['trip_result']['hotels_circuit'] as $hotel) {
        $key = $hotel['id'] ?? $hotel['name'];
        if (isset($byKey[$key])) {
            $ordered[] = $byKey[$key];
        }
    }
    $_SESSION['trip_result']['clusters'] = $ordered;
}

// ── Manual reordering (POST) ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['trip_result']['hotels_circuit'])) {
    $action   = $_POST['action']    ?? '';
    $hotelIdx = isset($_POST['hotel_idx']) ? (int) $_POST['hotel_idx'] : -1;
    $k        = count($_SESSION['trip_result']['hotels_circuit']);

    // Hotel-level reordering
    if ($action === 'move_up' && $hotelIdx > 0 && $hotelIdx < $k) {
        $tmp = $_SESSION['trip_result']['hotels_circuit'][$hotelIdx - 1];
        $_SESSION['trip_result']['hotels_circuit'][$hotelIdx - 1] = $_SESSION['trip_result']['hotels_circuit'][$hotelIdx];
        $_SESSION['trip_result']['hotels_circuit'][$hotelIdx]     = $tmp;
        resyncClusters();
        recalcDistances();

    } elseif ($action === 'move_down' && $hotelIdx >= 0 && $hotelIdx < $k - 1) {
        $tmp = $_SESSION['trip_result']['hotels_circuit'][$hotelIdx + 1];
        $_SESSION['trip_result']['hotels_circuit'][$hotelIdx + 1] = $_SESSION['trip_result']['hotels_circuit'][$hotelIdx];
        $_SESSION['trip_result']['hotels_circuit'][$hotelIdx]     = $tmp;
        resyncClusters();
        recalcDistances();

    } elseif ($action === 'set_start' && $hotelIdx > 0 && $hotelIdx < $k) {
        $_SESSION['trip_result']['hotels_circuit'] = array_merge(
            array_slice($_SESSION['trip_result']['hotels_circuit'], $hotelIdx),
            array_slice($_SESSION['trip_result']['hotels_circuit'], 0, $hotelIdx)
        );
        resyncClusters();
        recalcDistances();

    // Day-trip-level reordering within a hotel cluster
    } elseif (in_array($action, ['trip_up', 'trip_down'])) {
        $hotelKey = $_POST['hotel_key'] ?? '';
        $tripIdx  = isset($_POST['trip_idx']) ? (int) $_POST['trip_idx'] : -1;
        foreach ($_SESSION['trip_result']['clusters'] as &$cluster) {
            $key = (string) ($cluster['hotel']['id'] ?? $cluster['hotel']['name']);
            if ($key !== $hotelKey) continue;
            $trips = &$cluster['day_trips'];
            $n     = count($trips);
            if ($action === 'trip_up' && $tripIdx > 0 && $tripIdx < $n) {
                [$trips[$tripIdx - 1], $trips[$tripIdx]] = [$trips[$tripIdx], $trips[$tripIdx - 1]];
            } elseif ($action === 'trip_down' && $tripIdx >= 0 && $tripIdx < $n - 1) {
                [$trips[$tripIdx], $trips[$tripIdx + 1]] = [$trips[$tripIdx + 1], $trips[$tripIdx]];
            }
            unset($trips);
            break;
        }
        unset($cluster);
    }

    header("Location: results.php?travel_id=" . $travelId);
    exit();
}

// ── Read session result ───────────────────────────────────────────────────────
$result = $_SESSION['trip_result'] ?? null;

// Build ordered clusters matching hotels_circuit order
$orderedClusters = [];
if ($result && isset($result['hotels_circuit'])) {
    $byKey = [];
    foreach ($result['clusters'] as $cluster) {
        $key = $cluster['hotel']['id'] ?? $cluster['hotel']['name'];
        $byKey[$key] = $cluster;
    }
    foreach ($result['hotels_circuit'] as $hotel) {
        $key = $hotel['id'] ?? $hotel['name'];
        if (isset($byKey[$key])) {
            $orderedClusters[] = $byKey[$key];
        }
    }
}

$k             = $result['optimal_k']              ?? 0;
$scoredK       = $result['scored_k']               ?? $k;
$circuitKm     = $result['circuit_distance_km']    ?? 0;
$dayTripsKm    = $result['day_trips_distance_km']  ?? 0;
$totalKm       = $result['total_distance_km']      ?? 0;
$costByK       = $result['cost_by_k']              ?? [];
$hotelsCircuit = $result['hotels_circuit']         ?? [];
$nbHotels      = count($hotelsCircuit);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Itinéraire optimisé</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>

<h1>Itineraire optimise</h1>

<?php if (!$result || empty($orderedClusters)): ?>

    <p class="meta">Aucun resultat disponible.</p>
    <?php if ($travelId > 0): ?>
        <a href="generate.php?travel_id=<?= $travelId ?>">Generer le tour</a>
    <?php endif; ?>

<?php else: ?>

    <p class="meta">
        <?= $k ?> hotel<?= $k > 1 ? 's' : '' ?> &middot; <?= $totalKm ?> km
        <span style="margin-left:.8rem;color:#bbb;">|</span>
        <span style="margin-left:.8rem;">circuit <?= $circuitKm ?> km &middot; excursions <?= $dayTripsKm ?> km</span>
    </p>

    <h2>Etapes</h2>
    <p style="font-size:.82rem;color:#aaa;font-family:sans-serif;margin-top:-.4rem">Reordonnez si besoin — aucune re-optimisation.</p>

    <?php foreach ($orderedClusters as $idx => $cluster):
        $hotel        = $cluster['hotel'];
        $dayTrips     = $cluster['day_trips'];
        $isFirst      = ($idx === 0);
        $isLast       = ($idx === $nbHotels - 1);
        $nextHotel    = !$isLast ? $hotelsCircuit[$idx + 1] : $hotelsCircuit[0];
        $travelToNext = round(sphericalKm($hotel['lat'], $hotel['lng'], $nextHotel['lat'], $nextHotel['lng']), 1);
    ?>
    <div class="hotel-block">

        <div class="hotel-name">
            <?= htmlspecialchars($hotel['name']) ?>
            <span class="hotel-tag">hotel</span>
        </div>

        <div class="btn-row">
            <?php if (!$isFirst): ?>
            <form method="POST" action="results.php?travel_id=<?= $travelId ?>">
                <input type="hidden" name="hotel_idx" value="<?= $idx ?>">
                <button type="submit" name="action" value="move_up">&#8593; remonter</button>
            </form>
            <form method="POST" action="results.php?travel_id=<?= $travelId ?>">
                <input type="hidden" name="hotel_idx" value="<?= $idx ?>">
                <button type="submit" name="action" value="set_start">partir d'ici</button>
            </form>
            <?php endif; ?>
            <?php if (!$isLast): ?>
            <form method="POST" action="results.php?travel_id=<?= $travelId ?>">
                <input type="hidden" name="hotel_idx" value="<?= $idx ?>">
                <button type="submit" name="action" value="move_down">&#8595; descendre</button>
            </form>
            <?php endif; ?>
        </div>

        <?php if (empty($dayTrips)): ?>
            <p style="font-size:.85rem;color:#aaa;font-family:sans-serif">Pas d'excursion depuis cet hotel.</p>
        <?php else:
            $hotelKey  = (string) ($hotel['id'] ?? $hotel['name']);
            $nbTrips   = count($dayTrips);
        ?>
            <ul class="day-list">
            <?php foreach ($dayTrips as $tripIdx => $city):
                $dist         = round(sphericalKm($hotel['lat'], $hotel['lng'], $city['lat'], $city['lng']), 1);
                $isFirstTrip  = ($tripIdx === 0);
                $isLastTrip   = ($tripIdx === $nbTrips - 1);
            ?>
                <li>
                    <?php if (!$isFirstTrip): ?>
                    <form method="POST" action="results.php?travel_id=<?= $travelId ?>">
                        <input type="hidden" name="hotel_key" value="<?= htmlspecialchars($hotelKey) ?>">
                        <input type="hidden" name="trip_idx"  value="<?= $tripIdx ?>">
                        <button type="submit" name="action" value="trip_up">&#8593;</button>
                    </form>
                    <?php else: ?>
                        <span style="display:inline-block;width:26px"></span>
                    <?php endif; ?>

                    <?php if (!$isLastTrip): ?>
                    <form method="POST" action="results.php?travel_id=<?= $travelId ?>">
                        <input type="hidden" name="hotel_key" value="<?= htmlspecialchars($hotelKey) ?>">
                        <input type="hidden" name="trip_idx"  value="<?= $tripIdx ?>">
                        <button type="submit" name="action" value="trip_down">&#8595;</button>
                    </form>
                    <?php else: ?>
                        <span style="display:inline-block;width:26px"></span>
                    <?php endif; ?>

                    <span class="day-name"><?= htmlspecialchars($city['name']) ?></span>
                    <span class="day-dist"><?= round($dist * 2, 1) ?> km aller-retour</span>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <p class="leg">
            <?php if (!$isLast): ?>
                vers <?= htmlspecialchars($nextHotel['name']) ?> &mdash; <?= $travelToNext ?> km
            <?php else: ?>
                retour a <?= htmlspecialchars($hotelsCircuit[0]['name']) ?> &mdash; <?= $travelToNext ?> km
            <?php endif; ?>
        </p>

    </div>
    <?php endforeach; ?>

    <?php if (!empty($costByK)): ?>
    <h2>Comparaison par nombre d'hotels</h2>
    <table>
        <thead>
            <tr><th>Hotels</th><th>Distance totale (km)</th><th>Statut</th></tr>
        </thead>
        <tbody>
        <?php foreach ($costByK as $row):
            $isRec   = ($row['k'] === $k);
            $isValid = $row['valid'] ?? true;
        ?>
            <tr <?= $isRec ? 'class="rec"' : '' ?> <?= !$isValid ? 'style="color:#bbb"' : '' ?>>
                <td><?= $row['k'] ?><?= $isRec ? ' &larr;' : '' ?></td>
                <td><?= $row['total_distance_km'] ?></td>
                <td><?= $isValid ? '' : '&gt; 200 km' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="nav">
        <?php if ($travelId > 0): ?>
            <a href="generate.php?travel_id=<?= $travelId ?>">Regenerer</a> &middot;
        <?php endif; ?>
        <a href="dashboard.php">Dashboard</a>
    </div>

<?php endif; ?>

</body>
</html>
