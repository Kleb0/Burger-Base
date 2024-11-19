<?php
session_start();
require 'bd.php';
$db = Database::connect();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['utilisateur'];

// Insérer une nouvelle commande
$stmt = $db->prepare("INSERT INTO commandes (user_id, date) VALUES (?, NOW())");
$stmt->execute([$userId]);
$orderId = $db->lastInsertId();

// Récupérer les articles du panier
$commandeTempId = $_SESSION['commande_temp'];
$stmt = $db->prepare("SELECT panier.id_item, panier.qte, panier.prix AS prix_panier, items.name, items.price AS prix_item, items.image
                      FROM panier 
                      JOIN items ON panier.id_item = items.id 
                      WHERE panier.userTemp = ?");
$stmt->execute([$commandeTempId]);
$panier = $stmt->fetchAll(PDO::FETCH_ASSOC);

//récupérer les jouets du panier
$stmt = $db->prepare("SELECT toys.name AS toy_name, toys.description AS toy_description, toys.image AS toy_image 
                      FROM panier 
                      JOIN toys ON panier.id_toy = toys.id
                      WHERE panier.userTemp = ?");
$stmt->execute([$commandeTempId]);
$toys = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vérifier les articles et les insérer dans commande_items
$_SESSION['confirmation_articles'] = $panier;
$_SESSION['confirmation_toys'] = $toys;



// Insérer les jouets associés dans la commande (si applicable)
foreach ($toys as $toy) 
{
    $stmt = $db->prepare("INSERT INTO commande_toys (commande_id, toy_id, description, image) VALUES (?, ?, ?, ?)");
    $stmt->execute([$orderId, $toy['toy_name'], $toy['toy_description'], $toy['toy_image']]);
}


// Vider le panier après validation de la commande
$stmt = $db->prepare("DELETE FROM panier WHERE userTemp = ?");
$stmt->execute([$commandeTempId]);

// Générer une nouvelle commande temporaire
unset($_SESSION['commande_temp']);
$_SESSION['commande_temp'] = uniqid('temp_', true);

// Rediriger vers la page de confirmation
header("Location: confirmation.php");
exit();
