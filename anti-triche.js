// ============================================
// ANTI-TRICHE.JS — Surveillance pendant un QCM
// ============================================

const antiTriche = {
    actif: false,
    nombreAvertissements: 0,
    maxAvertissements: 3,
    idTentative: null,
    dureeTotale: 0,
    intervalleTimer: null,
    dernierAvertissementTimestamp: 0,
    alerteEnCours: false,
};

// ------------------------------------------------------
// 1. DÉMARRAGE GÉNÉRAL
// ------------------------------------------------------

function demarrerQcm(idTentative, dureeMinutes) {
    if (antiTriche.actif) {
        return; // un QCM est déjà en cours, on ignore ce nouveau clic
    }

    antiTriche.actif = true;
    antiTriche.idTentative = idTentative;
    antiTriche.dureeTotale = dureeMinutes;
    antiTriche.nombreAvertissements = 0;
    antiTriche.alerteEnCours = false;

    demanderPleinEcran();
    demarrerTimer();
    activerBlocageCopierColler();
    activerBlocageClicDroit();
    activerBlocageSelection();
    activerBlocageRaccourcis();
}

// ------------------------------------------------------
// 2. PLEIN ÉCRAN OBLIGATOIRE
// ------------------------------------------------------

function demanderPleinEcran() {
    const element = document.documentElement;

    if (element.requestFullscreen) {
        element.requestFullscreen();
    } else if (element.webkitRequestFullscreen) {
        element.webkitRequestFullscreen();
    } else if (element.msRequestFullscreen) {
        element.msRequestFullscreen();
    }
}

document.addEventListener('fullscreenchange', function() {
    if (!antiTriche.actif) return;

    if (!document.fullscreenElement) {
        declencherAvertissement("Attention ! Vous avez quitté le mode plein écran.");
    }
});

// ------------------------------------------------------
// 3. CHANGEMENT D'ONGLET / MINIMISATION
// ------------------------------------------------------

document.addEventListener('visibilitychange', function() {
    if (!antiTriche.actif) return;
    if (antiTriche.alerteEnCours) return;

    if (document.hidden) {
        declencherAvertissement("Attention ! Vous avez quitté l'onglet du QCM.");
    }
});

window.addEventListener('blur', function() {
    if (!antiTriche.actif) return;
    if (antiTriche.alerteEnCours) return;

    declencherAvertissement("Attention ! Vous avez quitté la fenêtre du QCM.");
});

// ------------------------------------------------------
// 4. GESTION CENTRALISÉE DES AVERTISSEMENTS
// ------------------------------------------------------

function declencherAvertissement(message) {
    if (!antiTriche.actif) return;
    if (antiTriche.alerteEnCours) return;

    const maintenant = Date.now();
    if (maintenant - antiTriche.dernierAvertissementTimestamp < 500) {
        return;
    }
    antiTriche.dernierAvertissementTimestamp = maintenant;

    antiTriche.nombreAvertissements++;

    if (antiTriche.nombreAvertissements >= antiTriche.maxAvertissements) {
        annulerTentative();
        return;
    }

    antiTriche.alerteEnCours = true;

    const texteComplet = message + " (Avertissement " +
        antiTriche.nombreAvertissements + "/" + antiTriche.maxAvertissements +
        "). Après " + antiTriche.maxAvertissements + " avertissements, votre tentative sera annulée.";

    afficherOverlayAvertissement(texteComplet);
}

// ------------------------------------------------------
// 5. OVERLAY D'AVERTISSEMENT (remplace alert() pour garder
//    un vrai geste utilisateur, nécessaire au plein écran)
// ------------------------------------------------------

function afficherOverlayAvertissement(texte) {
    const overlay = document.getElementById('overlay-avertissement');
    const messageElement = document.getElementById('message-avertissement');

    if (messageElement) {
        messageElement.textContent = texte;
    }
    if (overlay) {
        overlay.style.display = 'flex';
    }
}

