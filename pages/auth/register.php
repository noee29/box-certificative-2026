<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
</head>
<body>

    <h1>Créer vous un compte ci-dessous :</h1>

    <form action="" method="POST">
        <input type="hidden" name="action" value="creation">

        <input type="text" placeholder="Pseudo" name="pseudo">
        <input type="email" placeholder="Adresse mail" name="email">
        <input type="password" placeholder="Mot de passe" name="password">

        <button type="submit">Créer vous un compte</button>
    </form>

    <form action="" method="POST">
        <input type="hidden" name="action" value="login">
        <button type="submit">Retour à la page de connexion</button>
    </form>

</body>
</html>


<?php


// Start a session for possible post-registration flow.
session_start();

// Load the shared database connection.
require("../../config/database.php");

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