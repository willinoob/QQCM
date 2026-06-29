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
    if (antiTriche.enPause) return;

    if (!document.fullscreenElement) {
        declencherAvertissement("Vous avez quitté le mode plein écran.");
    }
});

document.addEventListener('visibilitychange', function() {
    if (!antiTriche.actif) return;
    if (antiTriche.enPause) return;

    if (document.hidden) {
        declencherAvertissement("Vous avez quitté l'onglet du QCM.");
    }
});

window.addEventListener('blur', function() {
    if (!antiTriche.actif) return;
    if (antiTriche.enPause) return;

    declencherAvertissement("Vous avez quitté la fenêtre du QCM.");
});

function declencherAvertissement(message) {
    if (!antiTriche.actif) return;
    if (antiTriche.enPause) return;

    const maintenant = Date.now();
    if (maintenant - antiTriche.dernierAvertissementTimestamp < 500) {
        return;
    }
    antiTriche.dernierAvertissementTimestamp = maintenant;

    antiTriche.nombreAvertissements++;

    if (antiTriche.nombreAvertissements >= antiTriche.maxAvertissements) {
        annulerPourTriche();
        return;
    }

    antiTriche.enPause = true;
    arreterTimer();

    const texte = "Attention ! " + message +
        " (Avertissement " + antiTriche.nombreAvertissements + "/" + antiTriche.maxAvertissements + ").";

    afficherOverlay(texte);

    demarrerChronoSortie();
}

function demarrerChronoSortie() {
    antiTriche.chronoSortie = setTimeout(function() {
        annulerPourTriche();
    }, 30000);
}

function arreterChronoSortie() {
    if (antiTriche.chronoSortie) {
        clearTimeout(antiTriche.chronoSortie);
        antiTriche.chronoSortie = null;
    }
}

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

function masquerOverlay() {
    const overlay = document.getElementById('overlay-avertissement');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

function continuerQcm() {
    arreterChronoSortie();
    masquerOverlay();
    antiTriche.enPause = false;
    demanderPleinEcran();
    demarrerTimer();
}

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

function terminerQcm() {
    arreterChronoSortie();
    if (document.fullscreenElement) {
        antiTriche.actif = false;
        arreterTimer();
        const formulaire = document.getElementById('formulaire-qcm');
        if (formulaire) {
            formulaire.submit();
        }
    } else {
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

function demarrerTimer() {
    afficherTemps(antiTriche.tempsRestant);

    antiTriche.intervalleTimer = setInterval(function() {
        antiTriche.tempsRestant--;
        afficherTemps(antiTriche.tempsRestant);

        if (antiTriche.tempsRestant <= 0) {
            arreterTimer();
            soumissionAutomatique();
        }
    }, 1000);
}

function arreterTimer() {
    if (antiTriche.intervalleTimer) {
        clearInterval(antiTriche.intervalleTimer);
        antiTriche.intervalleTimer = null;
    }
}

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

function soumissionAutomatique() {
    antiTriche.actif = false;

    const formulaire = document.getElementById('formulaire-qcm');
    if (formulaire) {
        formulaire.submit();
    }
}

function activerBlocageClicDroit(){
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
        console.error('Erreur de communication avec le serveur :', erreur);
    });
}

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