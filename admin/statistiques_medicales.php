<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../auth/login.php');
    exit;
}

// Vérifier et créer la table si elle n'existe pas
$check_table_sql = "CREATE TABLE IF NOT EXISTS visites_medicales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type_visite VARCHAR(100) NOT NULL,
    medecin VARCHAR(100) NOT NULL,
    lieu VARCHAR(100) NOT NULL,
    diagnostic TEXT,
    traitement TEXT,
    date_visite DATE NOT NULL,
    heure_visite TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$pdo->exec($check_table_sql);

// Initialiser les variables
$success = '';
$error = '';

// Gérer l'ajout d'une visite médicale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_visite'])) {
    $user_id = intval($_POST['user_id']);
    $type_visite = sanitize($_POST['type_visite']);
    $medecin = sanitize($_POST['medecin']);
    $lieu = sanitize($_POST['lieu']);
    $diagnostic = sanitize($_POST['diagnostic']);
    $traitement = sanitize($_POST['traitement']);
    $date_visite = sanitize($_POST['date_visite']);
    $heure_visite = sanitize($_POST['heure_visite']);
    
    // Validation
    if (empty($user_id) || empty($type_visite) || empty($medecin)) {
        $error = "Veuillez remplir tous les champs obligatoires";
    } else {
        // Insérer la visite médicale
        $sql = "INSERT INTO visites_medicales (user_id, type_visite, medecin, lieu, diagnostic, traitement, date_visite, heure_visite) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$user_id, $type_visite, $medecin, $lieu, $diagnostic, $traitement, $date_visite, $heure_visite])) {
            $success = "Visite médicale enregistrée avec succès pour le stagiaire";
            log_activity($_SESSION['user_id'], "Nouvelle visite médicale ajoutée pour l'utilisateur #$user_id", 'VISITE_MEDICALE_ADDED');
        } else {
            $error = "Erreur lors de l'enregistrement de la visite médicale";
        }
    }
}

