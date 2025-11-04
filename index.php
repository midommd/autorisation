<?php
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMC Tamsna - Système d'Autorisation Internat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite alternate;
        }
        .slide-in {
            animation: slideIn 0.8s ease-out;
        }
        .bounce-in {
            animation: bounceIn 1s ease-out;
        }
        @keyframes floating {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(2deg); }
        }
        @keyframes pulse-glow {
            0% { box-shadow: 0 0 20px rgba(102, 126, 234, 0.4); }
            100% { box-shadow: 0 0 40px rgba(102, 126, 234, 0.8); }
        }
        @keyframes slideIn {
            from { transform: translateX(-100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }
        .feature-card:hover {
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }
        .stats-counter {
            font-feature-settings: "tnum";
            font-variant-numeric: tabular-nums;
        }
    </style>
</head>
<body class="bg-gray-900">
    <!-- Navigation -->
    <nav class="fixed w-full z-50 glass-effect">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-graduation-cap text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-white font-bold text-xl">CMC Tamsna</h1>
                        <p class="text-blue-200 text-xs">Excellence Académique</p>
                    </div>
                </div>
                
                <div class="hidden lg:flex space-x-8">
                    <a href="#accueil" class="text-white hover:text-blue-300 transition-all duration-300 font-medium">Accueil</a>
                    <a href="#features" class="text-white hover:text-blue-300 transition-all duration-300 font-medium">Fonctionnalités</a>
                    <a href="#about" class="text-white hover:text-blue-300 transition-all duration-300 font-medium">À Propos</a>
                    <a href="#stats" class="text-white hover:text-blue-300 transition-all duration-300 font-medium">Statistiques</a>
                    <a href="#contact" class="text-white hover:text-blue-300 transition-all duration-300 font-medium">Contact</a>
                </div>

                <div class="flex space-x-4">
                    <a href="auth/login.php" class="bg-white text-blue-600 px-6 py-2 rounded-xl hover:bg-blue-50 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl">
                        <i class="fas fa-sign-in-alt mr-2"></i>Connexion
                    </a>
                    <a href="auth/register.php" class="glass-effect text-white px-6 py-2 rounded-xl hover:bg-white hover:text-blue-600 transition-all duration-300 font-semibold border border-white/20">
                        <i class="fas fa-user-plus mr-2"></i>Inscription
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="accueil" class="gradient-bg min-h-screen flex items-center pt-20">
        <div class="container mx-auto px-6">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="slide-in">
                    <div class="bg-white/10 rounded-3xl p-8 backdrop-blur-lg border border-white/20 mb-6">
                        <h1 class="text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight">
                            Système d'Autorisation
                            <span class="text-blue-300">Intelligent</span>
                        </h1>
                        <p class="text-xl text-blue-100 mb-8 leading-relaxed">
                            Gestion moderne et sécurisée des autorisations de sortie pour l'internat CMC Tamsna. 
                            Une solution complète pour optimiser le processus administratif.
                        </p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-6">
                        <a href="auth/register.php" class="bg-white text-blue-600 px-8 py-4 rounded-2xl hover:bg-blue-50 transition-all duration-300 font-bold text-lg text-center shadow-2xl pulse-glow">
                            <i class="fas fa-rocket mr-3"></i>Commencer Maintenant
                        </a>
                        <a href="#features" class="glass-effect text-white px-8 py-4 rounded-2xl hover:bg-white hover:text-blue-600 transition-all duration-300 font-semibold text-lg text-center border border-white/30">
                            <i class="fas fa-play-circle mr-3"></i>Voir la Démo
                        </a>
                    </div>
                </div>
                
                <div class="relative">
                    <div class="floating">
                        <div class="bg-white/10 rounded-3xl p-8 backdrop-blur-lg border border-white/20">
                            <div class="grid grid-cols-2 gap-6">
                                <div class="bg-white/20 rounded-2xl p-6 text-center transform rotate-3 hover:rotate-0 transition-transform duration-300">
                                    <i class="fas fa-shield-alt text-3xl text-white mb-3"></i>
                                    <h3 class="text-white font-semibold">Sécurisé</h3>
                                </div>
                                <div class="bg-white/20 rounded-2xl p-6 text-center transform -rotate-3 hover:rotate-0 transition-transform duration-300">
                                    <i class="fas fa-bolt text-3xl text-yellow-300 mb-3"></i>
                                    <h3 class="text-white font-semibold">Rapide</h3>
                                </div>
                                <div class="bg-white/20 rounded-2xl p-6 text-center transform -rotate-2 hover:rotate-0 transition-transform duration-300">
                                    <i class="fas fa-mobile-alt text-3xl text-green-300 mb-3"></i>
                                    <h3 class="text-white font-semibold">Mobile</h3>
                                </div>
                                <div class="bg-white/20 rounded-2xl p-6 text-center transform rotate-2 hover:rotate-0 transition-transform duration-300">
                                    <i class="fas fa-chart-line text-3xl text-purple-300 mb-3"></i>
                                    <h3 class="text-white font-semibold">Analytique</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-800">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-white mb-4">Fonctionnalités Avancées</h2>
                <p class="text-gray-300 text-xl">Découvrez les capacités exceptionnelles de notre système</p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="feature-card bg-gray-700 rounded-3xl p-8 hover:bg-gray-600 transition-all duration-300 group">
                    <div class="w-16 h-16 bg-blue-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-fingerprint text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white text-xl font-semibold mb-4">Authentification Sécurisée</h3>
                    <p class="text-gray-300">Système d'authentification multi-couches avec validation administrative.</p>
                </div>
                
                <div class="feature-card bg-gray-700 rounded-3xl p-8 hover:bg-gray-600 transition-all duration-300 group">
                    <div class="w-16 h-16 bg-green-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-calendar-check text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white text-xl font-semibold mb-4">Gestion Intelligente</h3>
                    <p class="text-gray-300">Planification automatique des sessions et gestion des délais.</p>
                </div>
                
                <div class="feature-card bg-gray-700 rounded-3xl p-8 hover:bg-gray-600 transition-all duration-300 group">
                    <div class="w-16 h-16 bg-purple-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-bell text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white text-xl font-semibold mb-4">Notifications Temps Réel</h3>
                    <p class="text-gray-300">Alertes instantanées pour les autorisations et retours.</p>
                </div>
                
                <div class="feature-card bg-gray-700 rounded-3xl p-8 hover:bg-gray-600 transition-all duration-300 group">
                    <div class="w-16 h-16 bg-red-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-chart-bar text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white text-xl font-semibold mb-4">Tableaux de Bord</h3>
                    <p class="text-gray-300">Analyses détaillées et rapports pour l'administration.</p>
                </div>
                
                <div class="feature-card bg-gray-700 rounded-3xl p-8 hover:bg-gray-600 transition-all duration-300 group">
                    <div class="w-16 h-16 bg-yellow-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-mobile text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white text-xl font-semibold mb-4">Design Responsive</h3>
                    <p class="text-gray-300">Interface adaptée à tous les appareils, du mobile au desktop.</p>
                </div>
                
                <div class="feature-card bg-gray-700 rounded-3xl p-8 hover:bg-gray-600 transition-all duration-300 group">
                    <div class="w-16 h-16 bg-indigo-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-clock text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white text-xl font-semibold mb-4">Contrôle Temporel</h3>
                    <p class="text-gray-300">Gestion précise des horaires et délais de retour.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section id="stats" class="py-20 gradient-bg">
        <div class="container mx-auto px-6">
            <div class="grid md:grid-cols-4 gap-8 text-center">
                <div class="glass-effect rounded-3xl p-8">
                    <div class="text-4xl font-bold text-white stats-counter" data-target="1500">0</div>
                    <div class="text-blue-200 font-semibold">Stagiaires</div>
                </div>
                <div class="glass-effect rounded-3xl p-8">
                    <div class="text-4xl font-bold text-white stats-counter" data-target="89">0</div>
                    <div class="text-blue-200 font-semibold">Autorisations/Semaine</div>
                </div>
                <div class="glass-effect rounded-3xl p-8">
                    <div class="text-4xl font-bold text-white stats-counter" data-target="24">0</div>
                    <div class="text-blue-200 font-semibold">Heures/7j</div>
                </div>
                <div class="glass-effect rounded-3xl p-8">
                    <div class="text-4xl font-bold text-white stats-counter" data-target="100">0</div>
                    <div class="text-blue-200 font-semibold">% Sécurisé</div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-20 bg-gray-900">
        <div class="container mx-auto px-6">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="bounce-in">
                    <h2 class="text-4xl font-bold text-white mb-6">À Propos du CMC Tamsna</h2>
                    <p class="text-gray-300 text-lg mb-6 leading-relaxed">
                        Le Centre Marocain de Certification de Tamsna s'engage à fournir une éducation d'excellence 
                        et un environnement d'apprentissage optimal pour ses stagiaires.
                    </p>
                    <div class="space-y-4">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-green-500 rounded-2xl flex items-center justify-center">
                                <i class="fas fa-award text-white"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-semibold">Certification Reconnue</h4>
                                <p class="text-gray-400">Programmes accrédités au niveau national</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-blue-500 rounded-2xl flex items-center justify-center">
                                <i class="fas fa-users text-white"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-semibold">Encadrement Expert</h4>
                                <p class="text-gray-400">Équipe pédagogique qualifiée</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-purple-500 rounded-2xl flex items-center justify-center">
                                <i class="fas fa-home text-white"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-semibold">Internat Moderne</h4>
                                <p class="text-gray-400">Infrastructures confortables et sécurisées</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="relative">
                    <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-3xl p-8 transform rotate-3 hover:rotate-0 transition-transform duration-500">
                        <div class="bg-gray-900 rounded-2xl p-6">
                            <h3 class="text-white text-2xl font-bold mb-4">Processus d'Autorisation</h3>
                            <div class="space-y-4">
                                <div class="flex items-center space-x-4">
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">1</div>
                                    <span class="text-gray-300">Inscription et validation</span>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white font-bold">2</div>
                                    <span class="text-gray-300">Demande d'autorisation</span>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center text-white font-bold">3</div>
                                    <span class="text-gray-300">Approbation administrative</span>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold">4</div>
                                    <span class="text-gray-300">Suivi en temps réel</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script>
        // Stats Counter Animation
        const counters = document.querySelectorAll('.stats-counter');
        counters.forEach(counter => {
            const updateCount = () => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText;
                const increment = target / 200;

                if (count < target) {
                    counter.innerText = Math.ceil(count + increment);
                    setTimeout(updateCount, 10);
                } else {
                    counter.innerText = target;
                }
            };
            updateCount();
        });
    </script>
</body>
</html>