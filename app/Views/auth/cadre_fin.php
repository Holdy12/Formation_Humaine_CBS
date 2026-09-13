            </div>
        </main>
    </div>
    <script src="assets/js/espace.js"></script>
    <script>
        document.querySelectorAll('[data-voir-mdp]').forEach(function (bouton) {
            var champ = document.getElementById(bouton.getAttribute('data-voir-mdp'));
            if (!champ) return;
            bouton.addEventListener('click', function () {
                var visible = champ.type === 'text';
                champ.type = visible ? 'password' : 'text';
                bouton.setAttribute('aria-pressed', visible ? 'false' : 'true');
                bouton.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
                bouton.querySelector('.icone-oeil').hidden = !visible;
                bouton.querySelector('.icone-oeil-barre').hidden = visible;
            });
        });
    </script>
</body>
</html>
