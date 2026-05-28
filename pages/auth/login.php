<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
</head>
<body>

    <h1>Connectez vous à votre compte ci-dessous :</h1>

    <form action="" method="POST">
        <input type="hidden" name="action" value="login">

        <input type="email" name="email" placeholder="Saisir adresse mail">
        <input type="password" name="password" placeholder="Saisir mot de passe">

        <button type="submit">Se connecter</button>
    </form>

    <form action="" method="POST">
        <input type="hidden" name="action" value="creation">
        <button type="submit">Créer un compte</button>
    </form>

</body>
</html>



<?php

session_start();
require("../../config/database.php");
/** @var PDO $bdd */

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    if ($_POST['action'] == 'login') {

        $email = $_POST["email"];
        $password = $_POST["password"];
        $tab2 = [$email];

        $req = $bdd->prepare("SELECT * FROM users WHERE email = ?");
        $req->execute($tab2);

        $user = $req->fetch();

        if ($user && password_verify($password, $user['password'])) {

            $_SESSION['id'] = $user['id'];
            $_SESSION['pseudo'] = $user['pseudo'];
            $_SESSION['email'] = $user['email'];

            header('Location: ../dashboard.php');
            exit();

        } else {
            $error = "Identifiants non valides";
            echo $error;
        }
    }

    if ($_POST['action'] == 'creation') {
        header('Location: register.php');
        exit();
    }
}

?>