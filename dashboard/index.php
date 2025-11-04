<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!$auth->isLoggedIn() || $auth->isAdmin()) {
    header('Location: ../auth/login.php');
    exit;
}

// Récupérer les autorisations de l'utilisateur
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM autorisations WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$autorisations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
    FROM autorisations WHERE user_id = ?";
$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute([$user_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - CMC Tamsna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .slide-in {
            animation: slideIn 0.6s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body class="gradient-bg min-h-screen">
    <!-- Header -->
    <header class="glass-effect">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center">
                        <i class="fas fa-graduation-cap text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-white font-bold text-xl">Tableau de Bord</h1>
                        <p class="text-blue-200 text-sm">Bienvenue, <?php echo $_SESSION['user_prenom']; ?></p>
                    </div>
                </div>
                
                <nav class="flex space-x-4">
                    <a href="index.php" class="text-white bg-white/20 px-4 py-2 rounded-xl">Accueil</a>
                    <a href="request.php" class="text-white hover:bg-white/20 px-4 py-2 rounded-xl transition">Nouvelle Demande</a>
                    <a href="history.php" class="text-white hover:bg-white/20 px-4 py-2 rounded-xl transition">Historique</a>
                    <a href="../auth/logout.php" class="text-white hover:bg-red-500/20 px-4 py-2 rounded-xl transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-6 py-8">
        <!-- Statistiques -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <div class="glass-effect rounded-2xl p-6 text-white slide-in">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-200">Total des demandes</p>
                        <h3 class="text-3xl font-bold"><?php echo $stats['total']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-blue-500/30 rounded-xl flex items-center justify-center">
                        <i class="fas fa-file-alt text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="glass-effect rounded-2xl p-6 text-white slide-in" style="animation-delay: 0.2s;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-200">Approuvées</p>
                        <h3 class="text-3xl font-bold"><?php echo $stats['approved']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-green-500/30 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="glass-effect rounded-2xl p-6 text-white slide-in" style="animation-delay: 0.4s;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-200">En attente</p>
                        <h3 class="text-3xl font-bold"><?php echo $stats['pending']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-yellow-500/30 rounded-xl flex items-center justify-center">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="glass-effect rounded-2xl p-6 mb-8">
            <h2 class="text-2xl font-bold text-white mb-6">Actions Rapides</h2>
            <div class="grid md:grid-cols-2 gap-4">
                <a href="request.php" class="bg-white text-blue-600 p-6 rounded-xl hover:bg-blue-50 transform hover:scale-105 transition duration-300 group">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition">
                            <i class="fas fa-plus text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg">Nouvelle Autorisation</h3>
                            <p class="text-gray-600">Demander une autorisation de sortie</p>
                        </div>
                    </div>
                </a>
                
                <a href="history.php" class="bg-white/10 text-white p-6 rounded-xl hover:bg-white/20 transform hover:scale-105 transition duration-300 group border border-white/20">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-green-500/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition">
                            <i class="fas fa-history text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg">Historique</h3>
                            <p class="text-blue-200">Voir toutes vos demandes</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        

        <!-- Dernières demandes -->
        <div class="glass-effect rounded-2xl p-6">
            <h2 class="text-2xl font-bold text-white mb-6">Dernières Demandes</h2>
            
            <?php if (empty($autorisations)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-inbox text-4xl text-white/50 mb-4"></i>
                    <p class="text-white/70 text-lg">Aucune demande d'autorisation pour le moment</p>
                    <a href="request.php" class="inline-block mt-4 bg-white text-blue-600 px-6 py-3 rounded-xl font-semibold hover:bg-blue-50 transition">
                        Faire une première demande
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach (array_slice($autorisations, 0, 5) as $autorisation): ?>
                        <div class="bg-white/10 rounded-xl p-4 border border-white/20 hover:bg-white/20 transition group">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="text-white font-semibold">
                                        Du <?php echo date('d/m/Y', strtotime($autorisation['date_depart'])); ?> 
                                        au <?php echo date('d/m/Y', strtotime($autorisation['date_retour'])); ?>
                                    </h3>
                                    <p class="text-blue-200 text-sm">Chambre: <?php echo $autorisation['chambre']; ?></p>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <span class="<?php 
                                        echo match($autorisation['status']) {
                                            'approved' => 'bg-green-500/30 text-green-300',
                                            'pending' => 'bg-yellow-500/30 text-yellow-300',
                                            'rejected' => 'bg-red-500/30 text-red-300',
                                            default => 'bg-gray-500/30 text-gray-300'
                                        };
                                    ?> px-3 py-1 rounded-full text-sm font-medium">
                                        <?php echo ucfirst($autorisation['status']); ?>
                                    </span>
                                    <i class="fas fa-chevron-right text-white/50 group-hover:text-white transition"></i>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($autorisations) > 5): ?>
                        <div class="text-center mt-6">
                            <a href="history.php" class="text-white hover:text-blue-300 font-semibold transition">
                                Voir toutes les demandes <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Animation au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.slide-in');
            elements.forEach((el, index) => {
                el.style.animationDelay = (index * 0.1) + 's';
            });
        });
    </script>
</body>
</html>