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

    // Photos : inclinaison vers le pointeur et reflet qui le suit. Pas pour les photos pleine
    // largeur (accueil, bandeau), dont les bords sortent de l'écran.
    document.querySelectorAll('.photo').forEach(function (photo) {
        if (photo.closest('.accueil-hero-photo, .photo-debord')) return;
        photo.addEventListener('pointermove', function (e) {
            if (!souris.matches || !bureau.matches || calme.matches) return;
            var r = photo.getBoundingClientRect();
            var x = (e.clientX - r.left) / r.width;
            var y = (e.clientY - r.top) / r.height;
            photo.classList.add('incline');
            photo.style.setProperty('--ry', ((x - 0.5) * 10).toFixed(2) + 'deg');
            photo.style.setProperty('--rx', ((0.5 - y) * 8).toFixed(2) + 'deg');
            photo.style.setProperty('--mx', (x * 100).toFixed(1) + '%');
            photo.style.setProperty('--my', (y * 100).toFixed(1) + '%');
        });
        photo.addEventListener('pointerleave', function () {
            photo.classList.remove('incline');
            ['--rx', '--ry', '--mx', '--my'].forEach(function (p) { photo.style.removeProperty(p); });
        });
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

    // Formulaire de contact : compteur de caractères, et après un envoi refusé, le curseur va au
    // premier champ à corriger.
    document.querySelectorAll('[data-compteur]').forEach(function (zone) {
        var valeur = zone.closest('.champ').querySelector('[data-compteur-valeur]');
        zone.addEventListener('input', function () { valeur.textContent = zone.value.length; });
    });
    var aCorriger = document.querySelector('[data-formulaire] [aria-invalid="true"]');
    if (aCorriger) aCorriger.focus({ preventScroll: true });
})();
