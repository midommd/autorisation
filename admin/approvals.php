<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../auth/login.php');
    exit;
}

// Gérer l'approbation/rejet des autorisations
if (isset($_GET['approve'])) {
    $auth_id = intval($_GET['approve']);
    $sql = "UPDATE autorisations SET status = 'approved' WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$auth_id]);
    header('Location: approvals.php?success=Autorisation approuvée');
    exit;
}

if (isset($_GET['reject'])) {
    $auth_id = intval($_GET['reject']);
    $sql = "UPDATE autorisations SET status = 'rejected' WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$auth_id]);
    header('Location: approvals.php?success=Autorisation rejetée');
    exit;
}

if (isset($_GET['pointage'])) {
    $auth_id = intval($_GET['pointage']);
    $sql = "UPDATE autorisations SET pointage = 1 WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$auth_id]);
    header('Location: approvals.php?success=Pointage enregistré');
    exit;
}

// Filtrer les autorisations
$filter = $_GET['filter'] ?? 'pending';
$search = $_GET['search'] ?? '';

$sql = "SELECT a.*, u.nom, u.prenom, u.telephone as user_telephone 
        FROM autorisations a 
        JOIN users u ON a.user_id = u.id";
$params = [];

if ($filter === 'pending') {
    $sql .= " WHERE a.status = 'pending'";
} elseif ($filter === 'approved') {
    $sql .= " WHERE a.status = 'approved'";
} elseif ($filter === 'rejected') {
    $sql .= " WHERE a.status = 'rejected'";
} elseif ($filter === 'retards') {
    $sql .= " WHERE a.status = 'approved' AND a.pointage = 0 AND a.date_retour < CURDATE()";
}

