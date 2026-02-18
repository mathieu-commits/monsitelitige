<?php
// Récupérer l'ID de l'avis à valider
$avisId = isset($_GET['id']) ? $_GET['id'] : '';

// Lire les avis en attente
$pendingAvisFile = 'pending-avis.json';

if (!file_exists($pendingAvisFile)) {
    die("Aucun avis en attente trouvé.");
}

$pendingAvisList = json_decode(file_get_contents($pendingAvisFile), true);
$found = false;
$avisToValidate = null;

// Trouver l'avis à valider
foreach ($pendingAvisList as $key => $avis) {
    if ($avis['id'] === $avisId) {
        $found = true;
        $avisToValidate = $avis;
        $pendingAvisList[$key]['status'] = 'validated';

        // Ajouter à la liste des avis validés
        $validatedAvisFile = 'validated-avis.json';
        $validatedAvisList = [];

        if (file_exists($validatedAvisFile)) {
            $validatedAvisList = json_decode(file_get_contents($validatedAvisFile), true);
        }

        $validatedAvisList[] = $pendingAvisList[$key];
        file_put_contents($validatedAvisFile, json_encode($validatedAvisList, JSON_PRETTY_PRINT));

        break;
    }
}

// Sauvegarder les avis en attente mis à jour
file_put_contents($pendingAvisFile, json_encode($pendingAvisList, JSON_PRETTY_PRINT));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Avis validé</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .success { color: green; font-size: 1.2rem; margin: 20px; }
        .button { display: inline-block; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Avis validé avec succès</h1>
    <?php if ($found && $avisToValidate): ?>
        <div class="success">
            L'avis pour <?php echo htmlspecialchars($avisToValidate['societe']); ?>
            soumis par <?php echo htmlspecialchars($avisToValidate['nom']); ?>
            a été validé et publié.
        </div>
    <?php else: ?>
        <div class="success" style="color: red;">
            Avis non trouvé ou déjà traité.
        </div>
    <?php endif; ?>
    <a href="avis-demenageurs.html" class="button">Retour aux avis</a>
</body>
</html>
