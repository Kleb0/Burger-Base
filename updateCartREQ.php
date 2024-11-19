<?php
session_start();
require 'bd.php';
$db = Database::connect();

if (isset($_GET['id']) && isset($_GET['action'])) 
{
    $productId = $_GET['id'];
    $action = $_GET['action'];

    if (isset($_SESSION['utilisateur']))
    {
        $userId = $_SESSION['utilisateur'];
    } 
    elseif (isset($_SESSION['commande_temp']))
    {
        $userId = $_SESSION['commande_temp'];
    }
    else 
    {
        header("Location: panier.php");
        exit();
        
    }

    if ($action === 'addToy') 
    {
        // Compter le nombre de menus enfants dans le panier
        $stmt = $db->prepare("SELECT SUM(qte) FROM panier JOIN items ON panier.id_item = items.id WHERE items.name LIKE '%Menu Enfant%' AND userTemp = ?");
        $stmt->execute([$userId]);
        $menuEnfantCount = $stmt->fetchColumn();

        // Compter le nombre de jouets dans le panier
        $stmt = $db->prepare("SELECT COUNT(*) FROM panier WHERE id_toy IS NOT NULL AND userTemp = ?");
        $stmt->execute([$userId]);
        $currentToyCount = $stmt->fetchColumn();

        // Permettre l'ajout d'un jouet si le nombre de jouets est strictement inférieur au nombre de menus enfants
        if ($currentToyCount <= $menuEnfantCount)
        {
            $stmt = $db->prepare("INSERT INTO panier (id_toy, userTemp, qte, prix) VALUES (?, ?, 1, 0)");
            $stmt->execute([$productId, $userId]);
        } 
        else 
        {
            header("Location: panier.php?error=toy_already_exists");
            exit();
        }
    }

    if ($action === 'increase')
    {
        $stmt = $db->prepare("UPDATE panier SET qte = qte + 1 WHERE id_item = ? AND userTemp = ?");
        $stmt->execute([$productId, $userId]);
    } 
    
    if ($action === 'decrease')
    {
        $stmt = $db->prepare("SELECT qte FROM panier WHERE id_item = ? AND userTemp = ?");
        $stmt->execute([$productId, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        //vérifier si l'article est un menu enfant
        $stmt = $db->prepare("SELECT name FROM items WHERE id = ?");	
        $stmt->execute([$productId]);
        $productName = $stmt->fetchColumn();

        $isChildMenu = str_contains($productName, 'Menu Enfant');


        

        if ($result && $result['qte'] > 1)
        {
            $stmt = $db->prepare("UPDATE panier SET qte = qte - 1 WHERE id_item = ? AND userTemp = ?");
            $stmt->execute([$productId, $userId]);
        } 
        else
        {
            // Si la quantité est 1, supprime le produit du panier
            $stmt = $db->prepare("DELETE FROM panier WHERE id_item = ? AND userTemp = ?");
            $stmt->execute([$productId, $userId]);

            //vérifier si l'article supprimé est un menu enfant
            $stmt = $db->prepare("SELECT name FROM items WHERE id = ?");
            $stmt->execute([$productId]);
            $productName = $stmt->fetchColumn();

        }
        if ($isChildMenu)
        {
            // Vérifier le nombre de menus enfants restants
            $stmt = $db->prepare("SELECT SUM(qte) FROM panier JOIN items ON panier.id_item = items.id WHERE items.name LIKE '%Menu Enfant%' AND userTemp = ?");
            $stmt->execute([$userId]);
            $menuEnfantCount = $stmt->fetchColumn();

            // Compter le nombre de jouets dans le panier
            $stmt = $db->prepare("SELECT COUNT(*) FROM panier WHERE id_toy IS NOT NULL AND userTemp = ?");
            $stmt->execute([$userId]);
            $currentToyCount = $stmt->fetchColumn();

            // Si le nombre de jouets est supérieur au nombre de menus enfants, supprimer le dernier jouet ajouté
            if ($currentToyCount > $menuEnfantCount)
            {
                $stmt = $db->prepare("
                    UPDATE panier 
                    SET id_toy = NULL 
                    WHERE id_toy IS NOT NULL 
                    AND userTemp = ? 
                    ORDER BY id DESC 
                    LIMIT 1
                ");
                $stmt->execute([$userId]);
            }
        }
    }

    header("Location: panier.php");
    exit();

} 
else
{
    header("Location: panier.php");
    exit();
}
