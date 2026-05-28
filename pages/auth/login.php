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
