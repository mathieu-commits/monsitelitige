// =============================================
// Gestion des avis - Demecall
// =============================================

// Attendre que la page soit entièrement chargée
document.addEventListener('DOMContentLoaded', () => {
  // Charger les avis validés et les afficher
  loadValidatedAvis();

  // Si on est sur la page de soumission d'avis, configurer le formulaire
  const avisForm = document.getElementById('avis-form');
  if (avisForm) {
    setupAvisForm();
  }

  // Si on est sur la page admin, configurer les boutons de validation
  if (document.querySelector('.valider-btn')) {
    setupValidationButtons();
  }
});

// =============================================
// 1. Charger et afficher les avis validés
// =============================================
function loadValidatedAvis() {
  fetch('validated-avis.json')
    .then((response) => {
      if (!response.ok) {
        throw new Error("Erreur lors du chargement des avis validés.");
      }
      return response.json();
    })
    .then((avisList) => {
      displayAvis(avisList, 'avis-list');
    })
    .catch((error) => {
      console.error("Erreur :", error);
      document.getElementById('avis-list').innerHTML =
        "<p>Impossible de charger les avis pour le moment.</p>";
    });
}

// =============================================
// 2. Afficher les avis dans le conteneur spécifié
// =============================================
function displayAvis(avisList, containerId) {
  const container = document.getElementById(containerId);
  if (!container) return;

  if (avisList.length === 0) {
    container.innerHTML = "<p>Aucun avis pour le moment.</p>";
    return;
  }

  container.innerHTML = avisList
    .map(
      (avis) => `
      <div class="avis-item">
        <h3>${avis.nom || "Anonyme"}</h3>
        <div class="note">Note : ${avis.note || "?"}/5</div>
        <p>${avis.commentaire || ""}</p>
        <small>Posté le ${avis.date ? new Date(avis.date).toLocaleDateString("fr-FR") : "?"}</small>
      </div>
    `
    )
    .join("");
}

// =============================================
// 3. Configurer le formulaire de soumission d'avis
// =============================================
function setupAvisForm() {
  const form = document.getElementById('avis-form');
  form.addEventListener('submit', (e) => {
    e.preventDefault();

    // Récupérer les valeurs du formulaire
    const nom = document.getElementById('nom').value;
    const note = document.getElementById('note').value;
    const commentaire = document.getElementById('commentaire').value;

    // Créer un nouvel avis
    const nouvelAvis = {
      id: Date.now(), // ID unique basé sur le timestamp
      nom: nom,
      note: parseInt(note),
      commentaire: commentaire,
      date: new Date().toISOString().split('T')[0],
    };

    // Ajouter l'avis aux avis en attente
    addPendingAvis(nouvelAvis);

    // Réinitialiser le formulaire
    form.reset();

    // Afficher un message de confirmation
    alert("Merci pour votre avis ! Il sera validé sous peu.");
  });
}

// =============================================
// 4. Ajouter un avis en attente de validation
// =============================================
function addPendingAvis(avis) {
  fetch('pending-avis.json')
    .then((response) => response.json())
    .then((pendingAvis) => {
      pendingAvis.push(avis);

      // Ici, en conditions réelles, tu devrais envoyer les données vers un serveur
      // Pour l'instant, on simule avec localStorage (à adapter selon ton besoin)
      localStorage.setItem('pendingAvis', JSON.stringify(pendingAvis));
      console.log("Avis ajouté aux avis en attente :", avis);
    })
    .catch((error) => {
      console.error("Erreur lors de l'ajout de l'avis :", error);
    });
}

// =============================================
// 5. Configurer les boutons de validation (page admin)
// =============================================
function setupValidationButtons() {
  document.querySelectorAll('.valider-btn').forEach((button) => {
    button.addEventListener('click', (e) => {
      const avisId = parseInt(e.target.dataset.avisId);
      validateAvis(avisId);
    });
  });
}

// =============================================
// 6. Valider un avis (déplacer de pending à validated)
// =============================================
function validateAvis(avisId) {
  // Récupérer les avis en attente
  let pendingAvis = JSON.parse(localStorage.getItem('pendingAvis')) || [];

  // Trouver l'avis à valider
  const avisIndex = pendingAvis.findIndex((avis) => avis.id === avisId);
  if (avisIndex === -1) {
    console.error("Avis non trouvé.");
    return;
  }

  // Récupérer l'avis
  const avisToValidate = pendingAvis[avisIndex];

  // Ajouter à la liste des avis validés
  fetch('validated-avis.json')
    .then((response) => response.json())
    .then((validatedAvis) => {
      validatedAvis.push(avisToValidate);

      // Sauvegarder les avis validés (simulé avec localStorage)
      localStorage.setItem('validatedAvis', JSON.stringify(validatedAvis));

      // Retirer l'avis de la liste des avis en attente
      pendingAvis.splice(avisIndex, 1);
      localStorage.setItem('pendingAvis', JSON.stringify(pendingAvis));

      // Rafraîchir l'affichage
      loadValidatedAvis();
      alert(`Avis de ${avisToValidate.nom} validé avec succès !`);
    })
    .catch((error) => {
      console.error("Erreur lors de la validation de l'avis :", error);
    });
}
