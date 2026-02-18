<?php
// Configuration
$adminEmail = "mathieu@expertdem.com"; // Remplace par ton email d'administration
$fromEmail = "mathieu@expertdem.com"; // Remplace par l'email d'envoi

// Récupérer les données du formulaire
$nom = isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : '';
$email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '';
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$titre = isset($_POST['titre']) ? htmlspecialchars($_POST['titre']) : '';
$contenu = isset($_POST['contenu']) ? htmlspecialchars($_POST['contenu']) : '';
$date = date('d/m/Y');

// Générer un ID unique pour cet avis
$avisId = uniqid();

// Préparer le contenu de l'email
$subject = "Nouvel avis client à valider";

$message = "
<html>
<head>
    <title>Nouvel avis client à valider</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1e3a8a; color: white; padding: 15px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .button { display: inline-block; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>Nouvel avis client à valider</h2>
        </div>
        <div class='content'>
            <h3>Un nouvel avis client a été soumis et nécessite validation :</h3>

            <p><strong>Auteur:</strong> $nom</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Date:</strong> $date</p>
            <p><strong>Note:</strong> $rating/5</p>
            <p><strong>Titre:</strong> $titre</p>
            <p><strong>Contenu:</strong></p>
            <p>$contenu</p>

            <div style='text-align: center; margin-top: 20px;'>
                <a href='http://ton-site.com/validate-avis-client.php?id=$avisId' class='button'>Valider cet avis</a>
                <a href='http://ton-site.com/reject-avis-client.php?id=$avisId' class='button' style='background: #ef4444;'>Rejeter cet avis</a>
            </div>
        </div>
    </div>
</body>
</html>
";
