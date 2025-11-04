<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../auth/login.php');
    exit;
}

// Gérer l'approbation/rejet des utilisateurs
if (isset($_GET['approve'])) {
    $user_id = intval($_GET['approve']);
    $sql = "UPDATE users SET status = 'active' WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    header('Location: users.php?success=Utilisateur approuvé');
    exit;
}

if (isset($_GET['reject'])) {
    $user_id = intval($_GET['reject']);
    $sql = "UPDATE users SET status = 'rejected' WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    header('Location: users.php?success=Utilisateur rejeté');
    exit;
}

// Filtrer les utilisateurs
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM users WHERE role = 'user'";
$params = [];

if ($filter === 'pending') {
    $sql .= " AND status = 'pending'";
} elseif ($filter === 'active') {
    $sql .= " AND status = 'active'";
} elseif ($filter === 'rejected') {
    $sql .= " AND status = 'rejected'";
}

if (!empty($search)) {
    $sql .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
    $search_term = "%$search%";
    $params = [$search_term, $search_term, $search_term];
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Utilisateurs - CMC Tamsna</title>
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
        .slide-in {
            animation: slideIn 0.6s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body class="gradient-bg min-h-screen">
    <!-- Header -->
    <header class="glass-effect border-b border-white/10">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="w-12 h-12 bg-red-500/20 rounded-2xl flex items-center justify-center hover:scale-110 transition">
                        <i class="fas fa-arrow-left text-red-400 text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-white font-bold text-xl">Gestion des Utilisateurs</h1>
                        <p class="text-gray-400 text-sm">Approbation et gestion des comptes</p>
                    </div>
                </div>
                
                <nav class="flex space-x-2">
                    <a href="index.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Tableau de Bord</a>
                    <a href="users.php" class="bg-red-500/20 text-white px-4 py-2 rounded-xl">Utilisateurs</a>
                    <a href="approvals.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Autorisations</a>
                    <a href="reports.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Rapports</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-6 py-8">
        <?php if (isset($_GET['success'])): ?>
            <div class="glass-effect border border-green-500/50 rounded-2xl p-4 mb-6 slide-in">
                <div class="flex items-center text-green-400">
                    <i class="fas fa-check-circle mr-3 text-xl"></i>
                    <span class="font-semibold"><?php echo $_GET['success']; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filtres et recherche -->
        <div class="glass-effect rounded-2xl p-6 mb-6 fade-in">
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-white font-semibold mb-3">Filtrer par statut</label>
                    <div class="flex space-x-2 flex-wrap gap-2">
                        <a href="?filter=all" class="<?php echo $filter === 'all' ? 'bg-red-500 text-white' : 'bg-white/10 text-gray-300'; ?> px-4 py-2 rounded-xl hover:bg-white/20 transition">Tous</a>
                        <a href="?filter=pending" class="<?php echo $filter === 'pending' ? 'bg-yellow-500 text-white' : 'bg-white/10 text-gray-300'; ?> px-4 py-2 rounded-xl hover:bg-white/20 transition">En attente</a>
                        <a href="?filter=active" class="<?php echo $filter === 'active' ? 'bg-green-500 text-white' : 'bg-white/10 text-gray-300'; ?> px-4 py-2 rounded-xl hover:bg-white/20 transition">Actifs</a>
                        <a href="?filter=rejected" class="<?php echo $filter === 'rejected' ? 'bg-red-500 text-white' : 'bg-white/10 text-gray-300'; ?> px-4 py-2 rounded-xl hover:bg-white/20 transition">Rejetés</a>
                    </div>
                </div>
                
                <div>
                    <label class="block text-white font-semibold mb-3">Rechercher</label>
                    <form method="GET" class="flex space-x-2">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Nom, prénom ou email..."
                               class="flex-1 px-4 py-2 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:border-transparent transition">
                        <button type="submit" class="bg-red-500 text-white px-6 py-2 rounded-xl hover:bg-red-600 transition">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="users.php" class="bg-gray-500 text-white px-4 py-2 rounded-xl hover:bg-gray-600 transition">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Liste des utilisateurs -->
        <div class="glass-effect rounded-2xl p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-white">
                    <?php echo count($users); ?> utilisateur(s) 
                    <?php echo $filter !== 'all' ? " - " . ucfirst($filter) : ''; ?>
                </h2>
            </div>

            <?php if (empty($users)): ?>
                <div class="text-center py-12 fade-in">
                    <div class="w-24 h-24 bg-gray-500/20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-users text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-400 mb-2">Aucun utilisateur trouvé</h3>
                    <p class="text-gray-500">Aucun utilisateur ne correspond à vos critères de recherche.</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($users as $index => $user): ?>
                        <div class="bg-white/5 rounded-2xl p-6 border border-white/10 hover:bg-white/10 transition slide-in" style="animation-delay: <?php echo $index * 0.1; ?>s">
                            <div class="grid md:grid-cols-4 gap-6 items-center">
                                <div>
                                    <h3 class="text-white font-bold text-lg"><?php echo $user['prenom'] . ' ' . $user['nom']; ?></h3>
                                    <p class="text-gray-400 text-sm"><?php echo $user['email']; ?></p>
                                </div>
                                
                                <div>
                                    <p class="text-gray-400 text-sm">Téléphone</p>
                                    <p class="text-white font-semibold"><?php echo $user['telephone']; ?></p>
                                </div>
                                
                                <div>
                                    <p class="text-gray-400 text-sm">Chambre</p>
                                    <p class="text-white font-semibold"><?php echo $user['chambre']; ?></p>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <span class="<?php 
                                        echo match($user['status']) {
                                            'active' => 'bg-green-500/30 text-green-300',
                                            'pending' => 'bg-yellow-500/30 text-yellow-300 pulse-alert',
                                            'rejected' => 'bg-red-500/30 text-red-300',
                                            default => 'bg-gray-500/30 text-gray-300'
                                        };
                                    ?> px-3 py-1 rounded-full text-sm font-medium">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                    
                                    <?php if ($user['status'] === 'pending'): ?>
                                        <div class="flex space-x-2">
                                            <a href="?approve=<?php echo $user['id']; ?>" 
                                               class="w-10 h-10 bg-green-500/20 rounded-xl flex items-center justify-center hover:bg-green-500/30 transition group"
                                               title="Approuver">
                                                <i class="fas fa-check text-green-400 group-hover:text-green-300"></i>
                                            </a>
                                            <a href="?reject=<?php echo $user['id']; ?>" 
                                               class="w-10 h-10 bg-red-500/20 rounded-xl flex items-center justify-center hover:bg-red-500/30 transition group"
                                               title="Rejeter">
                                                <i class="fas fa-times text-red-400 group-hover:text-red-300"></i>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-gray-500 text-sm">
                                            <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Informations supplémentaires -->
                            <div class="mt-4 pt-4 border-t border-white/10">
                                <div class="grid md:grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-400">Tél. parent:</span>
                                        <span class="text-white ml-2"><?php echo $user['parent_telephone']; ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400">Inscrit le:</span>
                                        <span class="text-white ml-2"><?php echo date('d/m/Y à H:i', strtotime($user['created_at'])); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400">Dernière modification:</span>
                                        <span class="text-white ml-2"><?php echo date('d/m/Y à H:i', strtotime($user['updated_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Confirmation pour les actions
        document.addEventListener('DOMContentLoaded', function() {
            const rejectLinks = document.querySelectorAll('a[href*="reject="]');
            rejectLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!confirm('Êtes-vous sûr de vouloir rejeter cet utilisateur ?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>