<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$adminEmail = "ton-email@domaine.com"; // Remplace par ton email réel
$fromEmail = "avis@ton-domaine.com"; // Remplace par l'email d'envoi

// Log des données reçues
file_put_contents('debug-submit.log', "Requête reçue: " . print_r($_POST, true), FILE_APPEND);

// Vérifier que la requête est bien de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    file_put_contents('debug-submit.log', "Méthode incorrecte: " . $_SERVER['REQUEST_METHOD'], FILE_APPEND);
    echo json_encode([
        'success' => false,
        'message' => 'Méthode de requête invalide.'
    ]);
    exit;
}

// Récupérer les données du formulaire
$nom = isset($_POST['nom']) ? trim(htmlspecialchars($_POST['nom'])) : '';
$email = isset($_POST['email']) ? trim(htmlspecialchars($_POST['email'])) : '';
$societe = isset($_POST['societe']) ? trim(htmlspecialchars($_POST['societe'])) : '';
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$titre = isset($_POST['titre']) ? trim(htmlspecialchars($_POST['titre'])) : '';
$contenu = isset($_POST['contenu']) ? trim(htmlspecialchars($_POST['contenu'])) : '';
$date = date('d/m/Y');

// Log des données
file_put_contents('debug-submit.log', "Données: nom=$nom, email=$email, societe=$societe, rating=$rating, titre=$titre", FILE_APPEND);

// Vérifier que toutes les données requises sont présentes
if (empty($nom) || empty($email) || empty($societe) || $rating < 1 || empty($titre) || empty($contenu)) {
    file_put_contents('debug-submit.log', "Champs manquants", FILE_APPEND);
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
$message = "Nouvel avis pour $societe par $nom. Note: $rating/5. Titre: $titre";

// Log de l'email
file_put_contents('debug-submit.log', "Email préparé: sujet=$subject", FILE_APPEND);

// En-têtes pour l'email HTML
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: $fromEmail" . "\r\n";

// Stocker l'avis en attente dans un fichier
$pendingAvisFile = 'pending-avis-demenageurs.json';
$pendingAvisList = [];

// Lire les avis existants
if (file_exists($pendingAvisFile)) {
    $pendingAvisList = json_decode(file_get_contents($pendingAvisFile), true);
    file_put_contents('debug-submit.log', "Avis existants chargés: " . count($pendingAvisList), FILE_APPEND);
} else {
    file_put_contents('debug-submit.log', "Fichier $pendingAvisFile non trouvé, création d'une nouvelle liste", FILE_APPEND);
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
file_put_contents('debug-submit.log', "Nouvel avis ajouté. Total: " . count($pendingAvisList), FILE_APPEND);

// Sauvegarder la liste mise à jour
$saveResult = file_put_contents($pendingAvisFile, json_encode($pendingAvisList, JSON_PRETTY_PRINT));
file_put_contents('debug-submit.log', "Fichier sauvegardé: " . ($saveResult !== false ? "OK" : "Échec"), FILE_APPEND);

// Envoyer l'email
$emailSent = false;
if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
    $emailSent = mail($adminEmail, $subject, $message, $headers);
    file_put_contents('debug-submit.log', "Email envoyé: " . ($emailSent ? "OK" : "Échec"), FILE_APPEND);
} else {
    file_put_contents('debug-submit.log', "Email admin invalide: $adminEmail", FILE_APPEND);
}

// Répondre au client
if ($emailSent !== false && $saveResult !== false) {
    file_put_contents('debug-submit.log', "Succès complet", FILE_APPEND);
    echo json_encode([
        'success' => true,
        'message' => 'Avis soumis avec succès. Il sera publié après validation.',
        'debug' => [
            'avis' => $newAvis,
            'email_sent' => $emailSent,
            'file_saved' => $saveResult !== false
        ]
    ]);
} else {
    file_put_contents('debug-submit.log', "Échec: email=" . ($emailSent ? "OK" : "Échec") . ", fichier=" . ($saveResult !== false ? "OK" : "Échec"), FILE_APPEND);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'envoi de l\'avis.',
        'debug' => [
            'email_sent' => $emailSent,
            'file_saved' => $saveResult !== false,
            'pending_file_content' => file_exists($pendingAvisFile) ? "Fichier existe" : "Fichier introuvable"
        ]
    ]);
}
?>
