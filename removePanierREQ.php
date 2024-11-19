<?php
session_start();
require 'bd.php';
$db = Database::connect();


if (isset($_GET['id']))
{
   
    $productId = $_GET['id'];

    if (isset($_SESSION['utilisateur']))
    {
        $userId = $_SESSION['utilisateur'];

    } 
    elseif (isset($_SESSION['commande_temp'])) 
    {
        $userId = $_SESSION['commande_temp'];
    }
    if ($userId) {
        // Récupérer le nom de l'article
        $stmt = $db->prepare("SELECT name FROM items WHERE id = ?");
        $stmt->execute([$productId]);
        $itemName = $stmt->fetchColumn();

        // Supprimer l'article du panier
        $stmt = $db->prepare("DELETE FROM panier WHERE id_item = ? AND userTemp = ?");
        $stmt->execute([$productId, $userId]);

        // Si l'article est un "Menu Enfant", mettre tous les jouets sur NULL
        if (str_contains($itemName, 'Menu Enfant'))
        {
            $stmt = $db->prepare("UPDATE panier SET id_toy = NULL WHERE userTemp = ? AND id_toy IS NOT NULL");
            $stmt->execute([$userId]);
        }
    }
}

header("Location: panier.php");
exit();