<?php
session_start(); //j'ai ajouté cette ligne pour qu'on puisse vérifier si l'utilisateur est connecté - Clément M 
require 'bd.php';
$db = Database::connect();

// la catégorie active par défaut est la première
$currentActiveCategoryId = null;

if(isset($_GET['category']))
{
    $currentActiveCategoryId = intval($_GET['category']);
}
else
{
    $currentActiveCategoryId = 1;
}

//on vérifie si la commande temporaire existe, si non on en crée une
if (!isset($_SESSION['commande_temp'])) 
{
    // si l'utilisateur est connecté, on utilise son identifiant pour gérer le panier
    if(isset($_SESSION['utilisateur']))
    {
        $_SESSION['commande_temp'] = $_SESSION['utilisateur'];
    }
    //sinon on génère un identifiant temporaire
    else
    {
        $_SESSION['commande_temp'] = uniqid('temp_', true);
    }
}


$query = "SELECT * FROM categories";
$parentCateg = $db->query($query)->fetchALL(PDO::FETCH_ASSOC);

$query2 = "SELECT * FROM items";
$parentProduit = $db->query($query2)->fetchALL(PDO::FETCH_ASSOC);


// var_dump($parentCateg);
// die();

?>


<!DOCTYPE html>
<html>
    <head>
        <title>Burger Code</title>
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
        
        <link href='http://fonts.googleapis.com/css?family=Holtwood+One+SC' rel='stylesheet' type='text/css'>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
        <link rel="stylesheet" href="styles.css">
        
    </head>
    <body>
        <div class="container site">
           
            <!-- j'ai modifié cette partie Clément M -->
            <div style="text-align:center; display:flex; justify-content:space-around; align-items:center;" class="text-logo">
                <h1 style="position : relative; right : -25%;">Burger Doe</h1>
       
                <div class="login-inscription "style="display: flex; position : relative; right : -550px; top : -25px">
                    <a href="inscription.php" class="bi bi-person-plus" style="position: absolute; right : 220px;"> </a>


                    <?php if (isset($_SESSION['utilisateur'])): ?>
                    <!-- Bouton Mon compte si l'utilisateur est connecté -->
                        <a href="moncompte.php" class="bi bi-person" style="margin-right: 150px;"> </a>
                    <?php else: ?>
                        <!-- Bouton Login si l'utilisateur n'est pas connecté -->
                        <a href="login.php" class="bi bi-person-circle" style="margin-right: 150px;"></a>
                    <?php endif; ?>


                    <a href="panier.php"class="bi bi-basket3 cart-icon"> </a>
                </div>
            </div>
            
                <nav>
                <ul class="nav nav-pills" role="tablist">
                    <?php foreach ($parentCateg as $categ): ?>
                        <li class="nav-item" role="presentation">
                            <a href="?category=<?= $categ['id'] ?>" 
                            class="nav-link <?= $categ['id'] == $currentActiveCategoryId ? 'active' : '' ?>">
                                <?= htmlspecialchars($categ['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <!-- Contenu des onglets -->
            <div class="tab-content">
            <?php foreach ($parentCateg as $categ): ?>
                <div class="tab-pane <?= $categ["id"] == $currentActiveCategoryId ? "active" : "" ?>" id="<?= $categ["id"] ?>" role="tabpanel">
                    <div class="row">
                        <?php foreach ($parentProduit as $produit): ?>
                            <?php if ($produit["category"] == $categ["id"]): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="img-thumbnail">
                                        <img src="images/<?= htmlspecialchars($produit["image"]) ?>" class="img-fluid" alt="<?= htmlspecialchars($produit["name"]) ?>">
                                        <div class="price"><?= htmlspecialchars($produit["price"]) ?> €</div>
                                        <div class="caption">
                                            <h4><?= htmlspecialchars($produit["name"]) ?></h4>
                                            <p><?= htmlspecialchars($produit["description"]) ?></p>
                                            <a href="addPanierREQ.php?id=<?= $produit['id'] ?>&category=<?= $currentActiveCategoryId ?>" 
                                            class="btn btn-order" role="button">
                                                <span class="bi-cart-fill"></span> Commander
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    
    <script>
    // Récupération de la catégorie active depuis l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const activeCategoryId = urlParams.get('category') || 1;

    // Affichage dans la consoleS
    console.log('Catégorie active :', activeCategoryId);
    </script>

    </body>
</html>