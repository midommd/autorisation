<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../auth/login.php');
    exit;
}

// Initialiser les variables de message
$success = '';
$error = '';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Créer une autorisation d'urgence
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['creer_urgence'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Token de sécurité invalide";
    } else {
        $user_id = intval($_POST['user_id']);
        $motif_urgence = sanitize($_POST['motif_urgence']);
        $justificatif = sanitize($_POST['justificatif']);
        $date_depart = sanitize($_POST['date_depart']);
        $date_retour = sanitize($_POST['date_retour']);
        
        // Validation
        if (empty($user_id) || empty($motif_urgence)) {
            $_SESSION['error'] = "Veuillez remplir tous les champs obligatoires";
        } else {
            // Récupérer les infos de l'utilisateur
            $user_sql = "SELECT * FROM users WHERE id = ? AND status = 'active'";
            $user_stmt = $pdo->prepare($user_sql);
            $user_stmt->execute([$user_id]);
            $user = $user_stmt->fetch();
            
            if ($user) {
                $sql = "INSERT INTO autorisations (
                    user_id, nom, prenom, telephone, parent_telephone, chambre,
                    date_demande, date_depart, date_retour, status, type_autorisation,
                    motif_urgence, justificatif_urgence, urgence_approuvee_par, date_urgence
                ) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, 'approved', 'urgence', ?, ?, ?, NOW())";
                
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([
                    $user_id, $user['nom'], $user['prenom'], $user['telephone'], 
                    $user['parent_telephone'], $user['chambre'], $date_depart, $date_retour,
                    $motif_urgence, $justificatif, $_SESSION['user_id']
                ])) {
                    $_SESSION['success'] = "Autorisation d'urgence créée avec succès";
                    log_activity($_SESSION['user_id'], "Autorisation d'urgence créée pour " . $user['prenom'] . " " . $user['nom'], 'URGENCE_CREATED');
                    
                    // Regenerate token after successful form submission
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    
                    // REDIRECT AFTER POST
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    $_SESSION['error'] = "Erreur lors de la création de l'autorisation d'urgence";
                }
            } else {
                $_SESSION['error'] = "Utilisateur non trouvé ou inactif";
            }
        }
    }
    
    // Si on arrive ici, il y a eu une erreur - rediriger aussi
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get messages from session
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

// Clear session messages after displaying them
unset($_SESSION['success'], $_SESSION['error']);

// Recherche d'utilisateurs pour l'autocomplétion
$search_term = sanitize($_GET['search_user'] ?? '');
$users = [];
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

// Récupérer les autorisations d'urgence
$urgences_sql = "SELECT a.*, u.nom, u.prenom, u2.prenom as admin_prenom, u2.nom as admin_nom 
                 FROM autorisations a 
                 JOIN users u ON a.user_id = u.id 
                 LEFT JOIN users u2 ON a.urgence_approuvee_par = u2.id 
                 WHERE a.type_autorisation = 'urgence' 
                 ORDER BY a.created_at DESC";
