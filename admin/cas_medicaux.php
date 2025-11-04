<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../auth/login.php');
    exit;
}

// Gestion des actions d'urgence
if (isset($_GET['resoudre'])) {
    $urgence_id = intval($_GET['resoudre']);
    $sql = "UPDATE emergency_cases SET statut = 'resolu', updated_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$urgence_id]);
    log_activity($_SESSION['user_id'], "Cas d'urgence #$urgence_id résolu", 'EMERGENCY_RESOLVED');
    header('Location: cas_medicaux.php?success=Cas résolu');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_urgence'])) {
    $nom = sanitize($_POST['nom']);
    $prenom = sanitize($_POST['prenom']);
    $chambre = sanitize($_POST['chambre']);
    $telephone = sanitize($_POST['telephone']);
    $parent_telephone = sanitize($_POST['parent_telephone']);
    $type_urgence = sanitize($_POST['type_urgence']);
    $description = sanitize($_POST['description']);
    $ambulance_appelee = isset($_POST['ambulance_appelee']) ? 1 : 0;
    $hopital = sanitize($_POST['hopital']);
    
    $sql = "INSERT INTO emergency_cases (nom, prenom, chambre, telephone, parent_telephone, type_urgence, description, ambulance_appelee, hopital) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$nom, $prenom, $chambre, $telephone, $parent_telephone, $type_urgence, $description, $ambulance_appelee, $hopital])) {
        log_activity($_SESSION['user_id'], "Nouveau cas d'urgence: $prenom $nom", 'EMERGENCY_ADDED');
        header('Location: cas_medicaux.php?success=Cas d\'urgence ajouté');
        exit;
    }
}

