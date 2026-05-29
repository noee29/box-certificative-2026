<?php

// Start session to access user data.
session_start();
require("../config/database.php");

if (!isset($_SESSION['id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = $_SESSION['id'];
$pseudo = $_SESSION['pseudo'];

// Handle travel creation.
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if ($_POST['action'] == "create_travel") {

        $title = $_POST['title'];
        $visibility = $_POST['visibility'];

        if ($title == "") {
            $error = "Veuillez saisir un titre pour le voyage";
            echo $error;
        } else {
            $token = bin2hex(random_bytes(16));
            $req = $bdd->prepare("INSERT INTO travels(user_id, titre, statut, share_token) VALUES (?, ?, ?, ?)");
            $req->execute([$user_id, $title, $visibility, $token]);

            header("Location: dashboard.php");
            exit();
        }
    }
}

// Fetch travels for the logged-in user.
$req = $bdd->prepare("SELECT * FROM travels WHERE user_id = ? ORDER BY id DESC");
$req->execute([$user_id]);
$travels = $req->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>

    <div class="topbar">
        <span class="brand">Planification de voyages</span>
        <form action="logout.php" method="POST">
            <button type="submit">Déconnexion</button>
        </form>
    </div>

    <h1>Mes voyages</h1>
    <p style="color:var(--muted)">Bienvenue, <?= htmlspecialchars($pseudo) ?></p>

    <h2>Nouveau voyage</h2>
    <form action="" method="POST">
        <input type="hidden" name="action" value="create_travel">
        <input type="text" name="title" placeholder="Nom du voyage">
        <select name="visibility">
            <option value="private">Privé</option>
            <option value="public">Public</option>
        </select>
        <button type="submit">Créer</button>
    </form>

    <h2>Voyages existants</h2>

    <?php if (count($travels) == 0): ?>
        <p style="color:var(--muted)">Aucun voyage créé pour l'instant.</p>
    <?php endif; ?>

    <?php foreach ($travels as $travel): ?>
        <div class="card">
            <h3>
                <?= htmlspecialchars($travel['titre']) ?>
                <span class="badge <?= $travel['statut'] === 'public' ? 'public' : '' ?>">
                    <?= htmlspecialchars($travel['statut']) ?>
                </span>
            </h3>
            <div class="card-links">
                <a href="places.php?travel_id=<?= $travel['id'] ?>">Gérer les lieux</a>
                <a href="generate.php?travel_id=<?= $travel['id'] ?>">Générer le tour</a>
                <?php if (!empty($travel['ordered_route'])): ?>
                    <a href="results.php?travel_id=<?= $travel['id'] ?>">Voir les résultats</a>
                    <a href="view_trip.php?token=<?= htmlspecialchars($travel['share_token']) ?>" style="color:var(--green)">
                        <?= $travel['statut'] === 'public' ? 'Lien public' : 'Lien privé' ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

</body>
</html>