// public/assets/js/site.js — menu plein écran sur petit écran, filet de l'en-tête au défilement,
// photos inclinées sous le pointeur, cartes des domaines, visionneuse, formulaire de contact.
(function () {
    // Safari sur iPhone et iPad n'applique :active (retour visuel à l'appui) qu'avec un écouteur tactile.
    document.addEventListener('touchstart', function () {}, { passive: true });

    var html = document.documentElement;
    var entete = document.querySelector('[data-entete]');
    var bouton = document.querySelector('[data-menu]');
    var nav = document.querySelector('[data-nav]');
    var bureau = window.matchMedia('(min-width: 880px)');
    var souris = window.matchMedia('(hover: hover) and (pointer: fine)');
    var calme = window.matchMedia('(prefers-reduced-motion: reduce)');

    // Menu : le panneau part sous l'en-tête et couvre le reste de l'écran ; la page ne défile plus.
    function basculerMenu(ouvrir) {
        if (!bouton || !nav) return;
        if (ouvrir) html.style.setProperty('--hauteur-entete', entete.offsetHeight + 'px');
        nav.classList.toggle('ouvert', ouvrir);
        html.classList.toggle('menu-ouvert', ouvrir);
        bouton.setAttribute('aria-expanded', ouvrir ? 'true' : 'false');
    }
    if (bouton && nav) {
        bouton.addEventListener('click', function () { basculerMenu(!nav.classList.contains('ouvert')); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && nav.classList.contains('ouvert')) { basculerMenu(false); bouton.focus(); }
        });
        bureau.addEventListener('change', function (e) { if (e.matches) basculerMenu(false); });
    }

    if (entete) {
        var marquer = function () { entete.classList.toggle('defile', window.scrollY > 8); };
        window.addEventListener('scroll', marquer, { passive: true });
        marquer();
    }

    // Photos : inclinaison vers le pointeur et reflet qui le suit. Pas pour les grandes photos
    // d'ouverture (accueil, vie étudiante), dont les bords sortent de l'écran.
    document.querySelectorAll('.photo').forEach(function (photo) {
        if (photo.closest('.accueil-hero-photo, .vie-hero-photo')) return;
        photo.addEventListener('pointermove', function (e) {
            if (!souris.matches || !bureau.matches || calme.matches) return;
            var r = photo.getBoundingClientRect();
            var x = (e.clientX - r.left) / r.width;
            var y = (e.clientY - r.top) / r.height;
            photo.classList.add('incline');
            photo.style.setProperty('--ry', ((x - 0.5) * 10).toFixed(2) + 'deg');
            photo.style.setProperty('--rx', ((0.5 - y) * 8).toFixed(2) + 'deg');
        });
        photo.addEventListener('pointerleave', function () {
            photo.classList.remove('incline');
            ['--rx', '--ry'].forEach(function (p) { photo.style.removeProperty(p); });
        });
    });

    // Le mur sous le pointeur : position du pointeur dans la couche de motif de chaque bande, une
    // fois par image affichée. Les bandes sable et encre étendent leur fond à toute la largeur de
    // l'écran : leur couche part du bord gauche de l'écran, pas de celui de la section.
    document.querySelectorAll('.accueil-hero, .bande-sable, .bande-encre, .bande-appel, .site-pied').forEach(function (surface) {
        var dernier = null, prevu = false;
        var elargie = surface.matches('.bande-sable, .bande-encre');
        surface.addEventListener('pointermove', function (e) {
            if (!souris.matches || calme.matches) return;
            dernier = e;
            if (prevu) return;
            prevu = true;
            requestAnimationFrame(function () {
                prevu = false;
                var r = surface.getBoundingClientRect();
                var gauche = elargie ? r.left + r.width / 2 - window.innerWidth / 2 : r.left;
                surface.style.setProperty('--mx', Math.round(dernier.clientX - gauche) + 'px');
                surface.style.setProperty('--my', Math.round(dernier.clientY - r.top) + 'px');
                surface.classList.add('motif-actif');
            });
        });
        surface.addEventListener('pointerleave', function () { surface.classList.remove('motif-actif'); });
    });

    // Cartes des domaines : le bouton retourne la carte (clavier, toucher) ; toucher la carte aussi.
    document.querySelectorAll('[data-carte]').forEach(function (carte) {
        var declencheur = carte.querySelector('[data-retourner]');
        function retourner() {
            var retournee = carte.classList.toggle('retournee');
            declencheur.setAttribute('aria-pressed', retournee ? 'true' : 'false');
        }
        declencheur.addEventListener('click', function (e) { e.stopPropagation(); retourner(); });
        carte.addEventListener('click', function () { if (!souris.matches) retourner(); });
    });

    // Visionneuse : chaque photo s'ouvre en grand dans une boîte de dialogue native.
    var visionneuse = document.querySelector('[data-visionneuse]');
    if (visionneuse && typeof visionneuse.showModal === 'function') {
        var image = visionneuse.querySelector('[data-visionneuse-image]');
        var legende = visionneuse.querySelector('[data-visionneuse-legende]');
        document.querySelectorAll('[data-agrandir]').forEach(function (photo) {
            photo.addEventListener('click', function () {
                image.src = photo.getAttribute('data-agrandir');
                image.alt = photo.querySelector('img').alt;
                legende.textContent = photo.getAttribute('data-legende');
                visionneuse.showModal();
            });
        });
        visionneuse.querySelector('[data-visionneuse-fermer]').addEventListener('click', function () { visionneuse.close(); });
        // Au toucher, la photo elle-même referme la visionneuse : pas besoin de viser le coin.
        image.addEventListener('click', function () { if (!souris.matches) visionneuse.close(); });
        visionneuse.addEventListener('click', function (e) { if (e.target === visionneuse) visionneuse.close(); });
        visionneuse.addEventListener('close', function () { image.src = ''; });
    }

    // Apparitions au défilement. Liste des parties concernées : c'est ici qu'on en ajoute ou retire.
    // Déclenchement quand l'élément a dépassé 18 % de la hauteur de l'écran depuis le bas, pour que
    // le mouvement se voie ; les éléments qui entrent ensemble partent l'un après l'autre.
    var cibles = [
        '.bloc-titre', '.reperes-phrase', '.accroche', '.capital', '.carte', '.etapes-courtes li',
        '.bloc-ecole .bloc-corps > div', '.tableau-cours li', '.photos-trio figure', '.bloc-photo',
        '.bloc-photo-aside', '.registre-domaine', '.etapes li', '.frise li', '.calendrier',
        '.tableau', '.carte-contact', '.bloc-coordonnees figure', '.formulaire', '.bande-appel-interieur',
        '.site-pied-devise span'
    ].join(', ');
    if (!calme.matches && 'IntersectionObserver' in window) {
        var aReveler = document.querySelectorAll(cibles);
        var limite = window.innerHeight * 0.82;
        aReveler.forEach(function (el) {
            el.setAttribute('data-reveler', '');
            if (el.getBoundingClientRect().top < limite) el.classList.add('deja-vu');
        });
        html.classList.add('reveler-actif');
        var montrer = function (liste) {
            liste.sort(function (a, b) { return a.getBoundingClientRect().top - b.getBoundingClientRect().top || a.getBoundingClientRect().left - b.getBoundingClientRect().left; });
            liste.forEach(function (el, i) { el.style.setProperty('--i', Math.min(i, 6)); el.classList.add('revele'); observateur.unobserve(el); });
        };
        var observateur = new IntersectionObserver(function (entrees) {
            montrer(entrees.filter(function (e) { return e.isIntersecting; }).map(function (e) { return e.target; }));
        }, { rootMargin: '0px 0px -18% 0px' });
        aReveler.forEach(function (el) { if (!el.classList.contains('deja-vu')) observateur.observe(el); });
        // En bas de page, plus rien ne peut monter jusqu'au seuil : on montre ce qui reste.
        window.addEventListener('scroll', function () {
            if (window.innerHeight + window.scrollY < document.documentElement.scrollHeight - 4) return;
            montrer(Array.prototype.filter.call(aReveler, function (el) { return !el.classList.contains('deja-vu') && !el.classList.contains('revele'); }));
        }, { passive: true });
    }

    // Formulaire de contact : compteur de caractères, et après un envoi refusé, le curseur va au
    // premier champ à corriger.
    document.querySelectorAll('[data-compteur]').forEach(function (zone) {
        var valeur = zone.closest('.champ').querySelector('[data-compteur-valeur]');
        zone.addEventListener('input', function () { valeur.textContent = zone.value.length; });
    });
    var aCorriger = document.querySelector('[data-formulaire] [aria-invalid="true"]');
    if (aCorriger) aCorriger.focus({ preventScroll: true });
})();
