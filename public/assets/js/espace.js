// Comportements communs aux espaces étudiant et personnel : thème, tiroir de navigation, zones de fichier.
(function () {
    // Thème clair / sombre, mémorisé dans le navigateur
    function appliquerTheme(sombre) {
        document.body.classList.toggle('dark-mode', sombre);
        var bascule = document.getElementById('basculeTheme');
        if (bascule) bascule.setAttribute('aria-pressed', sombre ? 'true' : 'false');
    }
    try { appliquerTheme(localStorage.getItem('theme') === 'dark'); } catch (e) {}
    window.toggleTheme = function () {
        var sombre = !document.body.classList.contains('dark-mode');
        appliquerTheme(sombre);
        try { localStorage.setItem('theme', sombre ? 'dark' : 'light'); } catch (e) {}
    };
    var bascule = document.getElementById('basculeTheme');
    if (bascule) bascule.addEventListener('click', window.toggleTheme);

    // Tiroir de navigation sur petit écran
    var bouton = document.getElementById('boutonMenu');
    var voile = document.getElementById('voile');
    if (bouton && voile) {
        function fermer() {
            document.body.classList.remove('nav-ouverte');
            bouton.setAttribute('aria-expanded', 'false');
        }
        bouton.addEventListener('click', function () {
            var ouvert = document.body.classList.toggle('nav-ouverte');
            bouton.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
        });
        voile.addEventListener('click', fermer);
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') fermer(); });
    }

    // Zones de dépôt de fichier : liste des fichiers choisis, glisser-déposer
    var COCHE = '<svg class="icone" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>';
    function taille(octets) {
        if (octets < 1024) return octets + ' o';
        if (octets < 1048576) return Math.round(octets / 1024) + ' Ko';
        return (octets / 1048576).toFixed(1).replace('.', ',') + ' Mo';
    }
    document.querySelectorAll('.zone-fichier').forEach(function (zone) {
        var entree = zone.querySelector('.zone-fichier-entree');
        var liste = zone.querySelector('.zone-fichier-liste');
        function afficher() {
            liste.innerHTML = '';
            Array.prototype.forEach.call(entree.files, function (f) {
                var li = document.createElement('li');
                li.innerHTML = COCHE + '<span></span><small></small>';
                li.querySelector('span').textContent = f.name;
                li.querySelector('small').textContent = taille(f.size);
                liste.appendChild(li);
            });
        }
        entree.addEventListener('change', afficher);
        ['dragenter', 'dragover'].forEach(function (ev) {
            zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('survol'); });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.remove('survol'); });
        });
        zone.addEventListener('drop', function (e) {
            if (!e.dataTransfer || !e.dataTransfer.files.length) return;
            try { entree.files = e.dataTransfer.files; afficher(); } catch (err) { entree.click(); }
        });
    });

    // Sélecteurs avec recherche (liste filtrée par un champ texte)
    function normaliser(t) { return t.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); }
    document.querySelectorAll('[data-selecteur]').forEach(function (champ) {
        var liste = document.getElementById(champ.getAttribute('data-selecteur'));
        if (!liste) return;
        var options = Array.prototype.slice.call(liste.querySelectorAll('.selecteur-option'));
        var vide = liste.querySelector('.selecteur-vide');
        function filtrer() {
            var mots = normaliser(champ.value).split(/\s+/).filter(Boolean);
            var visibles = 0;
            options.forEach(function (o) {
                var texte = normaliser(o.dataset.texte || '');
                var choisi = o.querySelector('input').checked;
                var ok = choisi || mots.every(function (m) { return texte.indexOf(m) !== -1; });
                o.hidden = !ok;
                if (ok) visibles++;
            });
            if (vide) vide.hidden = visibles > 0;
        }
        champ.addEventListener('input', filtrer);
        liste.addEventListener('change', function () {
            options.forEach(function (o) { o.classList.toggle('choisi', o.querySelector('input').checked); });
        });
    });
})();