function reprendreApresAvertissement() {
    const overlay = document.getElementById('overlay-avertissement');
    if (overlay) {
        overlay.style.display = 'none';
    }

    antiTriche.alerteEnCours = false;

    // Ce clic est un vrai geste utilisateur → requestFullscreen() va fonctionner
    demanderPleinEcran();
}

// ------------------------------------------------------
// 6. ANNULATION DE LA TENTATIVE
// ------------------------------------------------------

function annulerTentative() {
    antiTriche.actif = false;

    if (antiTriche.intervalleTimer) {
        clearInterval(antiTriche.intervalleTimer);
    }

    alert("Votre tentative a été annulée suite à plusieurs infractions détectées.");

    envoyerAuServeur('annuler_tentative.php', {
        id_t: antiTriche.idTentative,
        raison: 'infractions_multiples'
    }, function() {
        window.location.href = 'resultats.php?id_t=' + antiTriche.idTentative;
    });
}

// ------------------------------------------------------
// 7. TIMER
// ------------------------------------------------------

function demarrerTimer() {
    let tempsRestant = antiTriche.dureeTotale;

    afficherTemps(tempsRestant);

    antiTriche.intervalleTimer = setInterval(function() {
        tempsRestant--;
        afficherTemps(tempsRestant);

        if (tempsRestant <= 0) {
            clearInterval(antiTriche.intervalleTimer);
            soumissionAutomatique();
        }
    }, 1000);
}

function afficherTemps(secondes) {
    const minutes = Math.floor(secondes / 60);
    const sec = secondes % 60;
    const affichage = minutes + ':' + (sec < 10 ? '0' : '') + sec;

    const elementTimer = document.getElementById('timer-qcm');
    if (elementTimer) {
        elementTimer.textContent = affichage;
    }
}

function soumissionAutomatique() {
    antiTriche.actif = false;

    const formulaire = document.getElementById('formulaire-qcm');
    if (formulaire) {
        formulaire.submit();
    }
}

// ------------------------------------------------------
// 8. CLIC DROIT / COPIER-COLLER / SÉLECTION
// ------------------------------------------------------

function activerBlocageClicDroit() {
    document.addEventListener('contextmenu', function(event) {
        if (antiTriche.actif) {
            event.preventDefault();
        }
    });
}

function activerBlocageCopierColler() {
    document.addEventListener('copy', function(event) {
        if (antiTriche.actif) event.preventDefault();
    });
    document.addEventListener('paste', function(event) {
        if (antiTriche.actif) event.preventDefault();
    });
    document.addEventListener('cut', function(event) {
        if (antiTriche.actif) event.preventDefault();
    });
}

function activerBlocageSelection() {
    document.addEventListener('selectstart', function(event) {
        if (antiTriche.actif) event.preventDefault();
    });
}

// ------------------------------------------------------
// 9. COMMUNICATION AVEC LE SERVEUR
// ------------------------------------------------------

function envoyerAuServeur(url, donnees, callback) {
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(donnees)
    })
    .then(function(reponse) {
        return reponse.json();
    })
    .then(function(data) {
        if (callback) callback(data);
    })
    .catch(function(erreur) {
        console.error('Erreur lors de la communication avec le serveur :', erreur);
    });
}

// ------------------------------------------------------
// 10. BLOCAGE DES RACCOURCIS CLAVIER SENSIBLES
// ------------------------------------------------------

function activerBlocageRaccourcis() {
    document.addEventListener('keydown', function(event) {
        if (!antiTriche.actif) return;

        if (event.key === 'F12') {
            event.preventDefault();
        }

        if (event.ctrlKey && event.shiftKey &&
            (event.key === 'C' || event.key === 'I' || event.key === 'J' ||
             event.key === 'c' || event.key === 'i' || event.key === 'j')) {
            event.preventDefault();
        }

        if (event.ctrlKey && (event.key === 'u' || event.key === 'U')) {
            event.preventDefault();
        }
    });
}