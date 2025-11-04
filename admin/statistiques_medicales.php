<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../auth/login.php');
    exit;
}

// Recherche d'utilisateurs
$search_term = sanitize($_GET['search_user'] ?? '');
$selected_user_id = $_GET['user_id'] ?? '';
$selected_user = null;
$visites_medicales = [];

if (!empty($search_term)) {
    $search_sql = "SELECT id, nom, prenom, email, telephone, chambre FROM users 
                   WHERE (nom LIKE ? OR prenom LIKE ? OR email LIKE ?) 
                   AND status = 'active' 
                   LIMIT 10";
    $search_stmt = $pdo->prepare($search_sql);
    $search_term_like = "%$search_term%";
    $search_stmt->execute([$search_term_like, $search_term_like, $search_term_like]);
    $users = $search_stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (!empty($selected_user_id)) {
    // Récupérer les infos de l'utilisateur
    $user_sql = "SELECT * FROM users WHERE id = ?";
    $user_stmt = $pdo->prepare($user_sql);
    $user_stmt->execute([$selected_user_id]);
    $selected_user = $user_stmt->fetch(PDO::FETCH_ASSOC);

    // Récupérer les visites médicales (à implémenter dans la base de données)
    $visites_sql = "SELECT * FROM visites_medicales WHERE user_id = ? ORDER BY date_visite DESC";
    $visites_stmt = $pdo->prepare($visites_sql);
    $visites_stmt->execute([$selected_user_id]);
    $visites_medicales = $visites_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques Médicales - CMC Tamsna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg { background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%); }
        .glass-effect { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="gradient-bg min-h-screen">
    <header class="glass-effect border-b border-white/10">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <a href="gestion_autorisations.php" class="w-12 h-12 bg-red-500/20 rounded-2xl flex items-center justify-center hover:scale-110 transition">
                        <i class="fas fa-arrow-left text-red-400 text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-white font-bold text-xl">Statistiques Médicales</h1>
                        <p class="text-gray-400 text-sm">Suivi des consultations médicales</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-6 py-8">
        <!-- Recherche de stagiaire -->
        <div class="glass-effect rounded-2xl p-6 mb-6">
            <h2 class="text-xl font-bold text-white mb-4">Rechercher un Stagiaire</h2>
            <form method="GET" class="flex space-x-4">
                <div class="flex-1">
                    <input type="text" 
                           name="search_user" 
                           value="<?php echo htmlspecialchars($search_term); ?>"
                           placeholder="Nom, prénom ou email du stagiaire..."
                           class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                </div>
                <button type="submit" class="bg-red-500 text-white px-6 py-3 rounded-xl hover:bg-red-600 transition font-semibold">
                    <i class="fas fa-search mr-2"></i>Rechercher
                </button>
            </form>

            <?php if (!empty($users)): ?>
                <div class="mt-4 grid gap-2">
                    <?php foreach ($users as $user): ?>
                        <a href="?user_id=<?php echo $user['id']; ?>" 
                           class="bg-white/5 p-4 rounded-xl hover:bg-white/10 transition block">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="text-white font-semibold"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h3>
                                    <p class="text-gray-400 text-sm">Chambre <?php echo htmlspecialchars($user['chambre']); ?> • <?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400"></i>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($selected_user): ?>
            <!-- Profil médical du stagiaire -->
            <div class="glass-effect rounded-2xl p-6 mb-6">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-white">
                            <?php echo htmlspecialchars($selected_user['prenom'] . ' ' . $selected_user['nom']); ?>
                        </h2>
                        <p class="text-gray-400">
                            Chambre <?php echo htmlspecialchars($selected_user['chambre']); ?> • 
                            <?php echo htmlspecialchars($selected_user['email']); ?>
                        </p>
                    </div>
                    <button onclick="ajouterVisiteMedicale()" class="bg-green-500 text-white px-4 py-2 rounded-xl hover:bg-green-600 transition">
                        <i class="fas fa-plus mr-2"></i>Nouvelle visite
                    </button>
                </div>

                <!-- Statistiques -->
                <div class="grid md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-500/20 rounded-xl p-4 text-center">
                        <p class="text-blue-300 text-sm">Visites cette semaine</p>
                        <p class="text-white font-bold text-2xl">2</p>
                    </div>
                    <div class="bg-green-500/20 rounded-xl p-4 text-center">
                        <p class="text-green-300 text-sm">Visites ce mois</p>
                        <p class="text-white font-bold text-2xl">5</p>
                    </div>
                    <div class="bg-yellow-500/20 rounded-xl p-4 text-center">
                        <p class="text-yellow-300 text-sm">3 derniers mois</p>
                        <p class="text-white font-bold text-2xl">12</p>
                    </div>
                    <div class="bg-purple-500/20 rounded-xl p-4 text-center">
                        <p class="text-purple-300 text-sm">Total général</p>
                        <p class="text-white font-bold text-2xl">24</p>
                    </div>
                </div>

                <!-- Historique des visites -->
                <h3 class="text-xl font-bold text-white mb-4">Historique des Visites Médicales</h3>
                <?php if (empty($visites_medicales)): ?>
                    <div class="text-center py-8 text-gray-400">
                        <i class="fas fa-file-medical text-4xl mb-4"></i>
                        <p>Aucune visite médicale enregistrée</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($visites_medicales as $visite): ?>
                            <div class="bg-white/5 rounded-xl p-4 border-l-4 border-green-500">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="text-white font-semibold"><?php echo htmlspecialchars($visite['type_visite']); ?></h4>
                                        <p class="text-gray-300 mt-1"><?php echo htmlspecialchars($visite['diagnostic']); ?></p>
                                        <p class="text-gray-400 text-sm mt-2">
                                            <i class="fas fa-user-md mr-1"></i>
                                            <?php echo htmlspecialchars($visite['medecin']); ?> • 
                                            <i class="fas fa-map-marker-alt mr-1"></i>
                                            <?php echo htmlspecialchars($visite['lieu']); ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-white font-semibold"><?php echo date('d/m/Y', strtotime($visite['date_visite'])); ?></p>
                                        <p class="text-gray-400 text-sm"><?php echo date('H:i', strtotime($visite['date_visite'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function ajouterVisiteMedicale() {
            alert('Fonctionnalité à implémenter : Ajouter une visite médicale');
            // Ouvrir un modal ou rediriger vers un formulaire d'ajout
        }
    </script>
</body>
</html>