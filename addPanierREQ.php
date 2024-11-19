<?php
session_start();
require 'bd.php';
$db = Database::connect();

if(!isset($_SESSION['commande_temp']))
{
    //si la commande temporaire n'existe pas, on en crée une en générant un id unique avec la fonction uniqid
    $_SESSION['commande_temp'] = uniqid('temp_', true); 
}

if(isset($_GET['id']))
{
    $productId = $_GET['id'];
    $commandeTempid = $_SESSION['commande_temp'];
    $categoryId = isset($_GET['category']) ? intval($_GET['category']) : 1;

    if(!isset($_SESSION['panier']))
    {
        $_SESSION['panier'] = [];
    }

    //on vérifie si le produit est déjà dans le panier
    $stmt = $db->prepare("SELECT * FROM panier WHERE id_item = ? AND userTemp = ?");
    $stmt->execute([$productId, $commandeTempid]);
    $produitpanier = $stmt->fetch(PDO::FETCH_ASSOC);

    if($produitpanier)
    {
        //si le produit est déjà dans le panier, on incrémente la quantité
        $stmt = $db->prepare("UPDATE panier SET qte = qte + 1 WHERE id_item = ? AND userTemp = ?");
        $stmt->execute([$productId, $commandeTempid]);
    }
    else
    {
        $stmtPrice = $db->prepare("SELECT price FROM items WHERE id = ?");
        $stmtPrice->execute([$productId]);
        $price = $stmtPrice->fetchColumn();

        //sinon, on ajoute le produit au panier
        $stmt = $db->prepare("INSERT INTO panier (id_item, userTemp, qte, prix) VALUES (?, ?, 1, ?)");
        $stmt->execute([$productId, $commandeTempid, $price]);
    }
    header("Location: index.php?category=$categoryId");
    exit();
}

else
{
    header("Location : index.php");
    exit();
}

?>