if (!empty($search)) {
    $where = strpos($sql, 'WHERE') !== false ? ' AND' : ' WHERE';
    $sql .= $where . " (u.nom LIKE ? OR u.prenom LIKE ? OR a.chambre LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

$sql .= " ORDER BY a.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$autorisations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Autorisations - CMC Tamsna</title>
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
        .bounce-in {
            animation: bounceIn 0.6s ease-out;
        }
        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }
        .pulse-warning {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
            50% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
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
                        <h1 class="text-white font-bold text-xl">Gestion des Autorisations</h1>
                        <p class="text-gray-400 text-sm">Approbation et suivi des sorties</p>
                    </div>
                </div>
                
                <nav class="flex space-x-2">
                    <a href="index.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Tableau de Bord</a>
                    <a href="users.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Utilisateurs</a>
                    <a href="approvals.php" class="bg-red-500/20 text-white px-4 py-2 rounded-xl">Autorisations</a>
                    <a href="urgence_autorisations.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Urgences</a>
                    <a href="reports.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Rapports</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-6 py-8">
        <?php if (isset($_GET['success'])): ?>
            <div class="glass-effect border border-green-500/50 rounded-2xl p-4 mb-6 bounce-in">
                <div class="flex items-center text-green-400">
                    <i class="fas fa-check-circle mr-3 text-xl"></i>
                    <span class="font-semibold"><?php echo $_GET['success']; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filtres et recherche -->
        <div class="glass-effect rounded-2xl p-6 mb-6 bounce-in">
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-white font-semibold mb-3">Filtrer par statut</label>
                    <div class="flex space-x-2 flex-wrap gap-2">
                        <a href="?filter=pending" class="<?php echo $filter === 'pending' ? 'bg-yellow-500 text-white' : 'bg-white/10 text-gray-300'; ?> px-4 py-2 rounded-xl hover:bg-white/20 transition">En attente</a>
                        <a href="?filter=approved" class="<?php echo $filter === 'approved' ? 'bg-green-500 text-white' : 'bg-white/10 text-gray-300'; ?> px-4 py-2 rounded-xl hover:bg-white/20 transition">Approuvées</a>
                        <a href="?filter=rejected" class="<?php echo $filter === 'rejected' ? 'bg-red-500 text-white' : 'bg-white/10 text-gray-300'; ?> px-4 py-2 rounded-xl hover:bg-white/20 transition">Rejetées</a>
                        <a href="?filter=retards" class="<?php echo $filter === 'retards' ? 'bg-orange-500 text-white pulse-warning' : 'bg-white/10 text-gray-300'; ?> px-4 py-2 rounded-xl hover:bg-white/20 transition">Retards</a>
                    </div>
                </div>
                
                <div>
                    <label class="block text-white font-semibold mb-3">Rechercher</label>
                    <form method="GET" class="flex space-x-2">
                        <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Nom, prénom ou chambre..."
                               class="flex-1 px-4 py-2 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:border-transparent transition">
                        <button type="submit" class="bg-red-500 text-white px-6 py-2 rounded-xl hover:bg-red-600 transition">
                            <i class="fas fa-search"></i>
                        </button>
                        <div class="mt-4">
                            <a href="filtered_autorisations.php" class="bg-purple-500 text-white px-6 py-3 rounded-xl hover:bg-purple-600 transition font-semibold inline-flex items-center">
                                <i class="fas fa-chart-bar mr-2"></i>Rapports par Période
                            </a>
                        </div>
                        <?php if (!empty($search)): ?>
                            <a href="approvals.php?filter=<?php echo $filter; ?>" class="bg-gray-500 text-white px-4 py-2 rounded-xl hover:bg-gray-600 transition">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Liste des autorisations -->
        <div class="glass-effect rounded-2xl p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-white">
                    <?php echo count($autorisations); ?> autorisation(s) 
                    <?php echo $filter !== 'pending' ? " - " . ucfirst($filter) : ''; ?>
                </h2>
            </div>

            <?php if (empty($autorisations)): ?>
                <div class="text-center py-12 bounce-in">
                    <div class="w-24 h-24 bg-gray-500/20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-file-alt text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-400 mb-2">Aucune autorisation trouvée</h3>
                    <p class="text-gray-500">Aucune autorisation ne correspond à vos critères de recherche.</p>
                </div>
            <?php else: ?>
                <div class="grid gap-6">
                    <?php foreach ($autorisations as $index => $auth): ?>
                        <div class="bg-white/5 rounded-2xl p-6 border border-white/10 hover:bg-white/10 transition bounce-in" 
                             style="animation-delay: <?php echo $index * 0.1; ?>s">
                            
                            <!-- En-tête -->
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-white font-bold text-xl">
                                        <?php echo $auth['prenom'] . ' ' . $auth['nom']; ?>
                                    </h3>
                                    <p class="text-gray-400">Chambre <?php echo $auth['chambre']; ?> • Tél: <?php echo $auth['user_telephone']; ?></p>
                                </div>
                                
                                <div class="text-right">
                                    <span class="<?php 
                                        echo match($auth['status']) {
                                            'approved' => 'bg-green-500/30 text-green-300',
                                            'pending' => 'bg-yellow-500/30 text-yellow-300',
                                            'rejected' => 'bg-red-500/30 text-red-300',
                                            default => 'bg-gray-500/30 text-gray-300'
                                        };
                                    ?> px-3 py-1 rounded-full text-sm font-medium">
                                        <?php echo ucfirst($auth['status']); ?>
                                    </span>
                                    <p class="text-gray-400 text-sm mt-1">
                                        <?php echo date('d/m/Y à H:i', strtotime($auth['created_at'])); ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Détails de l'autorisation -->
                            <div class="grid md:grid-cols-3 gap-6 mb-4">
                                <div class="text-center">
                                    <div class="bg-blue-500/20 rounded-xl p-4">
                                        <i class="fas fa-calendar-day text-blue-400 text-xl mb-2"></i>
                                        <p class="text-gray-400 text-sm">Date de départ</p>
                                        <p class="text-white font-bold"><?php echo date('d/m/Y', strtotime($auth['date_depart'])); ?></p>
                                    </div>
                                </div>
                                
                                <div class="text-center">
                                    <div class="bg-green-500/20 rounded-xl p-4">
                                        <i class="fas fa-calendar-check text-green-400 text-xl mb-2"></i>
                                        <p class="text-gray-400 text-sm">Date de retour</p>
                                        <p class="text-white font-bold"><?php echo date('d/m/Y', strtotime($auth['date_retour'])); ?></p>
                                    </div>
                                </div>
                                
                                <div class="text-center">
                                    <div class="bg-purple-500/20 rounded-xl p-4">
                                        <i class="fas fa-phone-alt text-purple-400 text-xl mb-2"></i>
                                        <p class="text-gray-400 text-sm">Tél. parent</p>
                                        <p class="text-white font-bold"><?php echo $auth['parent_telephone']; ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex justify-between items-center pt-4 border-t border-white/10">
                                <div class="text-sm text-gray-400">
                                    Demande soumise le <?php echo date('d/m/Y', strtotime($auth['date_demande'])); ?>
                                    <?php if ($auth['type_autorisation'] === 'urgence'): ?>
                                        • <span class="text-red-400"><i class="fas fa-bolt mr-1"></i>Urgence</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <?php if ($auth['status'] === 'pending'): ?>
                                        <a href="?approve=<?php echo $auth['id']; ?>" 
                                           class="bg-green-500 text-white px-6 py-2 rounded-xl hover:bg-green-600 transition font-semibold">
                                            <i class="fas fa-check mr-2"></i>Approuver
                                        </a>
                                        <a href="?reject=<?php echo $auth['id']; ?>" 
                                           class="bg-red-500 text-white px-6 py-2 rounded-xl hover:bg-red-600 transition font-semibold">
                                            <i class="fas fa-times mr-2"></i>Rejeter
                                        </a>
                                    <?php elseif ($auth['status'] === 'approved' && !$auth['pointage']): ?>
                                        <?php if (date('Y-m-d') <= $auth['date_retour']): ?>
                                            <span class="bg-blue-500/30 text-blue-300 px-4 py-2 rounded-xl">
                                                <i class="fas fa-clock mr-2"></i>En cours
                                            </span>
                                        <?php else: ?>
                                            <span class="bg-orange-500/30 text-orange-300 px-4 py-2 rounded-xl pulse-warning">
                                                <i class="fas fa-exclamation-triangle mr-2"></i>Retard
                                            </span>
                                        <?php endif; ?>
                                        <a href="?pointage=<?php echo $auth['id']; ?>" 
                                           class="bg-green-500 text-white px-6 py-2 rounded-xl hover:bg-green-600 transition font-semibold">
                                            <i class="fas fa-check-circle mr-2"></i>Pointer
                                        </a>
                                        
                                        <!-- BOUTON D'IMPRESSION POUR LES AUTORISATIONS APPROUVÉES -->
                                        <a href="print_autorisation.php?id=<?php echo $auth['id']; ?>" 
                                           target="_blank"
                                           class="bg-blue-500 text-white px-6 py-2 rounded-xl hover:bg-blue-600 transition font-semibold">
                                            <i class="fas fa-print mr-2"></i>Imprimer
                                        </a>
                                        
                                    <?php elseif ($auth['pointage']): ?>
                                        <span class="bg-green-500/30 text-green-300 px-4 py-2 rounded-xl">
                                            <i class="fas fa-check-double mr-2"></i>Pointé
                                        </span>
                                        
                                        <!-- BOUTON D'IMPRESSION MÊME APRÈS POINTAGE -->
                                        <a href="print_autorisation.php?id=<?php echo $auth['id']; ?>" 
                                           target="_blank"
                                           class="bg-blue-500 text-white px-6 py-2 rounded-xl hover:bg-blue-600 transition font-semibold">
                                            <i class="fas fa-print mr-2"></i>Imprimer
                                        </a>
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
        document.addEventListener('DOMContentLoaded', function() {
            const rejectLinks = document.querySelectorAll('a[href*="reject="]');
            rejectLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!confirm('Êtes-vous sûr de vouloir rejeter cette autorisation ?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>