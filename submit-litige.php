<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Inclure les fichiers PHPMailer
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Configuration
$adminEmail = "ton-email@domaine.com"; // Remplace par ton email

// Vérification que la requête est bien de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode de requête invalide. Utilise POST.'
    ]);
    exit;
}

// Récupération des données du formulaire
$nom = isset($_POST['nom']) ? trim(htmlspecialchars($_POST['nom'])) : '';
$prenom = isset($_POST['prenom']) ? trim(htmlspecialchars($_POST['prenom'])) : '';
$telephone = isset($_POST['telephone']) ? trim(htmlspecialchars($_POST['telephone'])) : '';
$societe = isset($_POST['societe']) ? trim(htmlspecialchars($_POST['societe'])) : '';
$dateDem = isset($_POST['date']) ? trim(htmlspecialchars($_POST['date'])) : '';
$litigeType = isset($_POST['litige-type']) ? trim(htmlspecialchars($_POST['litige-type'])) : '';
$montant = isset($_POST['montant']) ? trim(htmlspecialchars($_POST['montant'])) : '';


// Validation des données requises
$errors = [];
if (empty($nom)) $errors[] = "Le nom est obligatoire.";
if (empty($prenom)) $errors[] = "Le prénom est obligatoire.";
if (empty($telephone)) $errors[] = "Le téléphone est obligatoire.";
if (empty($societe)) $errors[] = "La société de déménagement est obligatoire.";
if (empty($dateDem)) $errors[] = "La date du déménagement est obligatoire.";
if (empty($litigeType)) $errors[] = "Le type de litige est obligatoire.";
if (empty($montant)) $errors[] = "Le montant estimé du préjudice est obligatoire.";

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreurs de validation : ' . implode('; ', $errors)
    ]);
    exit;
}

// Gestion des fichiers joints
$preuves = [];
if (isset($_FILES['preuves'])) {
    $totalFiles = count($_FILES['preuves']['name']);
    for ($i = 0; $i < $totalFiles; $i++) {
        if ($_FILES['preuves']['error'][$i] === UPLOAD_ERR_OK) {
            $tmpFilePath = $_FILES['preuves']['tmp_name'][$i];
            $fileName = basename($_FILES['preuves']['name'][$i]);
            $targetPath = 'uploads/' . uniqid() . '_' . $fileName;

            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }

            if (move_uploaded_file($tmpFilePath, $targetPath)) {
                $preuves[] = $targetPath;
            }
        }
    }
}

// Création d'un ID unique pour ce litige
$litigeId = uniqid();

// Préparation du contenu de l'email
$emailSubject = "Nouveau litige déposé : $societe - $nom $prenom";
$emailBody = "
    <h2>Nouveau litige déposé</h2>
    <p><strong>ID du litige:</strong> $litigeId</p>
    <p><strong>Nom:</strong> $nom</p>
    <p><strong>Prénom:</strong> $prenom</p>
    <p><strong>Téléphone:</strong> $telephone</p>
    <p><strong>Société de déménagement:</strong> $societe</p>
    <p><strong>Date du déménagement:</strong> $dateDem</p>
    <p><strong>Type de litige:</strong> " . getLitigeTypeLabel($litigeType) . "</p>
    <p><strong>Montant estimé du préjudice:</strong> $montant €</p>
";

if (!empty($preuves)) {
    $emailBody .= "<p><strong>Preuves jointes:</strong></p><ul>";
    foreach ($preuves as $preuve) {
        $emailBody .= "<li><a href='http://" . $_SERVER['HTTP_HOST'] . "/" . $preuve . "'>" . basename($preuve) . "</a></li>";
    }
    $emailBody .= "</ul>";
}

// Fonction pour obtenir le libellé du type de litige
function getLitigeTypeLabel($type) {
    $types = [
        'degats' => 'Dégâts ou pertes',
        'retard' => 'Retard ou annulation',
        'frais' => 'Litiges facturation',
        'travail-dissimule' => 'Travail dissimulé'
    ];
    return $types[$type] ?? $type;
}

// Envoi de l'email avec PHPMailer
$mail = new PHPMailer(true);
try {
    // Configuration SMTP (Mailtrap pour le développement)
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'mathieu@expertdem.com';  // Remplace par ton utilisateur Mailtrap
    $mail->Password   = 'Steph77026';  // Remplace par ton mot de passe Mailtrap
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    // Pour utiliser Gmail en production, remplace par :
    /*
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'ton-email@gmail.com';
    $mail->Password   = 'ton-mot-de-passe-ou-mot-de-passe-d-application';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    */

    // Expéditeur et destinataire
    $mail->setFrom('noreply@demecall.test', 'Demecall - Nouveau Litige');
    $mail->addAddress($adminEmail);

    // Contenu de l'email
    $mail->isHTML(true);
    $mail->Subject = $emailSubject;
    $mail->Body    = $emailBody;

    // Joindre les preuves si elles existent
    foreach ($preuves as $preuve) {
        $mail->addAttachment($preuve);
    }

    $mail->send();

    // Sauvegarde des données dans un fichier JSON (optionnel)
    $litigesFile = 'litiges.json';
    $litigesList = [];

    if (file_exists($litigesFile)) {
        $litigesList = json_decode(file_get_contents($litigesFile), true);
    }

    $newLitige = [
        'id' => $litigeId,
        'nom' => $nom,
        'prenom' => $prenom,
        'telephone' => $telephone,
        'societe' => $societe,
        'dateDem' => $dateDem,
        'litigeType' => $litigeType,
        'montant' => $montant,
        'preuves' => $preuves,
        'dateDepot' => date('d/m/Y H:i:s')
    ];

    $litigesList[] = $newLitige;
    file_put_contents($litigesFile, json_encode($litigesList, JSON_PRETTY_PRINT));

    echo json_encode([
        'success' => true,
        'message' => 'Votre dossier a été envoyé avec succès. Nous vous contacterons rapidement.'
    ]);

} catch (Exception $e) {
    file_put_contents('litige-error.log', date('Y-m-d H:i:s') . " - Erreur: " . $mail->ErrorInfo . "\n", FILE_APPEND);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'envoi: ' . $mail->ErrorInfo
    ]);
}
?>
