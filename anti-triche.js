// L'état central du système : il garde en mémoire tout ce qui se passe pendant un QCM
const antiTriche = {
    actif: false,
    enPause: false,
    nombreAvertissements: 0,
    maxAvertissements: 3,
    idTentative: null,
    dureeTotale: 0,
    tempsRestant: 0,
    intervalleTimer: null,
    dernierAvertissementTimestamp: 0,
    chronoSortie: null,
};


// On lance le QCM quand l'utilisateur clique sur le bouton "Démarrer"
function demarrerQcm(idTentative, dureeSecondes) {
    if (antiTriche.actif) {
        return;
    }

    antiTriche.actif = true;
    antiTriche.enPause = false;
    antiTriche.idTentative = idTentative;
    antiTriche.dureeTotale = dureeSecondes;
    antiTriche.tempsRestant = dureeSecondes;
    antiTriche.nombreAvertissements = 0;

    // On cache l'écran de démarrage et on affiche les questions
    const ecranDemarrage = document.getElementById('ecran-demarrage');
    const ecranQcm = document.getElementById('ecran-qcm');
    if (ecranDemarrage) ecranDemarrage.style.display = 'none';
    if (ecranQcm) ecranQcm.style.display = 'block';

    demanderPleinEcran();
    demarrerTimer();
    activerBlocageCopierColler();
    activerBlocageClicDroit();
    activerBlocageSelection();
    activerBlocageRaccourcis();
}

// On force le passage en plein écran (avec les variantes selon le navigateur)
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


// On surveille si l'utilisateur quitte le plein écran
document.addEventListener('fullscreenchange', function() {
    if (!antiTriche.actif) return;
    if (antiTriche.enPause) return;

    if (!document.fullscreenElement) {
        declencherAvertissement("Vous avez quitté le mode plein écran.");
    }
});

// On surveille si l'utilisateur change d'onglet ou minimise la fenêtre
document.addEventListener('visibilitychange', function() {
    if (!antiTriche.actif) return;
    if (antiTriche.enPause) return;

    if (document.hidden) {
        declencherAvertissement("Vous avez quitté l'onglet du QCM.");
    }
});

// On surveille si la fenêtre du QCM perd le focus
window.addEventListener('blur', function() {
    if (!antiTriche.actif) return;
    if (antiTriche.enPause) return;

    declencherAvertissement("Vous avez quitté la fenêtre du QCM.");
});


// On gère un avertissement quand une infraction est détectée
function declencherAvertissement(message) {
    if (!antiTriche.actif) return;
    if (antiTriche.enPause) return;

    // On ignore deux infractions qui arrivent en même temps (moins de 500ms d'écart)
    const maintenant = Date.now();
    if (maintenant - antiTriche.dernierAvertissementTimestamp < 500) {
        return;
    }
    antiTriche.dernierAvertissementTimestamp = maintenant;

    antiTriche.nombreAvertissements++;

    // Au 3e avertissement, on annule directement la tentative
    if (antiTriche.nombreAvertissements >= antiTriche.maxAvertissements) {
        annulerPourTriche();
        return;
    }

    // Sinon on met le QCM en pause et on affiche l'avertissement
    antiTriche.enPause = true;
    arreterTimer();

    const texte = "Attention ! " + message +
        " (Avertissement " + antiTriche.nombreAvertissements + "/" + antiTriche.maxAvertissements + ").";

    afficherOverlay(texte);

    demarrerChronoSortie();
}

// On affiche la fenêtre d'avertissement avec son message
function afficherOverlay(texte) {
    const overlay = document.getElementById('overlay-avertissement');
    const messageElement = document.getElementById('message-avertissement');

    if (messageElement) {
        messageElement.textContent = texte;
    }
    if (overlay) {
        overlay.style.display = 'flex';
    }
}

// On cache la fenêtre d'avertissement
function masquerOverlay() {
    const overlay = document.getElementById('overlay-avertissement');
    if (overlay) {
        overlay.style.display = 'none';
    }
}


// On lance un compte à rebours invisible de 30 secondes pendant un avertissement
function demarrerChronoSortie() {
    antiTriche.chronoSortie = setTimeout(function() {
        annulerPourTriche();
    }, 30000);
}

// On annule ce compte à rebours si l'utilisateur réagit à temps
function arreterChronoSortie() {
    if (antiTriche.chronoSortie) {
        clearTimeout(antiTriche.chronoSortie);
        antiTriche.chronoSortie = null;
    }
}


