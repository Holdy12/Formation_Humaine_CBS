console.log("TEST CHARGEMENT FICHIER JS : OK");

document.addEventListener('DOMContentLoaded', () => {

    // ==========================================
    // 1. MENU ACCORDÉON DES POINTS
    // ==========================================
    window.toggleLinksMenu = function() {
        const menu = document.getElementById('collapsibleMenu');
        if (menu) {
            menu.classList.toggle('open');
        }
    };


    // ==========================================
    // 2. FILTRAGE EN TEMPS RÉEL DU TABLEAU
    // ==========================================
    window.filterActivities = function() {
        const input = document.getElementById('searchActivity');
        if (!input) return;
        
        const filter = input.value.toLowerCase();
        const table = document.getElementById('activityTable');
        if (!table) return;
        
        const tr = table.getElementsByTagName('tr');

        for (let i = 1; i < tr.length; i++) {
            let rowText = tr[i].textContent || tr[i].innerText;
            if (rowText.toLowerCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    };


    // ==========================================
    // 3. GESTION DU MODE SOMBRE / CLAIR
    // ==========================================
    function updateThemeButton(isDark) {
        const btn = document.getElementById('themeToggleBtn');
        if (btn) {
            btn.innerHTML = isDark ? '☀️ Mode Clair' : '🌙 Mode Sombre';
        }
    }

    window.toggleTheme = function() {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        updateThemeButton(isDark);
    };

    // Chargement de la préférence de thème au démarrage
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
        updateThemeButton(true);
    }


    // ==========================================
    // 4. INITIALISATION DES GRAPHIQUES (Chart.js)
    // ==========================================

    // --- Graphique Doughnut (Répartition) ---
    const ctxRepartition = document.getElementById('repartitionChart');
    if (ctxRepartition) {
        const pointsPositifs = typeof window.phpPositifs !== 'undefined' ? window.phpPositifs : 2;
        const pointsNegatifs = typeof window.phpNegatifs !== 'undefined' ? window.phpNegatifs : 1;

        new Chart(ctxRepartition.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Points Positifs', 'Points Négatifs'],
                datasets: [{
                    data: [pointsPositifs, pointsNegatifs],
                    backgroundColor: ['#10b981', '#ef4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { size: 12 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.raw || 0;
                                return ` ${label} : ${value} unités`;
                            }
                        }
                    }
                }
            }
        });
    }


    // --- Graphique en Ligne (Évolution Dynamique par Domaine) ---
    const ctxEvolution = document.getElementById('evolutionChart');
    if (ctxEvolution) {
        // Récupération sécurisée des variables globales injectées par PHP
        const labelsData = (typeof window.evolutionLabels !== 'undefined' && window.evolutionLabels.length > 0) 
            ? window.evolutionLabels 
            : ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
            
        const datasetsData = (typeof window.evolutionDatasets !== 'undefined') 
            ? window.evolutionDatasets 
            : [];

        // Debug console pour vérifier ce que le JS reçoit de PHP
        console.log("=== DEBUG EVOLUTION CHART ===");
        console.log("Labels reçus:", labelsData);
        console.log("Datasets reçus:", datasetsData);

        new Chart(ctxEvolution.getContext('2d'), {
            type: 'line',
            data: {
                labels: labelsData,
                datasets: datasetsData
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        grid: {
                            color: 'rgba(200, 200, 200, 0.15)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 15
                        }
                    }
                }
            }
        });
    }

});