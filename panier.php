<?php
session_start();
require 'bd.php';
$db = Database::connect();

$produits = [];

//récupérer les jouets au hasard :
$stmt = $db->query("SELECT * FROM toys ORDER BY RAND() LIMIT 5");
$toys = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(isset($_GET['error']) && $_GET['error'] === 'toy_already_exists') {
    echo "<div class='alert alert-warning'>Un jouet est déjà présent dans votre panier.</div>";
}

if(isset($_SESSION['utilisateur']))
{
    $userId = $_SESSION['utilisateur'];
    $stmt = $db->prepare("SELECT panier.id_item, panier.qte, panier.prix AS prix_panier, items.name, items.image, items.price AS prix_item
                        FROM panier
                        JOIN items ON panier.id_item = items.id
                        WHERE panier.userTemp = ?");
    $stmt->execute([$userId]);
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
else if(isset($_SESSION['commande_temp']))
{
    $commandeTempId = $_SESSION['commande_temp'];
    $stmt = $db->prepare("SELECT panier.id_item, panier.qte, panier.prix AS prix_panier, items.name, items.image, items.price AS prix_item
                        FROM panier
                        JOIN items ON panier.id_item = items.id
                        WHERE panier.userTemp = ?");
    $stmt->execute([$commandeTempId]);
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC); 
}
$canGetToy = false;
$jouetAssocie = [];
$hasChildMenuCheese = false;
$hasToyIncart = false;
$toyAlreadyExists = false;
$menuEnfantCount = 0; 
$toyCount = 0;

if(!empty($produits)) 
{
    foreach ($produits as $key => $produit) {
        // Vérifier si toutes les données nécessaires sont présentes
        if (!isset($produit['id_item'], $produit['prix_item'], $produit['qte']))
        {
            unset($produits[$key]); // Retirer le produit s'il manque des données
            continue;
        }

        if(isset($produit['name']) && strpos($produit['name'], 'Menu Enfant') !== false)
        {
            $menuEnfantCount += $produit['qte'];
        }
    }


    // Récupérer le nombre de jouets dans le panier
    $stmt = $db->prepare("SELECT COUNT(id_toy) FROM panier WHERE id_toy IS NOT NULL AND userTemp = ?");
    $stmt->execute([isset($userId) ? $userId : $commandeTempId]);
    $toyCount = $stmt->fetchColumn();

    // Vérifier si l'utilisateur peut ajouter un jouet
    $canGetToy = $menuEnfantCount > $toyCount;

    // Vérifier si un jouet est présent dans le panier
    $hasToyIncart = false;

    $stmt = $db->prepare("SELECT COUNT(*) FROM panier WHERE id_toy IS NOT NULL AND userTemp = ?");
    $stmt->execute([isset($userId) ? $userId : $commandeTempId]);
    $toyCount = $stmt->fetchColumn();

    // Si un ou plusieurs jouets sont présents, définir $hasToyInCart sur true
    $hasToyInCart = $toyCount > 0;

    // Si un jouet est présent, récupérer ses détails
    if ($hasToyInCart) {
        $stmt = $db->prepare("SELECT toys.name, toys.description, toys.image 
                            FROM panier
                            JOIN toys ON panier.id_toy = toys.id
                            WHERE panier.id_toy IS NOT NULL AND panier.userTemp = ?");
        $stmt->execute([isset($userId) ? $userId : $commandeTempId]);
        $jouetAssocie = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



   
    // Sauvegarder les articles confirmés pour la page de confirmation
    $_SESSION['confirmation_articles'] = $produits;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <style>

        .card-container {
            width: 200px;
            height: 350px;
            perspective: 1000px;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.6s ease-in-out;
        }

        .card-container:hover .card {
            transform: scale(1.1);
            transition: transform 0.3s ease-in-out;
        }

        .card-container.start-left {
        left: -200px; /* Position de départ à gauche */
        }

        .card-container.start-right {
            right: -200px; /* Position de départ à droite */
        }

        .card-container.center {
            left: 50%; /* Position au centre */
            transform: translateX(-50%); /* Centrage horizontal */
            transition: all 0.6s ease-in-out; /* Animation de déplacement */
        }

        .card-container.stack {
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%) rotate(0deg); /* Position finale en pile */
            transition: transform 0.6s ease-in-out;
        }

        .card {
            width: 100%;
            height: 100%;
            border-radius: 15px;
            position: relative;
            transform-style: preserve-3d;
            transform: rotateY(0);
            transition: transform 0.6s;
        }

        .card.flip {
            transform: rotateY(180deg);
        }

        .card-front, .card-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 15px;
        }

        .card-front {
            background-color: white;
        }

        .card-back {
            background-color: blue;
            transform: rotateY(180deg);
            color: white;
            font-weight: bold;
        }

        .cards-wrapper {
        display: flex; /* Maintient l'affichage en ligne initial */
        justify-content: space-around;
        align-items: center;
        width: 100%;
        height: 550px;
        position: relative; /* Nécessaire pour le positionnement absolu des cartes */
        }

        .cards-wrapper.grouped {
        display: block; /* Passe en mode superposition pour regrouper les cartes */
        position: relative;
        }

        /* Désactiver les interactions pour les cartes inactives */
        .inactive {
            pointer-events: none;
        }

        /* Désactiver l'effet d'agrandissement pour les cartes non au sommet après mélange */
        .inactive:hover .card {
            transform: none; 
        }

        /* Effet pour la carte au sommet */
        .top-card:hover .card {
            transform: scale(1.1) rotate(0deg); /* La carte au sommet peut encore s'agrandir */
            transition: transform 0.3s ease-in-out;
        }

        .card-container:hover:(.inactive) .card {
            transform: scale(1.1);
            transition: transform 0.3s ease-in-out;
        }



    </style>
</head>
<body>
    <div class="cart">
        <?php if(empty($produits)): ?>
            <div class="alert alert-danger text-center">
                Votre panier est vide !
            </div>
            <div class="actions text-center">
                <a href="index.php" class="btn btn-primary">Retour</a>
            </div>
            <?php else: ?>
                        <!-- Bouton acheter si l'utilisateur est connecté -->
                <div class="actions" style="margin-bottom : 150px;">
                        <a href="index.php" class="btn btn-primary">Retour</a>
                        <?php if (!empty($produits)): ?>
                            <?php if (isset($_SESSION['utilisateur'])): ?>
                                <a href="acheterREQ.php" class="btn btn-success">Acheter</a>
                                <div class="actions text-center" style="margin-top: 20px;">
                                    <a href="index.php" class="btn btn-primary">Retour</a>
                                </div>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-warning">Connectez-vous pour acheter</a>
                                
                            <?php endif; ?>
                        <?php endif; ?>
                 </div>
                </div>
                <div class="cart-content" style="width : 70%; margin-left : 10%;">
                <div class="cart-container" style=" background :#FAEBD7; top : auto;">
                    <table class="table table-bordered mb-3">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Image</th>
                                <th>Produit</th>
                                <th>Prix unitaire</th>
                                <th>Quantité</th> 
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>    
                        <?php
                            $totalHT = 0;
                            foreach ($produits as $produit) 
                            {
                                if ($produit) 
                                { // Vérifie que le produit est bien récupéré
                                    $totalProduit = $produit['prix_item'] * $produit['qte'];
                                    $totalHT += $totalProduit;

                                ?>
                                <tr>
                                    <td>
                                        <a href="removePanierREQ.php?id=<?= $produit['id_item'] ?>"
                                        onclick="return confirm('Etes-vous sûr de vouloir supprimer ce produit de votre panier ?')">
                                            <i class="bi bi-archive"></i>
                                        </a>
                                    </td>
                                    <td><img src="images/<?= $produit['image'] ?>" alt="<?= $produit['name'] ?>" style="width:100px"></td>
                                    <td><?= $produit['name'] ?></td>
                                    <td><?= $produit['prix_item'] ?> €</td>
                                    <td>
                                        <div class="quantity">
                                            <a href="updateCartREQ.php?id=<?= $produit['id_item'] ?>&action=decrease" class="btn">-</a>
                                            <span><?= $produit['qte'] ?></span>
                                            <a href="updateCartREQ.php?id=<?= $produit['id_item'] ?>&action=increase" class="btn">+</a>
                                        </div>
                                    </td>
                                    <td><?= number_format($totalProduit, 2) ?> €</td>
                                </tr>

                                <?php 
                                } 
                                
                                else
                                {
                                    echo "<tr><td colspan='6'>Erreur : produit introuvable.</td></tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>

                    <?php
                    //on vérifie ici s'il y a un jouet dans le panier
                    $hasToy= false;
                    $stmt = $db->prepare("SELECT COUNT(*) FROM panier WHERE id_toy IS NOT NULL AND userTemp = ?");
                    // Utiliser $commandeTempId si l'utilisateur n'est pas connecté
                    $idToUse = isset($userId) ? $userId : (isset($commandeTempId) ? $commandeTempId : null);

                    // Vérifier que $idToUse est défini avant d'exécuter la requête
                    if ($idToUse !== null) 
                    {
                        $stmt = $db->prepare("SELECT COUNT(*) FROM panier WHERE id_toy IS NOT NULL AND userTemp = ?");
                        $stmt->execute([$idToUse]);
                        $hasToy = $stmt->fetchColumn() > 0;
                    }
                    else 
                    {
                        // Gérer le cas où ni $userId ni $commandeTempId ne sont définis
                        $hasToy = false;
                    }



                    // $stmt->execute([$userId]);
                    $hasToy = $stmt->fetchColumn() > 0;

                    ?>
                    <?php if (!empty($jouetAssocie)): ?>
                    <table class="table table-bordered mb-3">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Nom du Jouet</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jouetAssocie as $jouet): ?>
                                <tr>
                                    <td><img src="<?= htmlspecialchars($jouet['image']) ?>" alt="<?= htmlspecialchars($jouet['name']) ?>" style="width:100px;"></td>
                                    <td><?= htmlspecialchars($jouet['name']) ?></td>
                                    <td><?= htmlspecialchars($jouet['description']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">Aucun jouet associé trouvé dans le panier.</div>
                <?php endif; ?>

                    
                  
                </div>

                <?php if ($canGetToy): ?>
                <div style="width: 100%; height: 650px; background-color: white; margin-top: 20px;">
                
                <!-- Liste des cartes -->
                <div class="cards-wrapper" id="cardsWrapper">
                    <?php foreach ($toys as $toy): ?>
                    <div class="card-container" data-id="<?= $toy['id'] ?>">
                        <div class="card">
                            <!-- Face avant -->
                            <div class="card-front" style="display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 10px;">
                                <!-- Image du jouet -->
                                <img src="<?= $toy['image'] ?>" alt="<?= $toy['name'] ?>" style="width: 60%; height: auto; border-radius: 10px; margin-bottom: 10px;">
                                
                                <!-- Nom du jouet -->
                                <p style="font-size: 16px; font-weight: bold; margin-bottom: 5px; text-align: center;"><?= $toy['name'] ?></p>
                                
                                <!-- Description du jouet -->
                                <p style="font-size: 14px; color: gray; text-align: center;"><?= $toy['description'] ?></p>
                            </div>
                            <!-- Face arrière -->
                            <div class="card-back" style="display: flex; justify-content: center; align-items: center;">
                                Jouet Mystère !
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Bouton pour tirer un jouet au sort -->
                <div style="text-align: center; margin-top: 20px;">
                    <p>Un menu enfant est présent dans votre commande ! Vous avez le droit d'obtenir un jouet au hasard ! Complétez votre collection ! </p>
                    <button onclick="shuffleAndFlip()" style="padding: 10px 20px; font-size: 16px; background-color: blue; color: white; border: none; cursor: pointer; border-radius 20px;">
                        Retournez les cartes !
                    </button>
                </div>
                </div>
                <?php endif; ?>
                    
                    <!-- Affichage des totaux -->
                    <div class="col-lg-5">
                        <div>
                            <h5>Total panier</h5>
                            <table class="table" style="margin-top : 20px;">
                                <tbody>
                                    <tr>
                                        <td>Total produit HT</td>
                                        <td><?= number_format($totalHT, 2) ?> €</td>
                                    </tr>
                                    <tr>
                                        <td>TVA (20%)</td>
                                        <td><?= number_format($totalHT * 0.20, 2) ?> €</td>
                                    </tr>
                                    <tr>
                                        <td>TOTAL TTC</td>
                                        <td><?= number_format($totalHT * 1.20, 2) ?> €</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div> 
                    <div class="table-footer" style="height:100px; background-color : green; margin-top: 100px; margin-bottom: 100px;"></div>                   
                </div>              
       
            <?php endif; ?>

        </div>

    <script>
    let hasBeenClicked = false; // Empêche les clics multiples
    let hasLastCardBeenAdded = false; // Empêche les validations multiples    
    let canGetToy = <?= json_encode($canGetToy) ?>; 
    let toyCount = <?= json_encode($toyCount) ?>; 
    const menuEnfantCount = <?= json_encode($menuEnfantCount) ?>; 


    function shuffleAndFlip() {
        if (!toyCount > menuEnfantCount)
        {
            alert("Vous n'avez droit qu'à un menu jouet par menu enfant ! Pensez un peu aux autres :/ ! ");
            return;
        }
        if (hasBeenClicked) return; //Empêche les clics multiples
        hasBeenClicked = true; // Le bouton a été cliqué


        const cardsWrapper = document.getElementById("cardsWrapper");
        const cards = Array.from(cardsWrapper.children);
        const button = document.querySelector("button");

        // Change le texte du bouton après un clic
        button.textContent = "Mélange en cours...";
        button.disabled = true; 

        // Étape 1 : Retourner toutes les cartes
        cards.forEach((card, index) => {
            setTimeout(() => {
                const innerCard = card.querySelector(".card");
                innerCard.style.transform = ""; // Supprime toute transformation existante
                innerCard.classList.add("flip");
            }, index * 300); // Délai progressif pour chaque carte
        });

        // Étape 2 : Mélanger les cartes **après** leur retournement
        setTimeout(() => {
            // Mélanger les cartes
            const shuffledCards = shuffleArray(cards);

            // Réorganiser les cartes dans le DOM
            cardsWrapper.innerHTML = ""; // Efface les anciennes cartes
            shuffledCards.forEach((card) => cardsWrapper.appendChild(card)); // Réinsère les cartes mélangées

            // Positionner les cartes mélangées
            shuffledCards.forEach((card, index) => {
                card.style.transition = "all 1s ease-in-out";
                card.style.position = "absolute";

                // Position initiale des cartes
                card.style.left = index % 2 === 0 ? "50px" : "calc(50% + 50px)";
                card.style.top = `${30 + index * 10}px`;
            });

            // Regrouper les cartes au centre
        setTimeout(() => {
                shuffledCards.forEach((card, index) => {
                    card.style.left = `${50 - (shuffledCards.length - 1) * 5 + index * 10}%`;
                    card.style.top = "50%";
                    card.style.transform = `translate(-50%, -50%)`;
                    card.style.zIndex = index;
                });
            }, 1200); // Délai pour le regroupement
        }, cards.length * 300 + 600); // Mélange après que toutes les cartes aient été retournées

        // Étape 3 : Former un tas avec uniquement la carte du sommet interactive
        setTimeout(() => {
            cardsWrapper.classList.add("grouped");

            cards.forEach((card, index) => {
                card.style.transition =
                    "transform 0.6s ease-in-out, left 0.6s ease-in-out, top 0.6s ease-in-out";
                card.style.position = "absolute";
                card.style.left = "50%";
                card.style.top = "50%";
                card.style.transform = `translate(-50%, -50%) rotate(${index * 10}deg)`;
                card.style.zIndex = index;

                // Gérer les interactions
                if (index === cards.length - 1) {
                    // Seule la carte au sommet peut être interactive
                    card.classList.add("top-card");
                    card.classList.remove("inactive");
                } else {
                    card.classList.add("inactive");
                    card.classList.remove("top-card");
                }
            });
            button.disabled = false;
            button.textContent = "Récupérez la carte au sommet de la pile";
            button.onclick = addTopCardToCart;
        }, cards.length * 300 + 2000); // Formation du tas après le mélange
    }

    function addTopCardToCart(){
        if (hasLastCardBeenAdded) return; //empêche les validations multiples
        hasLastCardBeenAdded = true; //la carte a été ajoutée

        const topCard = document.querySelector(".top-card");
        const toyId =topCard.getAttribute("data-id");
        const toyName = topCard.querySelector(".card-front p").textContent;

        if (toyId) 
        {
            alert(`Vous avez obtenu le jouet : ${toyName} !`);
            window.location.href = `updateCartREQ.php?id=${toyId}&action=addToy`;
        }
        button.disabled = true;
    }

    // Fonction pour mélanger un tableau
    function shuffleArray(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1)); // Index aléatoire entre 0 et i
            [array[i], array[j]] = [array[j], array[i]]; // Échange des éléments

        }
        
        return array;
    }

    // Gestion de l'effet de survol avant mélange
    document.addEventListener("mouseover", (event) => {
        const card = event.target.closest(".card-container");
        if (card && !hasShuffled) {
            const innerCard = card.querySelector(".card");
            innerCard.style.transform = "scale(1.1)";
        }
    });

    // Réinitialiser l'effet de survol si la souris quitte une carte
    document.addEventListener("mouseout", (event) => {
        const card = event.target.closest(".card-container");
        if (card && !hasShuffled) {
            const innerCard = card.querySelector(".card");
            innerCard.style.transform = "scale(1)";
        }
    });

    </script>
        
</body>
</html>