// L'utilisateur clique "Continuer" : il revient au QCM
function continuerQcm() {
    arreterChronoSortie();
    masquerOverlay();
    antiTriche.enPause = false;
    demanderPleinEcran();
    demarrerTimer();
}

// L'utilisateur clique "Arrêter" : il abandonne volontairement
function arreterQcm() {
    arreterChronoSortie();
    masquerOverlay();
    antiTriche.actif = false;
    arreterTimer();

    envoyerAuServeur('finaliser_tentative.php', {
        id_t: antiTriche.idTentative,
        action: 'abandon'
    }, function() {
        window.location.href = 'resultat_modif.php';
    });
}


// L'utilisateur clique "Terminer" : on valide seulement s'il est en plein écran
function terminerQcm() {
    arreterChronoSortie();

    if (document.fullscreenElement) {
        // En plein écran : on envoie ses réponses normalement
        antiTriche.actif = false;
        arreterTimer();
        const formulaire = document.getElementById('formulaire-qcm');
        if (formulaire) {
            formulaire.submit();
        }
    } else {
        // Hors plein écran : on considère que les conditions ne sont pas respectées
        antiTriche.actif = false;
        arreterTimer();
        envoyerAuServeur('finaliser_tentative.php', {
            id_t: antiTriche.idTentative,
            action: 'triche'
        }, function() {
            window.location.href = 'resultat_modif.php';
        });
    }
}


// On annule la tentative pour triche (3 avertissements ou 30 secondes hors plein écran)
function annulerPourTriche() {
    arreterChronoSortie();
    antiTriche.actif = false;
    arreterTimer();

    alert("Votre tentative a été annulée suite à plusieurs infractions détectées.");

    envoyerAuServeur('finaliser_tentative.php', {
        id_t: antiTriche.idTentative,
        action: 'triche'
    }, function() {
        window.location.href = 'acceuil.php';
    });
}


// On lance le compte à rebours du temps restant, mis à jour chaque seconde
function demarrerTimer() {
    afficherTemps(antiTriche.tempsRestant);

    antiTriche.intervalleTimer = setInterval(function() {
        antiTriche.tempsRestant--;
        afficherTemps(antiTriche.tempsRestant);

        // Quand le temps est écoulé, on soumet automatiquement le QCM
        if (antiTriche.tempsRestant <= 0) {
            arreterTimer();
            soumissionAutomatique();
        }
    }, 1000);
}

// On arrête le timer (pendant une pause ou à la fin du QCM)
function arreterTimer() {
    if (antiTriche.intervalleTimer) {
        clearInterval(antiTriche.intervalleTimer);
        antiTriche.intervalleTimer = null;
    }
}

// On affiche le temps restant au format minutes:secondes
function afficherTemps(secondes) {
    if (secondes < 0) secondes = 0;
    const minutes = Math.floor(secondes / 60);
    const sec = secondes % 60;
    const affichage = minutes + ':' + (sec < 10 ? '0' : '') + sec;

    const elementTimer = document.getElementById('timer-qcm');
    if (elementTimer) {
        elementTimer.textContent = affichage;
    }
}

// Quand le temps atteint zéro, on envoie les réponses automatiquement
function soumissionAutomatique() {
    antiTriche.actif = false;

    const formulaire = document.getElementById('formulaire-qcm');
    if (formulaire) {
        formulaire.submit();
    }
}


// On empêche le clic droit pendant le QCM
function activerBlocageClicDroit() {
    document.addEventListener('contextmenu', function(event) {
        if (antiTriche.actif) {
            event.preventDefault();
        }
    });
}

// On empêche le copier, le coller et le couper pendant le QCM
function activerBlocageCopierColler() {
    document.addEventListener('copy', function(event) {
        if (antiTriche.actif) {
            event.preventDefault();
        }
    });
    document.addEventListener('paste', function(event) {
        if (antiTriche.actif) {
            event.preventDefault();
        }
    });
    document.addEventListener('cut', function(event) {
        if (antiTriche.actif) {
            event.preventDefault();
        }
    });
}

// On empêche de sélectionner le texte pendant le QCM
function activerBlocageSelection() {
    document.addEventListener('selectstart', function(event) {
        if (antiTriche.actif) {
            event.preventDefault();
        }
    });
}

// On bloque les raccourcis qui ouvrent les outils de développement et le code source
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


// On envoie des données au serveur en arrière-plan, sans recharger la page
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
        if (callback) {
            callback(data);
        }
    })
    .catch(function(erreur) {
        console.error('Erreur de communication avec le serveur :', erreur);
    });
}