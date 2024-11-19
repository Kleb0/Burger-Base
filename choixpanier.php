<?php
session_start();
require 'bd.php';
$db = Database::connect();

if (!isset($_SESSION['choix_panier'])) {
    header("Location: login.php");
    exit();
}

$commandeTempId = $_SESSION['choix_panier']['temp'];
$userId = $_SESSION['choix_panier']['user'];

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
    if ($_POST['action'] === 'fusionner')
    {
        // Fusionner les paniers
        $stmtMerge = $db->prepare("
            UPDATE panier 
            SET qte = qte + (
                SELECT qte FROM panier AS temp 
                WHERE temp.id_item = panier.id_item AND temp.userTemp = ?
            )
            WHERE userTemp = ? AND id_item IN (
                SELECT id_item FROM panier AS temp WHERE temp.userTemp = ?
            )
        ");
        $stmtMerge->execute([$commandeTempId, $userId, $commandeTempId]);

        $stmtAdd = $db->prepare("
            INSERT INTO panier (id_item, userTemp, qte, prix)
            SELECT id_item, ?, qte, prix FROM panier
            WHERE userTemp = ? AND id_item NOT IN (
                SELECT id_item FROM panier WHERE userTemp = ?
            )
        ");
        $stmtAdd->execute([$userId, $commandeTempId, $userId]);

    } 
    elseif ($_POST['action'] === 'remplacer')
    {
        // Remplacer le panier existant par le panier temporaire
        $stmtDelete = $db->prepare("DELETE FROM panier WHERE userTemp = ?");
        $stmtDelete->execute([$userId]);

        $stmtUpdate = $db->prepare("UPDATE panier SET userTemp = ? WHERE userTemp = ?");
        $stmtUpdate->execute([$userId, $commandeTempId]);
    }

    unset($_SESSION['choix_panier'], $_SESSION['commande_temp']);
    header("Location: panier.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choix du Panier</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Choix du Panier</h1>
        <p class="text-center">Vous avez déjà un panier actif. Que voulez-vous faire ?</p>
        <form method="POST" class="text-center">
            <button type="submit" name="action" value="fusionner" class="btn btn-success me-2">Fusionner les paniers</button>
            <button type="submit" name="action" value="remplacer" class="btn btn-danger">Remplacer le panier existant</button>
        </form>
    </div>
</body>
</html>
