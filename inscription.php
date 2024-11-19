<?php 

require 'bd.php';
$db = Database::connect();

$query = "SELECT * FROM categories";
$parentCateg = $db->query($query)->fetchALL(PDO::FETCH_ASSOC);

$query2 = "SELECT * FROM items";
$parentProduit = $db->query($query2)->fetchALL(PDO::FETCH_ASSOC);

$message = '';
if($_SERVER["REQUEST_METHOD"] == "POST")
{
    $nom = htmlspecialchars($_POST['nom']);
    $email = htmlspecialchars($_POST['email']);
    $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);

    //verification de l'email existant
    $stmt = $db->prepare("SELECT * FROM utilisateurs where email = ?");
    $stmt->execute([$email]);
    if ($stmt -> rowCount() > 0)
    {
        $message = '<div class="alert alert-danger" role="alert" style="text-align:center">Erreur l\'email est déjà utilisé !</div>';
    }
    else
    {
        $stmt = $db-> prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe) VALUES (?, ?, ?)");
        if ($stmt->execute([$nom, $email, $mot_de_passe]))
        {
            $message = '<div class="alert alert-success" role="alert" style="text-align:center">Inscription réussie !</div>';
        }
        else
        {
            $message = '<div class="alert alert-danger" role="alert" style="text-align:center">Erreur lors de l\'inscription !</div>';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration and Login Form</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css">
    <style>
        .container-account {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>

    <?= $message ?>

    <div class="container-account">
        <div class="row">
            <div class="col-md-12">
                <h3>Registration</h3>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="regName" class="form-label">Nom</label>
                        <input type="text" name="nom" class="form-control" id="regName" placeholder="Enter your name" required>
                    </div>
                    <div class="mb-3">
                        <label for="regEmail" class="form-label">Email </label>
                        <input type="email" name="email" class="form-control" id="regEmail" placeholder="Enter your email" required>
                    </div>
                    <div class="mb-3">
                        <label for="regPassword" class="form-label">Mot de passe</label>
                        <input type="password" name="mot_de_passe" class="form-control" id="regPassword" placeholder="Enter a password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Inscription</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>

</body>