<?php 
require 'bd.php';
$db = Database::connect();

$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = htmlspecialchars($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe'];

    // Vérification de l'email dans la base de données
    $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($utilisateur) 
    {
        // L'utilisateur existe, vérifions le mot de passe
        if (password_verify($mot_de_passe, $utilisateur['mot_de_passe']))
        {
            session_start();
            $_SESSION['utilisateur'] = $utilisateur['id'];


            // Associer la commande temporaire à l'utilisateur connecté
            if(isset($_SESSION['commande_temp'])) 
            {
                $commandeTempId = $_SESSION['commande_temp'];
                $userId = $_SESSION['utilisateur'];
                
                //vérifier si l'utilisateur a déjà un panier
                $stmt = $db->prepare("SELECT COUNT(*) FROM panier WHERE userTemp = ?");
                $stmt->execute([$userId]);
                $panierExistant = $stmt->fetchColumn();

                if ($panierExistant > 0)
                {
                    //rediriger vers une page pour demander à l'utilisateur de choisir
                    $_SESSION['choix_panier'] = [
                        'temp' => $commandeTempId,
                        'user' => $userId
                    ];
                    header("Location: choixPanier.php");
                    exit();
                }
                else
                {
                    $stmt = $db->prepare("UPDATE panier SET userTemp = ? WHERE userTemp = ?");
                    $stmt->execute([$userId, $commandeTempId]);
                    unset($_SESSION['commande_temp']);
                }
    
            }
            header("Location: index.php");
            exit();
        } 
        else 
        {
            // Mot de passe incorrect
            $message = '<div class="alert alert-danger" role="alert" style="text-align:center">Mot de passe incorrect !</div>';
        }
    } 
    else 
    {
        // Email non trouvé
        $message = '<div class="alert alert-danger" role="alert" style="text-align:center">Aucun compte trouvé avec cet email !</div>';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
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
                <h3>Login</h3>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="loginEmail" class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" id="loginEmail" placeholder="Enter your email" required>
                    </div>
                    <div class="mb-3">
                        <label for="loginPassword" class="form-label">Mot de passe</label>
                        <input type="password" name="mot_de_passe" class="form-control" id="loginPassword" placeholder="Enter your password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Connexion</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
