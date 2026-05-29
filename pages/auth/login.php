<?php
session_start();
require "../../config/database.php";

// Redirect already-logged-in users
if (isset($_SESSION['id'])) {
    header('Location: ../dashboard.php');
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if ($_POST['action'] === 'login') {
        $email    = $_POST["email"]    ?? '';
        $password = $_POST["password"] ?? '';

        $req = $bdd->prepare("SELECT * FROM users WHERE email = ?");
        $req->execute([$email]);
        $user = $req->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['id']     = $user['id'];
            $_SESSION['pseudo'] = $user['pseudo'];
            $_SESSION['email']  = $user['email'];
            header('Location: ../dashboard.php');
            exit();
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    }

    if ($_POST['action'] === 'creation') {
        header('Location: register.php');
        exit();
    }
}

// Fetch public trips that have a generated result
$publicTrips = $bdd->query(
    "SELECT t.titre, t.share_token, u.pseudo
     FROM travels t
     JOIN users u ON u.id = t.user_id
     WHERE t.statut = 'public' AND t.ordered_route IS NOT NULL
     ORDER BY t.id DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="../../public/assets/css/style.css">
</head>
<body>

<h1>Connexion</h1>

<?php if ($error): ?>
    <p class="msg error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form action="" method="POST">
    <input type="hidden" name="action" value="login">
    <label>Adresse email</label>
    <input type="email" name="email" placeholder="votre@email.com">
    <label>Mot de passe</label>
    <input type="password" name="password" placeholder="••••••••">
    <button type="submit">Se connecter</button>
</form>

<p style="margin-top:.8rem;font-size:.9rem;color:var(--muted)">
    Pas encore de compte ?
    <form action="" method="POST" style="display:inline">
        <input type="hidden" name="action" value="creation">
        <button type="submit" style="background:none;color:var(--blue);border:none;padding:0;font-size:.9rem;cursor:pointer;margin:0">Créer un compte</button>
    </form>
</p>

<?php if (!empty($publicTrips)): ?>
<div style="margin:1.5rem 0;padding:1rem;background:var(--blue-light);border-radius:var(--radius);border-left:3px solid var(--blue)">
    <strong style="color:var(--blue)">Pas encore de compte ?</strong>
    <a href="../try.php" style="margin-left:.6rem;color:var(--blue)">Essayer sans compte →</a>
    <span style="font-size:.82rem;color:var(--muted);display:block;margin-top:.25rem">Générez un itinéraire directement, sans inscription.</span>
</div>

<hr style="margin:1.5rem 0;border:none;border-top:1px solid var(--border)">
<h2>Itinéraires publics</h2>
<p style="font-size:.88rem;color:var(--muted);margin-bottom:.8rem">Partagés par la communauté — accessibles sans connexion.</p>
<?php foreach ($publicTrips as $trip): ?>
    <div class="card">
        <h3><?= htmlspecialchars($trip['titre']) ?></h3>
        <p style="font-size:.85rem;color:var(--muted)">Par <?= htmlspecialchars($trip['pseudo']) ?></p>
        <div class="card-links">
            <a href="../view_trip.php?token=<?= htmlspecialchars($trip['share_token']) ?>">Voir l'itinéraire</a>
        </div>
    </div>
<?php endforeach; ?>
<?php endif; ?>

</body>
</html>