// Recherche d'utilisateurs
$search_term = sanitize($_GET['search_user'] ?? '');
$selected_user_id = $_GET['user_id'] ?? '';
$selected_user = null;
$visites_medicales = [];
$stats_visites = [
    'semaine' => 0,
    'mois' => 0,
    'trimestre' => 0,
    'total' => 0
];

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

    if ($selected_user) {
        // Récupérer les visites médicales
        try {
            $visites_sql = "SELECT * FROM visites_medicales WHERE user_id = ? ORDER BY date_visite DESC, heure_visite DESC";
            $visites_stmt = $pdo->prepare($visites_sql);
            $visites_stmt->execute([$selected_user_id]);
            $visites_medicales = $visites_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculer les statistiques
            $stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN date_visite >= CURDATE() - INTERVAL 7 DAY THEN 1 ELSE 0 END) as semaine,
                SUM(CASE WHEN date_visite >= CURDATE() - INTERVAL 30 DAY THEN 1 ELSE 0 END) as mois,
                SUM(CASE WHEN date_visite >= CURDATE() - INTERVAL 90 DAY THEN 1 ELSE 0 END) as trimestre
                FROM visites_medicales WHERE user_id = ?";
            $stats_stmt = $pdo->prepare($stats_sql);
            $stats_stmt->execute([$selected_user_id]);
            $stats_result = $stats_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($stats_result) {
                $stats_visites = $stats_result;
            }
        } catch (PDOException $e) {
            $error = "Erreur lors du chargement des visites médicales: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dossiers Médicaux - CMC Tamsna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg { background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%); }
        .glass-effect { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .medical-record { border-left: 4px solid #10b981; }
        .urgence-medical { border-left: 4px solid #ef4444; }
        .consultation-normal { border-left: 4px solid #3b82f6; }
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
                        <h1 class="text-white font-bold text-xl">Dossiers Médicaux</h1>
                        <p class="text-gray-400 text-sm">Suivi complet des consultations médicales</p>
                    </div>
                </div>
                <nav class="flex space-x-2">
                    <a href="gestion_autorisations.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Accueil</a>
                    <a href="cas_medicaux.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Urgences</a>
                    <a href="statistiques_medicales.php" class="bg-green-500/20 text-white px-4 py-2 rounded-xl">Dossiers</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-6 py-8">
        <!-- Messages -->
        <?php if ($success): ?>
            <div class="glass-effect border border-green-500/50 rounded-2xl p-4 mb-6">
                <div class="flex items-center text-green-400">
                    <i class="fas fa-check-circle mr-3 text-xl"></i>
                    <span class="font-semibold"><?php echo htmlspecialchars($success); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="glass-effect border border-red-500/50 rounded-2xl p-4 mb-6">
                <div class="flex items-center text-red-400">
                    <i class="fas fa-exclamation-triangle mr-3 text-xl"></i>
                    <span class="font-semibold"><?php echo htmlspecialchars($error); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recherche de stagiaire -->
        <div class="glass-effect rounded-2xl p-6 mb-6">
            <h2 class="text-xl font-bold text-white mb-4">
                <i class="fas fa-search mr-2"></i>Rechercher un Stagiaire
            </h2>
            <form method="GET" class="flex space-x-4">
                <div class="flex-1">
                    <input type="text" 
                           name="search_user" 
                           value="<?php echo htmlspecialchars($search_term); ?>"
                           placeholder="Nom, prénom ou email du stagiaire..."
                           class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500/50">
                </div>
                <button type="submit" class="bg-green-500 text-white px-6 py-3 rounded-xl hover:bg-green-600 transition font-semibold">
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
            <!-- Dossier médical du stagiaire -->
            <div class="glass-effect rounded-2xl p-6 mb-6">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-white">
                            <i class="fas fa-file-medical mr-2"></i>
                            Dossier Médical - <?php echo htmlspecialchars($selected_user['prenom'] . ' ' . $selected_user['nom']); ?>
                        </h2>
                        <p class="text-gray-400">
                            Chambre <?php echo htmlspecialchars($selected_user['chambre']); ?> • 
                            <?php echo htmlspecialchars($selected_user['email']); ?> •
                            Tél: <?php echo htmlspecialchars($selected_user['telephone']); ?>
                        </p>
                    </div>
                    <button onclick="document.getElementById('modal_visite').classList.remove('hidden')" 
                            class="bg-green-500 text-white px-4 py-2 rounded-xl hover:bg-green-600 transition font-semibold">
                        <i class="fas fa-plus mr-2"></i>Nouvelle visite
                    </button>
                </div>

                <!-- Statistiques -->
                <div class="grid md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-500/20 rounded-xl p-4 text-center">
                        <p class="text-blue-300 text-sm">Cette semaine</p>
                        <p class="text-white font-bold text-2xl"><?php echo $stats_visites['semaine'] ?? 0; ?></p>
                    </div>
                    <div class="bg-green-500/20 rounded-xl p-4 text-center">
                        <p class="text-green-300 text-sm">Ce mois</p>
                        <p class="text-white font-bold text-2xl"><?php echo $stats_visites['mois'] ?? 0; ?></p>
                    </div>
                    <div class="bg-yellow-500/20 rounded-xl p-4 text-center">
                        <p class="text-yellow-300 text-sm">3 derniers mois</p>
                        <p class="text-white font-bold text-2xl"><?php echo $stats_visites['trimestre'] ?? 0; ?></p>
                    </div>
                    <div class="bg-purple-500/20 rounded-xl p-4 text-center">
                        <p class="text-purple-300 text-sm">Total général</p>
                        <p class="text-white font-bold text-2xl"><?php echo $stats_visites['total'] ?? 0; ?></p>
                    </div>
                </div>

                <!-- Historique des visites -->
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-history mr-2"></i>Historique des Visites Médicales
                </h3>
                
                <?php if (empty($visites_medicales)): ?>
                    <div class="text-center py-12 text-gray-400">
                        <i class="fas fa-file-medical text-4xl mb-4"></i>
                        <p class="text-lg mb-2">Aucune visite médicale enregistrée</p>
                        <p class="text-sm">Cliquez sur "Nouvelle visite" pour créer le premier enregistrement</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($visites_medicales as $visite): ?>
                            <div class="bg-white/5 rounded-xl p-4 medical-record <?php echo $visite['type_visite'] === 'urgence' ? 'urgence-medical' : 'consultation-normal'; ?>">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <h4 class="text-white font-semibold text-lg">
                                                <?php 
                                                    $types_visites = [
                                                        'consultation' => 'Consultation',
                                                        'urgence' => 'Urgence Médicale',
                                                        'suivi' => 'Suivi Médical',
                                                        'vaccination' => 'Vaccination',
                                                        'examen' => 'Examen Médical',
                                                        'autre' => 'Autre Visite'
                                                    ];
                                                    echo $types_visites[$visite['type_visite']] ?? ucfirst($visite['type_visite']);
                                                ?>
                                            </h4>
                                            <?php if ($visite['type_visite'] === 'urgence'): ?>
                                                <span class="bg-red-500/30 text-red-300 px-2 py-1 rounded-full text-xs">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>Urgent
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="grid md:grid-cols-2 gap-4 mb-3">
                                            <div>
                                                <p class="text-gray-300">
                                                    <strong>Médecin:</strong> <?php echo htmlspecialchars($visite['medecin']); ?>
                                                </p>
                                                <p class="text-gray-300">
                                                    <strong>Lieu:</strong> <?php echo htmlspecialchars($visite['lieu']); ?>
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-gray-300">
                                                    <strong>Date:</strong> <?php echo date('d/m/Y', strtotime($visite['date_visite'])); ?>
                                                </p>
                                                <p class="text-gray-300">
                                                    <strong>Heure:</strong> <?php echo date('H:i', strtotime($visite['heure_visite'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($visite['diagnostic'])): ?>
                                            <div class="mb-2">
                                                <p class="text-gray-400 text-sm font-semibold">Diagnostic:</p>
                                                <p class="text-white"><?php echo htmlspecialchars($visite['diagnostic']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($visite['traitement'])): ?>
                                            <div class="mb-2">
                                                <p class="text-gray-400 text-sm font-semibold">Traitement:</p>
                                                <p class="text-white"><?php echo htmlspecialchars($visite['traitement']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Aucun stagiaire sélectionné -->
            <div class="glass-effect rounded-2xl p-12 text-center">
                <div class="w-24 h-24 bg-green-500/20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-stethoscope text-green-400 text-3xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-4">Dossiers Médicaux des Stagiaires</h3>
                <p class="text-gray-400 text-lg mb-6">
                    Recherchez un stagiaire pour accéder à son dossier médical complet
                </p>
                <div class="bg-white/5 rounded-xl p-6 inline-block">
                    <h4 class="text-white font-semibold mb-3">Fonctionnalités disponibles :</h4>
                    <ul class="text-gray-400 text-sm space-y-2 text-left">
                        <li class="flex items-center">
                            <i class="fas fa-search text-green-400 mr-3 w-5"></i>
                            Recherche par nom, prénom ou email
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-file-medical text-blue-400 mr-3 w-5"></i>
                            Dossier médical individuel complet
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-plus-circle text-yellow-400 mr-3 w-5"></i>
                            Ajout de nouvelles visites médicales
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-chart-bar text-purple-400 mr-3 w-5"></i>
                            Statistiques détaillées par période
                        </li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal pour nouvelle visite médicale -->
    <div id="modal_visite" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
        <div class="glass-effect rounded-2xl p-6 w-full max-w-2xl mx-4">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-white">Nouvelle Visite Médicale</h3>
                <button onclick="document.getElementById('modal_visite').classList.add('hidden')" 
                        class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="user_id" value="<?php echo $selected_user_id; ?>">
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-white text-sm font-medium mb-2 block">Type de visite *</label>
                        <select name="type_visite" required class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white">
                            <option value="">Sélectionnez...</option>
                            <option value="consultation">Consultation normale</option>
                            <option value="urgence">Urgence médicale</option>
                            <option value="suivi">Suivi médical</option>
                            <option value="vaccination">Vaccination</option>
                            <option value="examen">Examen médical</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="text-white text-sm font-medium mb-2 block">Médecin *</label>
                        <input type="text" name="medecin" required class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white"
                               placeholder="Dr. Nom Prénom">
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-white text-sm font-medium mb-2 block">Lieu *</label>
                        <input type="text" name="lieu" required class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white" 
                               placeholder="Ex: Infirmerie, Hôpital, Cabinet...">
                    </div>
                    
                    <div>
                        <label class="text-white text-sm font-medium mb-2 block">Date de la visite *</label>
                        <input type="date" name="date_visite" required class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white"
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-white text-sm font-medium mb-2 block">Heure de la visite *</label>
                        <input type="time" name="heure_visite" required class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white"
                               value="<?php echo date('H:i'); ?>">
                    </div>
                </div>
                
                <div>
                    <label class="text-white text-sm font-medium mb-2 block">Diagnostic</label>
                    <textarea name="diagnostic" rows="3" class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white"
                              placeholder="Diagnostic établi par le médecin..."></textarea>
                </div>
                
                <div>
                    <label class="text-white text-sm font-medium mb-2 block">Traitement prescrit</label>
                    <textarea name="traitement" rows="3" class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white"
                              placeholder="Traitement, médicaments, recommandations..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-4 pt-4">
                    <button type="button" 
                            onclick="document.getElementById('modal_visite').classList.add('hidden')" 
                            class="px-6 py-2 text-gray-400 hover:text-white transition">
                        Annuler
                    </button>
                    <button type="submit" 
                            name="ajouter_visite" 
                            class="bg-green-500 text-white px-6 py-2 rounded-xl hover:bg-green-600 transition font-semibold">
                        <i class="fas fa-save mr-2"></i>Enregistrer la visite
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Fermer le modal en cliquant à l'extérieur
        document.getElementById('modal_visite')?.addEventListener('click', function(e) {
            if (e.target.id === 'modal_visite') {
                this.classList.add('hidden');
            }
        });

        // Fermer le modal avec la touche Échap
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('modal_visite')?.classList.add('hidden');
            }
        });
    </script>
</body>
</html>