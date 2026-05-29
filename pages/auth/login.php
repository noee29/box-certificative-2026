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

</body>
</html>



<?php

// Start a session to store authenticated user data.
session_start();
// Load the shared database connection.
require("../../config/database.php");
/** @var PDO $bdd */

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    if ($_POST['action'] == 'login') {

        // Read credentials from the login form.
        $email = $_POST["email"];
        $password = $_POST["password"];
        $tab2 = [$email];

        // Fetch the user by email.
        $req = $bdd->prepare("SELECT * FROM users WHERE email = ?");
        $req->execute($tab2);

        $user = $req->fetch();

        if ($user && password_verify($password, $user['password'])) {

            // Persist user identity in session and redirect to dashboard.
            $_SESSION['id'] = $user['id'];
            $_SESSION['pseudo'] = $user['pseudo'];
            $_SESSION['email'] = $user['email'];

            header('Location: ../dashboard.php');
            exit();

        } else {
            // Invalid credentials feedback.
            $error = "Identifiants non valides";
            echo $error;
        }
    }

    if ($_POST['action'] == 'creation') {
        // Redirect to the registration page.
        header('Location: register.php');
        exit();
    }
}

?>