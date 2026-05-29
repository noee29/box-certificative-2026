<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <link rel="stylesheet" href="../../public/assets/css/style.css">
</head>
<body>

    <h1>Créer un compte</h1>

    <form action="" method="POST">
        <input type="hidden" name="action" value="creation">
        <label>Pseudo</label>
        <input type="text" placeholder="Votre pseudo" name="pseudo">
        <label>Adresse email</label>
        <input type="email" placeholder="votre@email.com" name="email">
        <label>Mot de passe</label>
        <input type="password" placeholder="••••••••" name="password">
        <button type="submit">Créer le compte</button>
    </form>

    <p style="margin-top:.8rem;font-size:.9rem;color:var(--muted)">
        Déjà un compte ?
        <form action="" method="POST" style="display:inline">
            <input type="hidden" name="action" value="login">
            <button type="submit" style="background:none;color:var(--blue);border:none;padding:0;font-size:.9rem;cursor:pointer;margin:0">Se connecter</button>
        </form>
    </p>

</body>
</html>


<?php


// Start a session for possible post-registration flow.
session_start();

// Load the shared database connection.
require("../../config/database.php");
/** @var PDO $bdd */

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    if ($_POST['action'] == 'creation') {

        // Read fields from the registration form.
        $pseudo = $_POST['pseudo'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        if($pseudo == "" || $email == "" || $password == "") {
            // Basic validation: require all fields.
            $error = "Veuillez renseigner tous les champs du formulaire";
            echo $error;
        }
        else {
            // Hash the password before storing.
            $hashedpassword = password_hash($password, PASSWORD_DEFAULT);
            $tab = [$pseudo, $email, $hashedpassword];

            // Insert the new user record.
            $query = $bdd -> prepare("INSERT INTO users(pseudo, email, password) VALUES (?, ?, ?)");
            $query -> execute($tab);

            // Simple confirmation message.
            $result = "Compte créé avec succès !";
            echo $result;
        }
        
    }
    if ($_POST['action'] == 'login') {
        // Back to the login page.
        header('Location: login.php');
        exit();
    }

}

?>