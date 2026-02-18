<?php
// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$adminEmail = "mathieu@expertdem.com"; // Remplace par ton email réel
$fromEmail = "mathieu@expertdem.com"; // Remplace par l'email d'envoi

// Vérifier que la requête est bien de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode de requête invalide.'
    ]);
    exit;
}

// Récupérer les données du formulaire avec vérifications
$nom = isset($_POST['nom']) ? trim(htmlspecialchars($_POST['nom'])) : '';
$email = isset($_POST['email']) ? trim(htmlspecialchars($_POST['email'])) : '';
$societe = isset($_POST['societe']) ? trim(htmlspecialchars($_POST['societe'])) : '';
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$titre = isset($_POST['titre']) ? trim(htmlspecialchars($_POST['titre'])) : '';
$contenu = isset($_POST['contenu']) ? trim(htmlspecialchars($_POST['contenu'])) : '';
$date = date('d/m/Y');

// Vérifier que toutes les données requises sont présentes
if (empty($nom) || empty($email) || empty($societe) || $rating < 1 || empty($titre) || empty($contenu)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tous les champs sont obligatoires.'
    ]);
    exit;
}

// Générer un ID unique pour cet avis
$avisId = uniqid();

// Préparer le contenu de l'email
$subject = "Nouvel avis à valider pour $societe";

$message = "
<html>
<head>
    <title>Nouvel avis à valider</title>
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
            <h2>Nouvel avis à valider</h2>
        </div>
        <div class='content'>
            <h3>Un nouvel avis a été soumis et nécessite validation :</h3>

            <p><strong>Société:</strong> $societe</p>
            <p><strong>Auteur:</strong> $nom</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Date:</strong> $date</p>
            <p><strong>Note:</strong> $rating/5</p>
            <p><strong>Titre:</strong> $titre</p>
            <p><strong>Contenu:</strong></p>
            <p>$contenu</p>

            <div style='text-align: center; margin-top: 20px;'>
                <a href='http://ton-site.com/validate-avis.php?id=$avisId' class='button'>Valider cet avis</a>
                <a href='http://ton-site.com/reject-avis.php?id=$avisId' class='button' style='background: #ef4444;'>Rejeter cet avis</a>
            </div>
        </div>
    </div>
</body>
</html>
";

// En-têtes pour l'email HTML
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: $fromEmail" . "\r\n";

// Créer le fichier JSON si nécessaire
$pendingAvisFile = 'pending-avis.json';
if (!file_exists($pendingAvisFile)) {
    file_put_contents($pendingAvisFile, '[]');
}

// Lire les avis existants
$pendingAvisList = json_decode(file_get_contents($pendingAvisFile), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $pendingAvisList = [];
}

// Créer le nouvel avis
$newAvis = [
    'id' => $avisId,
    'nom' => $nom,
    'email' => $email,
    'societe' => $societe,
    'rating' => $rating,
    'titre' => $titre,
    'contenu' => $contenu,
    'date' => $date,
    'status' => 'pending'
];

// Ajouter le nouvel avis à la liste
$pendingAvisList[] = $newAvis;

// Sauvegarder la liste mise à jour
$saveResult = file_put_contents($pendingAvisFile, json_encode($pendingAvisList, JSON_PRETTY_PRINT));

// Envoyer l'email
$emailSent = false;
if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
    $emailSent = mail($adminEmail, $subject, $message, $headers);
}

// Créer un fichier de log pour le débogage
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'avis' => $newAvis,
    'email_sent' => $emailSent,
    'file_saved' => $saveResult !== false,
    'pending_file_content' => file_exists($pendingAvisFile) ? file_get_contents($pendingAvisFile) : 'File not found'
];

file_put_contents('debug-log.json', json_encode($logData, JSON_PRETTY_PRINT));

// Répondre au client
if ($emailSent && $saveResult !== false) {
    echo json_encode([
        'success' => true,
        'message' => 'Avis soumis avec succès. Il sera publié après validation.',
        'debug' => $logData
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'envoi de l\'avis.',
        'debug' => $logData
    ]);
}
?>
