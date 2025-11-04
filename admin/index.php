<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../auth/login.php');
    exit;
}

// Statistiques pour l'admin 
$stats_sql = "
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN u.status = 'pending' THEN 1 ELSE 0 END) as pending_users,
        COUNT(DISTINCT a.id) as total_autorisations,
        SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending_autorisations,
        SUM(CASE WHEN a.status = 'approved' AND a.pointage = 0 AND a.date_retour < CURDATE() THEN 1 ELSE 0 END) as retards
    FROM users u 
    LEFT JOIN autorisations a ON u.id = a.user_id
    WHERE u.role = 'user'
";
$stats = $pdo->query($stats_sql)->fetch(PDO::FETCH_ASSOC);

// Utilisateurs en attente
$pending_users_sql = "SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5";
$pending_users = $pdo->query($pending_users_sql)->fetchAll(PDO::FETCH_ASSOC);

// Autorisations récentes
$recent_auth_sql = "SELECT a.*, u.nom, u.prenom FROM autorisations a 
                   JOIN users u ON a.user_id = u.id 
                   ORDER BY a.created_at DESC LIMIT 5";
$recent_autorisations = $pdo->query($recent_auth_sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - CMC Tamsna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .pulse-alert {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
</head>
<body class="gradient-bg min-h-screen">
    <!-- Header -->
    <header class="glass-effect border-b border-white/10">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-red-500 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-crown text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-white font-bold text-xl">Administration</h1>
                        <p class="text-gray-400 text-sm">Panel de gestion CMC Tamsna</p>
                    </div>
                </div>
                
                <nav class="flex space-x-2">
                    <a href="index.php" class="bg-red-500/20 text-white px-4 py-2 rounded-xl">Tableau de Bord</a>
                    <a href="users.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Utilisateurs</a>
                    <a href="approvals.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Autorisations</a>
                    <a href="reports.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Rapports</a>
                    <a href="urgence_autorisations.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">
                        <i class="fas fa-bolt mr-2"></i>Urgences
                    </a>
                    <a href="../auth/logout.php" class="text-gray-400 hover:bg-red-500/20 px-4 py-2 rounded-xl transition">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-6 py-8">
        <!-- Alertes importantes -->
        <?php if ($stats['pending_users'] > 0 || $stats['retards'] > 0): ?>
        <div class="glass-effect rounded-2xl p-6 mb-8 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-yellow-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-bold text-lg">Alertes nécessitant votre attention</h3>
                        <p class="text-gray-400">
                            <?php 
                                $alerts = [];
                                if ($stats['pending_users'] > 0) $alerts[] = $stats['pending_users'] . " utilisateur(s) en attente";
                                if ($stats['retards'] > 0) $alerts[] = $stats['retards'] . " retard(s) de pointage";
                                echo implode(' • ', $alerts);
                            ?>
                        </p>
                    </div>
                </div>
                <a href="users.php" class="bg-yellow-500 text-white px-6 py-2 rounded-xl hover:bg-yellow-600 transition">
                    Vérifier
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="glass-effect rounded-2xl p-6 text-white stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Utilisateurs totaux</p>
                        <h3 class="text-3xl font-bold"><?php echo $stats['total_users']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-users text-blue-400"></i>
                    </div>
                </div>
            </div>
            
            <div class="glass-effect rounded-2xl p-6 text-white stat-card <?php echo $stats['pending_users'] > 0 ? 'pulse-alert border border-yellow-500/50' : ''; ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">En attente</p>
                        <h3 class="text-3xl font-bold"><?php echo $stats['pending_users']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-yellow-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-400"></i>
                    </div>
                </div>
            </div>
            
            <div class="glass-effect rounded-2xl p-6 text-white stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Autorisations</p>
                        <h3 class="text-3xl font-bold"><?php echo $stats['total_autorisations']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-file-alt text-green-400"></i>
                    </div>
                </div>
            </div>
            
            <div class="glass-effect rounded-2xl p-6 text-white stat-card <?php echo $stats['retards'] > 0 ? 'pulse-alert border border-red-500/50' : ''; ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Retards</p>
                        <h3 class="text-3xl font-bold"><?php echo $stats['retards']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Utilisateurs en attente -->
            <div class="glass-effect rounded-2xl p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-white">Utilisateurs en attente</h2>
                    <a href="users.php" class="text-gray-400 hover:text-white text-sm">Voir tout</a>
                </div>
                
                <?php if (empty($pending_users)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-check-circle text-3xl text-green-400 mb-4"></i>
                        <p class="text-gray-400">Aucun utilisateur en attente</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($pending_users as $user): ?>
                            <div class="bg-white/5 rounded-xl p-4 border border-white/10 hover:bg-white/10 transition group">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h3 class="text-white font-semibold"><?php echo $user['prenom'] . ' ' . $user['nom']; ?></h3>
                                        <p class="text-gray-400 text-sm"><?php echo $user['email']; ?></p>
                                        <p class="text-gray-400 text-sm">Chambre: <?php echo $user['chambre']; ?></p>
                                    </div>
                                    <div class="flex space-x-2 opacity-0 group-hover:opacity-100 transition">
                                        <a href="users.php?approve=<?php echo $user['id']; ?>" class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center hover:bg-green-600 transition">
                                            <i class="fas fa-check text-white text-sm"></i>
                                        </a>
                                        <a href="users.php?reject=<?php echo $user['id']; ?>" class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center hover:bg-red-600 transition">
                                            <i class="fas fa-times text-white text-sm"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Autorisations récentes -->
            <div class="glass-effect rounded-2xl p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-white">Autorisations récentes</h2>
                    <a href="approvals.php" class="text-gray-400 hover:text-white text-sm">Voir tout</a>
                </div>
                
                <?php if (empty($recent_autorisations)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-3xl text-gray-400 mb-4"></i>
                        <p class="text-gray-400">Aucune autorisation récente</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recent_autorisations as $auth): ?>
                            <div class="bg-white/5 rounded-xl p-4 border border-white/10 hover:bg-white/10 transition">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-white font-semibold"><?php echo $auth['prenom'] . ' ' . $auth['nom']; ?></h3>
                                        <p class="text-gray-400 text-sm">
                                            <?php echo date('d/m', strtotime($auth['date_depart'])); ?> - 
                                            <?php echo date('d/m/Y', strtotime($auth['date_retour'])); ?>
                                        </p>
                                        <p class="text-gray-400 text-sm">Chambre: <?php echo $auth['chambre']; ?></p>
                                    </div>
                                    <span class="<?php 
                                        echo match($auth['status']) {
                                            'approved' => 'bg-green-500/30 text-green-300',
                                            'pending' => 'bg-yellow-500/30 text-yellow-300',
                                            'rejected' => 'bg-red-500/30 text-red-300',
                                            default => 'bg-gray-500/30 text-gray-300'
                                        };
                                    ?> px-2 py-1 rounded-full text-xs font-medium">
                                        <?php echo ucfirst($auth['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="glass-effect rounded-2xl p-6 mt-8">
            <h2 class="text-xl font-bold text-white mb-6">Actions Rapides</h2>
            <div class="grid md:grid-cols-4 gap-4">
                <a href="users.php" class="bg-blue-500/20 text-blue-300 p-4 rounded-xl hover:bg-blue-500/30 transition border border-blue-500/30 text-center">
                    <i class="fas fa-users text-2xl mb-2"></i>
                    <div class="font-semibold">Gérer Utilisateurs</div>
                </a>
                <a href="approvals.php" class="bg-green-500/20 text-green-300 p-4 rounded-xl hover:bg-green-500/30 transition border border-green-500/30 text-center">
                    <i class="fas fa-check-circle text-2xl mb-2"></i>
                    <div class="font-semibold">Approbations</div>
                </a>
                <a href="reports.php" class="bg-purple-500/20 text-purple-300 p-4 rounded-xl hover:bg-purple-500/30 transition border border-purple-500/30 text-center">
                    <i class="fas fa-chart-bar text-2xl mb-2"></i>
                    <div class="font-semibold">Rapports</div>
                </a>
                <a href="../auth/logout.php" class="bg-red-500/20 text-red-300 p-4 rounded-xl hover:bg-red-500/30 transition border border-red-500/30 text-center">
                    <i class="fas fa-sign-out-alt text-2xl mb-2"></i>
                    <div class="font-semibold">Déconnexion</div>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Animation des cartes de statistiques
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = (index * 0.1) + 's';
            });
        });
    </script>
</body>
</html>