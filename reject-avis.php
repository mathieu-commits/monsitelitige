<?php
// Récupérer l'ID de l'avis à rejeter
$avisId = isset($_GET['id']) ? $_GET['id'] : '';

// Lire les avis en attente
$pendingAvisFile = 'pending-avis.json';

if (!file_exists($pendingAvisFile)) {
    die("Aucun avis en attente trouvé.");
}

$pendingAvisList = json_decode(file_get_contents($pendingAvisFile), true);
$found = false;
$avisToReject = null;

// Trouver et supprimer l'avis à rejeter
foreach ($pendingAvisList as $key => $avis) {
    if ($avis['id'] === $avisId) {
        $found = true;
        $avisToReject = $avis;
        unset($pendingAvisList[$key]);
        break;
    }
}

// Sauvegarder les avis en attente mis à jour
file_put_contents($pendingAvisFile, json_encode(array_values($pendingAvisList), JSON_PRETTY_PRINT));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Avis rejeté</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .success { color: red; font-size: 1.2rem; margin: 20px; }
        .button { display: inline-block; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Avis rejeté</h1>
    <?php if ($found && $avisToReject): ?>
        <div class="success">
            L'avis pour <?php echo htmlspecialchars($avisToReject['societe']); ?>
            soumis par <?php echo htmlspecialchars($avisToReject['nom']); ?>
            a été rejeté.
        </div>
    <?php else: ?>
        <div class="success" style="color: red;">
            Avis non trouvé ou déjà traité.
        </div>
    <?php endif; ?>
    <a href="avis-demenageurs.html" class="button">Retour aux avis</a>
</body>
</html>
