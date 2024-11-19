<?php
session_start();
require 'bd.php';
$db = Database::connect(); 

// Récupération des articles confirmés
if (!isset($_SESSION['utilisateur'])) 
{
    if(!isset($_SESSION['utilisateur']))
    {
        header("Location: login.php");
        exit();
    }

}

$userId = $_SESSION['utilisateur'];
$commandeTempId = $_SESSION['utilisateur'];

//récupérer les article
$commandeTempId = $_SESSION['commande_temp'];
$stmt = $db->prepare("SELECT items.name AS item_name, items.price AS item_price, panier.qte, toys.name AS toy_name, toys.description AS toy_description, toys.image AS toy_image 
                      FROM panier 
                      LEFT JOIN items ON panier.id_item = items.id
                      LEFT JOIN toys ON panier.id_toy = toys.id
                      WHERE panier.userTemp = ?");

$stmt->execute([$commandeTempId]);
$confirmationArticles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalHT = 0;
foreach ($confirmationArticles as $article) 
{
    if (!empty($article['item_price']) && !empty($article['qte']))
    {
        $totalHT += $article['item_price'] * $article['qte'];
    }
}

$articles = $_SESSION['confirmation_articles'] ?? [];
$toys = $_SESSION['confirmation_toys'] ?? [];
unset($_SESSION['confirmation_articles'], $_SESSION['confirmation_toys']);


// // recupérer les jouets
// $stmt = $db->prepare("SELECT toy_id AS toy_name, description AS toy_description, image AS toy_image FROM commande_toys WHERE commande_id = ?");
// $stmt->execute([$commandeTempId]);
// $toys = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Réinitialisation de l'identifiant temporaire du panier pour les prochaines commandes
if (!isset($_SESSION['utilisateur'])) {
    // Si l'utilisateur n'est pas connecté, on génère un nouvel identifiant temporaire
    $_SESSION['commande_temp'] = uniqid('temp_', true);
}
else
{
    // Si l'utilisateur est connecté, on utilise son identifiant pour gérer le panier
    $_SESSION['commande_temp'] = $_SESSION['utilisateur'];
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .confirmation-container {
            margin-top: 50px;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
        }
        .product-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .total-price {
            font-size: 1.5rem;
            color: #28a745;
            font-weight: bold;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="confirmation-container text-center">
            <h1 class="text-success mb-4">Votre commande a été validée !</h1>
            <?php if (!empty($articles)): ?>
                <h3 class="mb-4">Résumé de vos articles :</h3>
                <ul class="list-group mb-4">
                    <?php foreach ($articles as $article): ?>
                        <li class="list-group-item">
                            <div class="product-info">
                            <img src="images/<?= isset($article['image']) ? htmlspecialchars($article['image']) : 'default.png' ?>" 
                            alt="<?= htmlspecialchars($article['name']) ?>" class="product-image">
                                <div class="ms-3 text-start">
                                    <h5><?= htmlspecialchars($article['name']) ?></h5>
                                    <p>Quantité : <?= htmlspecialchars($article['qte']) ?></p>
                                    <p>Prix unitaire : <?= number_format($article['prix_panier'], 2) ?> €</p>
                                </div>
                                <div class="text-end">
                                    <p class="total-price"><?= number_format($article['prix_panier'] * $article['qte'], 2) ?> €</p>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

            <!-- section des jouets -->
            <?php if (!empty($toys)): ?>
                <div class="toys-list">
                    <h3 class="mb-4">Des jouets ont été ajoutés à votre commande !</h3>
                    <ul class="list-group">
                        <?php foreach ($toys as $toy): ?>
                            <li class="list-group-item">
                                <div class="product-info">
                                    <img src="<?= htmlspecialchars($toy['toy_image']) ?>" 
                                        alt="<?= htmlspecialchars($toy['toy_name']) ?>" class="product-image">
                                    <div class="ms-3 text-start">
                                        <h5><?= htmlspecialchars($toy['toy_name']) ?></h5>
                                        <p><?= htmlspecialchars($toy['toy_description']) ?></p>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <h4 class="total-price">
                    Total TTC : 
                    <?= number_format(array_reduce($articles, function($carry, $item) {
                        return $carry + ($item['prix_panier'] * $item['qte']);
                    }, 0) * 1.2, 2) ?> €
                </h4>
            <?php else: ?>
                <p class="text-danger">Erreur : aucun article trouvé.</p>
            <?php endif; ?>
            <a href="index.php" class="btn btn-primary mt-4">Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>