$autorisations_urgence = $pdo->query($urgences_sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autorisations d'Urgence - CMC Tamsna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg { background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%); }
        .glass-effect { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .urgence-card { border-left: 4px solid #ef4444; }
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
                        <h1 class="text-white font-bold text-xl">Autorisations d'Urgence</h1>
                        <p class="text-gray-400 text-sm">Gestion des sorties exceptionnelles</p>
                    </div>
                </div>
                <nav class="flex space-x-2">
                    <a href="gestion_autorisations.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Accueil</a>
                    <a href="autorisations_normales.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Normales</a>
                    <a href="autorisations_urgence.php" class="bg-red-500/20 text-white px-4 py-2 rounded-xl">Urgences</a>
                    <a href="cas_medicaux.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Médicaux</a>
                    <a href="statistiques_medicales.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Statistiques</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-6 py-8">
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

        <!-- Formulaire de création d'urgence -->
        <div class="glass-effect rounded-2xl p-6 mb-8">
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-plus-circle mr-2"></i>Créer une Autorisation d'Urgence
            </h2>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <!-- Recherche d'utilisateur -->
                <div>
                    <label class="block text-white font-semibold mb-3">
                        <i class="fas fa-search mr-2"></i>Rechercher un Stagiaire *
                    </label>
                    <div class="flex space-x-4">
                        <div class="flex-1">
                            <input type="text" 
                                   id="search_user" 
                                   name="search_user" 
                                   value="<?php echo htmlspecialchars($search_term); ?>"
                                   placeholder="Nom, prénom ou email du stagiaire..."
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500/50">
                            <div id="user_results" class="mt-2 bg-white/10 rounded-xl max-h-60 overflow-y-auto hidden">
                                <!-- Résultats de recherche -->
                            </div>
                        </div>
                        <button type="button" 
                                onclick="searchUsers()" 
                                class="bg-red-500 text-white px-6 py-3 rounded-xl hover:bg-red-600 transition font-semibold">
                            <i class="fas fa-search mr-2"></i>Rechercher
                        </button>
                    </div>
                    <input type="hidden" name="user_id" id="selected_user_id" required>
                    <div id="selected_user_info" class="mt-3 hidden"></div>
                </div>

                <!-- Motif de l'urgence -->
                <div>
                    <label class="block text-white font-semibold mb-3">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Motif de l'Urgence *
                    </label>
                    <select name="motif_urgence" required class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-red-500/50">
                        <option value="">Sélectionnez un motif</option>
                        <option value="maladie">Maladie / Problème médical</option>
                        <option value="deces_famille">Décès dans la famille</option>
                        <option value="urgence_familiale">Urgence familiale</option>
                        <option value="probleme_sante_grave">Problème de santé grave</option>
                        <option value="autre">Autre situation exceptionnelle</option>
                    </select>
                </div>

                <!-- Justificatif -->
                <div>
                    <label class="block text-white font-semibold mb-3">
                        <i class="fas fa-file-alt mr-2"></i>Justificatif / Détails *
                    </label>
                    <textarea name="justificatif" 
                              required 
                              rows="4" 
                              placeholder="Décrivez en détail la situation d'urgence..."
                              class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500/50"></textarea>
                </div>

                <!-- Dates -->
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-white font-semibold mb-3">
                            <i class="fas fa-calendar-day mr-2"></i>Date de Départ *
                        </label>
                        <input type="datetime-local" 
                               name="date_depart" 
                               required 
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-red-500/50">
                    </div>
                    <div>
                        <label class="block text-white font-semibold mb-3">
                            <i class="fas fa-calendar-check mr-2"></i>Date de Retour Prévue *
                        </label>
                        <input type="datetime-local" 
                               name="date_retour" 
                               required 
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-red-500/50">
                    </div>
                </div>

                <!-- Informations de sécurité -->
                <div class="bg-red-500/20 rounded-xl p-4 border border-red-500/30">
                    <h4 class="text-white font-semibold mb-2 flex items-center">
                        <i class="fas fa-shield-alt mr-2"></i>Procédure d'Urgence
                    </h4>
                    <ul class="text-red-200 text-sm space-y-1">
                        <li>• Cette autorisation est immédiatement approuvée</li>
                        <li>• Le stagiaire sera notifié automatiquement</li>
                        <li>• Toutes les actions sont journalisées pour audit</li>
                        <li>• Réservé aux situations exceptionnelles validées</li>
                    </ul>
                </div>

                <button type="submit" 
                        name="creer_urgence" 
                        class="w-full bg-red-500 text-white py-4 px-6 rounded-xl font-bold hover:bg-red-600 transform hover:scale-105 transition duration-300 shadow-lg text-lg">
                    <i class="fas fa-bolt mr-2"></i>Créer l'Autorisation d'Urgence
                </button>
            </form>
        </div>

        <!-- Liste des autorisations d'urgence -->
        <div class="glass-effect rounded-2xl p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-white">
                    <i class="fas fa-history mr-2"></i>
                    Historique des Autorisations d'Urgence
                </h2>
                <span class="text-gray-400">
                    <?php echo count($autorisations_urgence); ?> autorisation(s)
                </span>
            </div>

            <?php if (empty($autorisations_urgence)): ?>
                <div class="text-center py-12 text-gray-400">
                    <i class="fas fa-bolt text-4xl mb-4"></i>
                    <p>Aucune autorisation d'urgence créée</p>
                    <p class="text-sm mt-2">Les autorisations d'urgence apparaîtront ici</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($autorisations_urgence as $auth): ?>
                        <div class="bg-white/5 rounded-2xl p-6 border border-white/10 hover:bg-white/10 transition urgence-card">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-white font-bold text-xl">
                                        <?php echo htmlspecialchars($auth['prenom'] . ' ' . $auth['nom']); ?>
                                    </h3>
                                    <p class="text-gray-400">
                                        <i class="fas fa-door-closed mr-1"></i>
                                        Chambre <?php echo htmlspecialchars($auth['chambre']); ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="bg-red-500/30 text-red-300 px-3 py-1 rounded-full text-sm font-medium">
                                        <i class="fas fa-bolt mr-1"></i>Urgence
                                    </span>
                                    <p class="text-gray-400 text-sm mt-1">
                                        <?php echo date('d/m/Y H:i', strtotime($auth['date_urgence'])); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="grid md:grid-cols-2 gap-6 mb-4">
                                <div>
                                    <h4 class="text-white font-semibold mb-2">Détails de l'urgence</h4>
                                    <p class="text-gray-300 mb-2">
                                        <strong>Motif:</strong> 
                                        <?php echo match($auth['motif_urgence']) {
                                            'maladie' => 'Maladie/Problème médical',
                                            'deces_famille' => 'Décès dans la famille',
                                            'urgence_familiale' => 'Urgence familiale',
                                            'probleme_sante_grave' => 'Problème de santé grave',
                                            'autre' => 'Autre situation exceptionnelle',
                                            default => $auth['motif_urgence']
                                        }; ?>
                                    </p>
                                    <p class="text-gray-300">
                                        <strong>Justificatif:</strong><br>
                                        <?php echo htmlspecialchars($auth['justificatif_urgence']); ?>
                                    </p>
                                </div>
                                <div>
                                    <h4 class="text-white font-semibold mb-2">Période d'autorisation</h4>
                                    <p class="text-gray-300">
                                        <strong>Départ:</strong> 
                                        <?php echo date('d/m/Y H:i', strtotime($auth['date_depart'])); ?>
                                    </p>
                                    <p class="text-gray-300">
                                        <strong>Retour:</strong> 
                                        <?php echo date('d/m/Y H:i', strtotime($auth['date_retour'])); ?>
                                    </p>
                                    <p class="text-gray-300">
                                        <strong>Approuvé par:</strong> 
                                        <?php echo htmlspecialchars($auth['admin_prenom'] . ' ' . $auth['admin_nom']); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="flex justify-between items-center pt-4 border-t border-white/10">
                                <div class="text-sm text-gray-400">
                                    <i class="fas fa-phone mr-1"></i>
                                    <?php echo htmlspecialchars($auth['telephone']); ?>
                                </div>
                                <div class="flex space-x-2">
                                    <?php if ($auth['status'] === 'approved' && !$auth['pointage']): ?>
                                        <span class="bg-yellow-500/30 text-yellow-300 px-3 py-1 rounded-full text-sm">
                                            <i class="fas fa-clock mr-1"></i>En cours
                                        </span>
                                    <?php elseif ($auth['pointage']): ?>
                                        <span class="bg-green-500/30 text-green-300 px-3 py-1 rounded-full text-sm">
                                            <i class="fas fa-check mr-1"></i>Terminée
                                        </span>
                                    <?php endif; ?>
                                    
                                    <a href="print_autorisation.php?id=<?php echo $auth['id']; ?>" 
                                       target="_blank"
                                       class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition text-sm">
                                        <i class="fas fa-print mr-1"></i>Imprimer
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
    <script>
        function searchUsers() {
            const searchTerm = document.getElementById('search_user').value;
            if (searchTerm.length < 2) {
                alert('Veuillez saisir au moins 2 caractères');
                return;
            }

            fetch(`?search_user=${encodeURIComponent(searchTerm)}`)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const results = doc.querySelectorAll('.user-result');
                    
                    const resultsContainer = document.getElementById('user_results');
                    resultsContainer.innerHTML = '';
                    
                    if (results.length > 0) {
                        results.forEach(result => {
                            resultsContainer.appendChild(result.cloneNode(true));
                        });
                        resultsContainer.classList.remove('hidden');
                    } else {
                        resultsContainer.innerHTML = '<div class="p-4 text-gray-400 text-center">Aucun utilisateur trouvé</div>';
                        resultsContainer.classList.remove('hidden');
                    }
                });
        }

        function selectUser(userId, userInfo) {
            document.getElementById('selected_user_id').value = userId;
            document.getElementById('selected_user_info').innerHTML = userInfo;
            document.getElementById('selected_user_info').classList.remove('hidden');
            document.getElementById('user_results').classList.add('hidden');
            document.getElementById('search_user').value = '';
        }

        // Fermer les résultats en cliquant ailleurs
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#search_user') && !e.target.closest('#user_results')) {
                document.getElementById('user_results').classList.add('hidden');
            }
        });
    </script>
</body>
</html>

<?php
// Afficher les résultats de recherche
if (!empty($users)) {
    echo '<div class="user-results">';
    foreach ($users as $user) {
        $userInfo = "
            <div class='p-3 border-b border-white/10 hover:bg-white/10 cursor-pointer user-result'
                 onclick='selectUser({$user['id']}, `{$user['prenom']} {$user['nom']} - Chambre {$user['chambre']}`)'>
                <div class='font-semibold text-white'>{$user['prenom']} {$user['nom']}</div>
                <div class='text-gray-400 text-sm'>Chambre {$user['chambre']} • {$user['email']}</div>
            </div>
        ";
        echo $userInfo;
    }
    echo '</div>';
}
?>