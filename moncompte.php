<?php
session_start();

if (!isset($_SESSION['utilisateur'])) {
    header("Location: login.php"); 
    exit();
}

require 'bd.php';
$db = Database::connect();


$utilisateur_id = $_SESSION['utilisateur'];
$stmt = $db->prepare("SELECT nom, email, date_inscription FROM utilisateurs WHERE id = ?");
$stmt->execute([$utilisateur_id]);
$utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$utilisateur) {
    echo "<div class='alert alert-danger' role='alert' style='text-align:center'>Erreur : utilisateur non trouvé.</div>";
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Compte</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css">
    <style>
        .container-account {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
    </style>
</head>
<body>

<div class="container-account">
    <div class="row">
        <div class="col-md-12">
            <h3>Informations du compte</h3>
            <table class="table">
                <tr>
                    <th>Nom</th>
                    <td><?= htmlspecialchars($utilisateur['nom']) ?></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><?= htmlspecialchars($utilisateur['email']) ?></td>
                </tr>
                <tr>
                    <th>Date d'inscription</th>
                    <td><?= htmlspecialchars($utilisateur['date_inscription']) ?></td>
                </tr>
            </table>
            <a href="logout.php" class="btn btn-danger">Déconnexion</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
