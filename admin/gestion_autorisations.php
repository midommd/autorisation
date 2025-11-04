<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../auth/login.php');
    exit;
}

// Récupérer les statistiques rapides
$stats_sql = "SELECT 
    COUNT(*) as total_autorisations,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as en_attente,
    SUM(CASE WHEN status = 'approved' AND pointage = 0 AND date_retour < CURDATE() THEN 1 ELSE 0 END) as retards,
    (SELECT COUNT(*) FROM emergency_cases WHERE statut = 'en_cours') as urgences_actives
    FROM autorisations";
$stats = $pdo->query($stats_sql)->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Autorisations - CMC Tamsna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg { background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%); }
        .glass-effect { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .pulse-warning { animation: pulse 2s infinite; }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
            50% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
        }
    </style>
</head>
<body class="gradient-bg min-h-screen">
    <header class="glass-effect border-b border-white/10">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="w-12 h-12 bg-red-500/20 rounded-2xl flex items-center justify-center hover:scale-110 transition">
                        <i class="fas fa-arrow-left text-red-400 text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-white font-bold text-xl">Gestion des Autorisations</h1>
                        <p class="text-gray-400 text-sm">Centre de gestion complet des sorties</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-6 py-8">
        <!-- Alertes urgentes -->
        <?php if ($stats['retards'] > 0 || $stats['urgences_actives'] > 0): ?>
        <div class="glass-effect rounded-2xl p-6 mb-6 border-l-4 border-red-500 pulse-warning">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-bold text-lg">Alertes Actives</h3>
                        <p class="text-gray-400">
                            <?php 
                                $alertes = [];
                                if ($stats['retards'] > 0) $alertes[] = $stats['retards'] . " retard(s)";
                                if ($stats['urgences_actives'] > 0) $alertes[] = $stats['urgences_actives'] . " urgence(s) médicale(s)";
                                echo implode(' • ', $alertes);
                            ?>
                        </p>
                    </div>
                </div>
                <div class="text-red-400 font-semibold">
                    <i class="fas fa-clock mr-2"></i>Action Requise
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Cartes de navigation -->
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Autorisations Normales -->
            <a href="autorisations_normales.php" class="glass-effect rounded-2xl p-6 card-hover border-l-4 border-blue-500">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-file-alt text-blue-400 text-xl"></i>
                    </div>
                    <span class="text-blue-400 text-sm font-semibold">Gérer</span>
                </div>
                <h3 class="text-white font-bold text-lg mb-2">Autorisations Normales</h3>
                <p class="text-gray-400 text-sm">Gestion des sorties programmées par les stagiaires</p>
                <div class="mt-4 flex justify-between items-center">
                    <span class="text-white font-bold"><?php echo $stats['total_autorisations']; ?> total</span>
                    <?php if ($stats['en_attente'] > 0): ?>
                        <span class="bg-yellow-500 text-white px-2 py-1 rounded-full text-xs"><?php echo $stats['en_attente']; ?> en attente</span>
                    <?php endif; ?>
                </div>
            </a>

            <!-- Autorisations d'Urgence -->
            <a href="autorisations_urgence.php" class="glass-effect rounded-2xl p-6 card-hover border-l-4 border-red-500">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-bolt text-red-400 text-xl"></i>
                    </div>
                    <span class="text-red-400 text-sm font-semibold">Créer</span>
                </div>
                <h3 class="text-white font-bold text-lg mb-2">Autorisations d'Urgence</h3>
                <p class="text-gray-400 text-sm">Création d'autorisations exceptionnelles</p>
                <div class="mt-4">
                    <span class="text-white text-sm">Situations exceptionnelles validées</span>
                </div>
            </a>

            <!-- Cas Médicaux -->
            <a href="cas_medicaux.php" class="glass-effect rounded-2xl p-6 card-hover border-l-4 border-orange-500">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-orange-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-heartbeat text-orange-400 text-xl"></i>
                    </div>
                    <span class="text-orange-400 text-sm font-semibold">Suivre</span>
                </div>
                <h3 class="text-white font-bold text-lg mb-2">Cas Médicaux</h3>
                <p class="text-gray-400 text-sm">Gestion des urgences médicales et accidents</p>
                <div class="mt-4 flex justify-between items-center">
                    <span class="text-white text-sm">Urgences actives</span>
                    <?php if ($stats['urgences_actives'] > 0): ?>
                        <span class="bg-red-500 text-white px-2 py-1 rounded-full text-xs"><?php echo $stats['urgences_actives']; ?></span>
                    <?php else: ?>
                        <span class="text-gray-400 text-xs">Aucune</span>
                    <?php endif; ?>
                </div>
            </a>

            <!-- Statistiques Médicales -->
            <a href="statistiques_medicales.php" class="glass-effect rounded-2xl p-6 card-hover border-l-4 border-purple-500">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-bar text-purple-400 text-xl"></i>
                    </div>
                    <span class="text-purple-400 text-sm font-semibold">Analyser</span>
                </div>
                <h3 class="text-white font-bold text-lg mb-2">Statistiques Médicales</h3>
                <p class="text-gray-400 text-sm">Suivi des consultations et visites médicales</p>
                <div class="mt-4">
                    <span class="text-white text-sm">Historique complet</span>
                </div>
            </a>
        </div>

        <!-- Actions rapides -->
        <div class="glass-effect rounded-2xl p-6">
            <h2 class="text-xl font-bold text-white mb-6">Actions Rapides</h2>
            <div class="grid md:grid-cols-3 gap-4">
                <a href="autorisations_normales.php?filter=pending" 
                   class="bg-yellow-500/20 text-yellow-300 p-4 rounded-xl hover:bg-yellow-500/30 transition group border border-yellow-500/30">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-yellow-500/30 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold">Vérifier les demandes</h3>
                            <p class="text-yellow-200/70 text-sm"><?php echo $stats['en_attente']; ?> en attente</p>
                        </div>
                    </div>
                </a>

                <a href="autorisations_urgence.php" 
                   class="bg-red-500/20 text-red-300 p-4 rounded-xl hover:bg-red-500/30 transition group border border-red-500/30">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-red-500/30 rounded-lg flex items-center justify-center">
                            <i class="fas fa-plus text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold">Nouvelle urgence</h3>
                            <p class="text-red-200/70 text-sm">Autorisation exceptionnelle</p>
                        </div>
                    </div>
                </a>

                <a href="statistiques_medicales.php" 
                   class="bg-blue-500/20 text-blue-300 p-4 rounded-xl hover:bg-blue-500/30 transition group border border-blue-500/30">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-500/30 rounded-lg flex items-center justify-center">
                            <i class="fas fa-search text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold">Recherche médicale</h3>
                            <p class="text-blue-200/70 text-sm">Consulter un stagiaire</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</body>
</html>