// Récupérer les cas d'urgence
$filter = $_GET['filter'] ?? 'en_cours';
$sql = "SELECT * FROM emergency_cases";
if ($filter === 'en_cours') {
    $sql .= " WHERE statut = 'en_cours'";
} elseif ($filter === 'resolus') {
    $sql .= " WHERE statut = 'resolu'";
}
$sql .= " ORDER BY date_incident DESC";
$urgences = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cas Médicaux - CMC Tamsna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg { background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%); }
        .glass-effect { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .urgence-medical { border-left: 4px solid #ef4444; }
        .urgence-accident { border-left: 4px solid #f59e0b; }
        .urgence-autre { border-left: 4px solid #6366f1; }
        .pulse-urgence { animation: pulse 2s infinite; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
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
                        <h1 class="text-white font-bold text-xl">Cas Médicaux d'Urgence</h1>
                        <p class="text-gray-400 text-sm">Gestion des urgences médicales et accidents</p>
                    </div>
                </div>
                <nav class="flex space-x-2">
                    <a href="gestion_autorisations.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Accueil</a>
                    <a href="autorisations_normales.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Normales</a>
                    <a href="autorisations_urgence.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Urgences</a>
                    <a href="cas_medicaux.php" class="bg-orange-500/20 text-white px-4 py-2 rounded-xl">Médicaux</a>
                    <a href="statistiques_medicales.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Statistiques</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-6 py-8">
        <!-- Alertes urgentes -->
        <?php
        $urgences_actives = array_filter($urgences, fn($u) => $u['statut'] === 'en_cours');
        if (!empty($urgences_actives)): ?>
        <div class="glass-effect rounded-2xl p-6 mb-6 border-l-4 border-red-500 pulse-urgence">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-bold text-lg">Alertes Médicales Actives</h3>
                        <p class="text-gray-400"><?php echo count($urgences_actives); ?> cas nécessitent une attention immédiate</p>
                    </div>
                </div>
                <div class="text-red-400 font-semibold">
                    <i class="fas fa-clock mr-2"></i>Action Requise
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Formulaire d'ajout d'urgence -->
        <div class="glass-effect rounded-2xl p-6 mb-6">
            <h2 class="text-xl font-bold text-white mb-4">
                <i class="fas fa-plus-circle mr-2"></i>Nouveau Cas Médical
            </h2>
            <form method="POST" class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="text-white text-sm font-medium mb-2 block">Nom *</label>
                    <input type="text" name="nom" required class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white">
                </div>
                <div>
                    <label class="text-white text-sm font-medium mb-2 block">Prénom *</label>
                    <input type="text" name="prenom" required class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white">
                </div>
                <div>
                    <label class="text-white text-sm font-medium mb-2 block">Chambre *</label>
                    <input type="text" name="chambre" required class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white">
                </div>
                <div>
                    <label class="text-white text-sm font-medium mb-2 block">Téléphone *</label>
                    <input type="tel" name="telephone" required class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white">
                </div>
                <div>
                    <label class="text-white text-sm font-medium mb-2 block">Tél. Parent *</label>
                    <input type="tel" name="parent_telephone" required class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white">
                </div>
                <div>
                    <label class="text-white text-sm font-medium mb-2 block">Type d'urgence *</label>
                    <select name="type_urgence" required class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white">
                        <option value="medical">Urgence Médicale</option>
                        <option value="accident">Accident</option>
                        <option value="malaise">Malaise</option>
                        <option value="blessure">Blessure</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-white text-sm font-medium mb-2 block">Description *</label>
                    <textarea name="description" required rows="3" class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white"></textarea>
                </div>
                <div class="flex items-center space-x-2">
                    <input type="checkbox" name="ambulance_appelee" id="ambulance" class="rounded">
                    <label for="ambulance" class="text-white text-sm">Ambulance appelée</label>
                </div>
                <div>
                    <label class="text-white text-sm font-medium mb-2 block">Hôpital (si applicable)</label>
                    <input type="text" name="hopital" class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white">
                </div>
                <div class="md:col-span-2">
                    <button type="submit" name="ajouter_urgence" class="bg-orange-500 text-white px-6 py-3 rounded-lg hover:bg-orange-600 transition font-semibold">
                        <i class="fas fa-save mr-2"></i>Enregistrer le Cas Médical
                    </button>
                </div>
            </form>
        </div>

        <!-- Liste des urgences -->
        <div class="glass-effect rounded-2xl p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-white">Cas Médicaux Enregistrés</h2>
                <div class="flex space-x-2">
                    <a href="?filter=en_cours" class="<?php echo $filter === 'en_cours' ? 'bg-red-500 text-white' : 'bg-white/10 text-gray-300'; ?> px-4 py-2 rounded-lg">En Cours</a>
                    <a href="?filter=resolus" class="<?php echo $filter === 'resolus' ? 'bg-green-500 text-white' : 'bg-white/10 text-gray-300'; ?> px-4 py-2 rounded-lg">Résolus</a>
                    <a href="?filter=all" class="<?php echo $filter === 'all' ? 'bg-blue-500 text-white' : 'bg-white/10 text-gray-300'; ?> px-4 py-2 rounded-lg">Tous</a>
                </div>
            </div>

            <?php if (empty($urgences)): ?>
                <div class="text-center py-12 text-gray-400">
                    <i class="fas fa-heartbeat text-4xl mb-4"></i>
                    <p>Aucun cas médical enregistré</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($urgences as $urgence): ?>
                        <div class="bg-white/5 rounded-xl p-4 <?php echo 'urgence-' . $urgence['type_urgence']; ?> hover:bg-white/10 transition">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h3 class="text-white font-semibold text-lg">
                                            <?php echo htmlspecialchars($urgence['prenom'] . ' ' . $urgence['nom']); ?>
                                        </h3>
                                        <span class="text-sm px-2 py-1 rounded-full <?php echo $urgence['statut'] === 'en_cours' ? 'bg-red-500/30 text-red-300' : 'bg-green-500/30 text-green-300'; ?>">
                                            <?php echo $urgence['statut'] === 'en_cours' ? 'En Cours' : 'Résolu'; ?>
                                        </span>
                                        <?php if ($urgence['ambulance_appelee']): ?>
                                            <span class="text-sm px-2 py-1 rounded-full bg-orange-500/30 text-orange-300">
                                                <i class="fas fa-ambulance mr-1"></i>Ambulance
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="grid md:grid-cols-3 gap-4 text-sm mb-3">
                                        <div>
                                            <span class="text-gray-400">Chambre:</span>
                                            <span class="text-white ml-2"><?php echo htmlspecialchars($urgence['chambre']); ?></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400">Téléphone:</span>
                                            <span class="text-white ml-2"><?php echo htmlspecialchars($urgence['telephone']); ?></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400">Tél. Parent:</span>
                                            <span class="text-white ml-2"><?php echo htmlspecialchars($urgence['parent_telephone']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <span class="text-gray-400">Description:</span>
                                        <p class="text-white mt-1"><?php echo htmlspecialchars($urgence['description']); ?></p>
                                    </div>
                                    
                                    <?php if ($urgence['hopital']): ?>
                                        <div class="mb-2">
                                            <span class="text-gray-400">Hôpital:</span>
                                            <span class="text-white ml-2"><?php echo htmlspecialchars($urgence['hopital']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="text-gray-400 text-sm">
                                        <i class="fas fa-clock mr-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($urgence['date_incident'])); ?>
                                    </div>
                                </div>
                                
                                <?php if ($urgence['statut'] === 'en_cours'): ?>
                                    <div class="flex space-x-2">
                                        <a href="?resoudre=<?php echo $urgence['id']; ?>" 
                                           class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition text-sm"
                                           onclick="return confirm('Marquer ce cas comme résolu ?');">
                                            <i class="fas fa-check mr-1"></i>Résoudre
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>