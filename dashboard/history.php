<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!$auth->isLoggedIn() || $auth->isAdmin()) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$filter = $_GET['filter'] ?? 'all';

$sql = "SELECT * FROM autorisations WHERE user_id = ?";
$params = [$user_id];

if ($filter === 'pending') {
    $sql .= " AND status = 'pending'";
} elseif ($filter === 'approved') {
    $sql .= " AND status = 'approved'";
} elseif ($filter === 'rejected') {
    $sql .= " AND status = 'rejected'";
} elseif ($filter === 'completed') {
    $sql .= " AND status = 'approved' AND pointage = 1";
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$autorisations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique - CMC Tamsna</title>
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
        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }
        .slide-up {
            animation: slideUp 0.6s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
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
                    <a href="index.php" class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center hover:scale-110 transition">
                        <i class="fas fa-arrow-left text-blue-600 text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-white font-bold text-xl">Historique des Demandes</h1>
                        <p class="text-blue-200 text-sm">Vos autorisations de sortie</p>
                    </div>
                </div>
                
                <nav class="flex space-x-4">
                    <a href="index.php" class="text-white hover:bg-white/20 px-4 py-2 rounded-xl transition">Accueil</a>
                    <a href="request.php" class="text-white hover:bg-white/20 px-4 py-2 rounded-xl transition">Nouvelle Demande</a>
                    <a href="history.php" class="text-white bg-white/20 px-4 py-2 rounded-xl">Historique</a>
                    <a href="../auth/logout.php" class="text-white hover:bg-red-500/20 px-4 py-2 rounded-xl transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-6 py-8">
        <!-- Filtres -->
        <div class="glass-effect rounded-2xl p-6 mb-6 fade-in">
            <h2 class="text-white font-bold text-xl mb-4">Filtrer par statut</h2>
            <div class="flex space-x-4 flex-wrap gap-2">
                <a href="?filter=all" class="<?php echo $filter === 'all' ? 'bg-white text-blue-600' : 'bg-white/10 text-white'; ?> px-6 py-3 rounded-xl hover:bg-white/20 transition font-semibold">
                    Toutes (<?php echo count($autorisations); ?>)
                </a>
                <a href="?filter=pending" class="<?php echo $filter === 'pending' ? 'bg-yellow-500 text-white' : 'bg-white/10 text-white'; ?> px-6 py-3 rounded-xl hover:bg-white/20 transition font-semibold">
                    <i class="fas fa-clock mr-2"></i>En attente
                </a>
                <a href="?filter=approved" class="<?php echo $filter === 'approved' ? 'bg-green-500 text-white' : 'bg-white/10 text-white'; ?> px-6 py-3 rounded-xl hover:bg-white/20 transition font-semibold">
                    <i class="fas fa-check mr-2"></i>Approuvées
                </a>
                <a href="?filter=rejected" class="<?php echo $filter === 'rejected' ? 'bg-red-500 text-white' : 'bg-white/10 text-white'; ?> px-6 py-3 rounded-xl hover:bg-white/20 transition font-semibold">
                    <i class="fas fa-times mr-2"></i>Rejetées
                </a>
                <a href="?filter=completed" class="<?php echo $filter === 'completed' ? 'bg-blue-500 text-white' : 'bg-white/10 text-white'; ?> px-6 py-3 rounded-xl hover:bg-white/20 transition font-semibold">
                    <i class="fas fa-check-double mr-2"></i>Terminées
                </a>
            </div>
        </div>

        <!-- Liste des autorisations -->
        <div class="glass-effect rounded-2xl p-6">
            <?php if (empty($autorisations)): ?>
                <div class="text-center py-12 slide-up">
                    <div class="w-24 h-24 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-inbox text-white text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Aucune demande trouvée</h3>
                    <p class="text-blue-200 text-lg mb-6">
                        <?php echo $filter === 'all' ? 'Vous n\'avez encore fait aucune demande d\'autorisation.' : 'Aucune demande ne correspond à ce filtre.'; ?>
                    </p>
                    <?php if ($filter === 'all'): ?>
                        <a href="request.php" class="bg-white text-blue-600 px-8 py-4 rounded-xl font-bold hover:bg-blue-50 transition inline-block">
                            <i class="fas fa-plus mr-2"></i>Faire une première demande
                        </a>
                    <?php else: ?>
                        <a href="?filter=all" class="bg-white/20 text-white px-6 py-3 rounded-xl hover:bg-white/30 transition">
                            Voir toutes les demandes
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($autorisations as $index => $auth): ?>
                        <div class="bg-white/10 rounded-2xl p-6 border border-white/20 hover:bg-white/20 transition slide-up" 
                             style="animation-delay: <?php echo $index * 0.1; ?>s">
                            
                            <!-- En-tête -->
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-white font-bold text-xl mb-2">
                                        Autorisation #<?php echo $auth['id']; ?>
                                    </h3>
                                    <div class="flex items-center space-x-4 text-sm">
                                        <span class="text-blue-200">
                                            <i class="fas fa-calendar-day mr-1"></i>
                                            Départ: <?php echo date('d/m/Y', strtotime($auth['date_depart'])); ?>
                                        </span>
                                        <span class="text-blue-200">
                                            <i class="fas fa-calendar-check mr-1"></i>
                                            Retour: <?php echo date('d/m/Y', strtotime($auth['date_retour'])); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="text-right">
                                    <span class="<?php 
                                        echo match($auth['status']) {
                                            'approved' => $auth['pointage'] ? 'bg-blue-500/30 text-blue-300' : 'bg-green-500/30 text-green-300',
                                            'pending' => 'bg-yellow-500/30 text-yellow-300',
                                            'rejected' => 'bg-red-500/30 text-red-300',
                                            default => 'bg-gray-500/30 text-gray-300'
                                        };
                                    ?> px-4 py-2 rounded-full font-semibold">
                                        <?php 
                                            if ($auth['status'] === 'approved' && $auth['pointage']) {
                                                echo 'Terminée';
                                            } else {
                                                echo ucfirst($auth['status']);
                                            }
                                        ?>
                                    </span>
                                    <p class="text-blue-200 text-sm mt-2">
                                        <?php echo date('d/m/Y à H:i', strtotime($auth['created_at'])); ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Détails -->
                            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                                <div class="bg-white/5 rounded-xl p-4">
                                    <p class="text-blue-200 text-sm mb-1">Chambre</p>
                                    <p class="text-white font-semibold"><?php echo $auth['chambre']; ?></p>
                                </div>
                                
                                <div class="bg-white/5 rounded-xl p-4">
                                    <p class="text-blue-200 text-sm mb-1">Votre téléphone</p>
                                    <p class="text-white font-semibold"><?php echo $auth['telephone']; ?></p>
                                </div>
                                
                                <div class="bg-white/5 rounded-xl p-4">
                                    <p class="text-blue-200 text-sm mb-1">Tél. parent</p>
                                    <p class="text-white font-semibold"><?php echo $auth['parent_telephone']; ?></p>
                                </div>
                                
                                <div class="bg-white/5 rounded-xl p-4">
                                    <p class="text-blue-200 text-sm mb-1">Durée</p>
                                    <p class="text-white font-semibold">
                                        <?php 
                                            $start = new DateTime($auth['date_depart']);
                                            $end = new DateTime($auth['date_retour']);
                                            $diff = $start->diff($end);
                                            echo $diff->days . ' jour(s)';
                                        ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Statut détaillé -->
                            <div class="bg-white/5 rounded-xl p-4 border-l-4 <?php 
                                echo match($auth['status']) {
                                    'approved' => $auth['pointage'] ? 'border-blue-500' : 'border-green-500',
                                    'pending' => 'border-yellow-500',
                                    'rejected' => 'border-red-500',
                                    default => 'border-gray-500'
                                };
                            ?>">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-white font-semibold">
                                            <?php 
                                                if ($auth['status'] === 'pending') {
                                                    echo 'En attente d\'approbation par l\'administration';
                                                } elseif ($auth['status'] === 'approved' && !$auth['pointage']) {
                                                    if (date('Y-m-d') > $auth['date_retour']) {
                                                        echo '⚠️ Retour en retard - Veuillez pointer à l\'administration';
                                                    } else {
                                                        echo '✅ Autorisation approuvée - Sortie en cours';
                                                    }
                                                } elseif ($auth['status'] === 'approved' && $auth['pointage']) {
                                                    echo '✅ Sortie terminée - Pointage effectué';
                                                } elseif ($auth['status'] === 'rejected') {
                                                    echo '❌ Autorisation refusée';
                                                }
                                            ?>
                                        </p>
                                        <?php if ($auth['status'] === 'approved' && !$auth['pointage'] && date('Y-m-d') > $auth['date_retour']): ?>
                                            <p class="text-yellow-300 text-sm mt-1">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                Vous devez pointer à l'administration dès votre retour
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($auth['status'] === 'approved' && !$auth['pointage']): ?>
                                        <div class="text-right">
                                            <p class="text-white text-sm">Heure limite de retour</p>
                                            <p class="text-white font-bold">19h00</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Animation au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.slide-up');
            elements.forEach((el, index) => {
                el.style.animationDelay = (index * 0.1) + 's';
            });
        });
    </script>
</body>
</html>