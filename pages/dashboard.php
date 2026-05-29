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
            $req = $bdd->prepare("INSERT INTO travels(user_id, titre, statut) VALUES (?, ?, ?)");
            $req->execute([$user_id, $title, $visibility]);

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
</head>
<body>

    <h1>Dashboard</h1>

    <p>Bienvenue <?= $pseudo ?></p>

    <!-- Logout form -->
    <form action="logout.php" method="POST">
        <button type="submit">Déconnexion</button>
    </form>

    <h2>Créer un nouveau voyage</h2>

    <!-- Travel creation form -->
    <form action="" method="POST">
        <input type="hidden" name="action" value="create_travel">

        <input type="text" name="title" placeholder="Nom du voyage">

        <select name="visibility">
            <option value="private">Privé</option>
            <option value="public">Public</option>
        </select>

        <button type="submit">Créer le voyage</button>
    </form>


    <h2>Mes voyages</h2>

    <?php if (count($travels) == 0): ?>
        <p>Vous n'avez pas encore créé de voyage.</p>
    <?php endif; ?>

    <!-- List of travels -->
    <?php foreach ($travels as $travel): ?>
        <div>
            <h3><?= $travel['titre'] ?></h3>

            <p>Statut : <?= $travel['statut'] ?></p>

            <a href="places.php?travel_id=<?= $travel['id'] ?>">Gérer les lieux</a>
            <a href="generate.php?travel_id=<?= $travel['id'] ?>">Générer le tour</a>
            <a href="results.php?travel_id=<?= $travel['id'] ?>">Voir les résultats</a>
        </div>
    <?php endforeach; ?>

</body>
</html>