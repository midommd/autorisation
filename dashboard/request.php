<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!$auth->isLoggedIn() || $auth->isAdmin()) {
    header('Location: ../auth/login.php');
    exit;
}

$success = '';
$error = '';

// TEMPORAIRE: Permettre les tests tous les jours
$today = new DateTime();
$isThursday = true; // TEMPORAIRE: Autoriser tous les jours pour test

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isThursday) {
    $date_depart = sanitize($_POST['date_depart']);
    $date_retour = sanitize($_POST['date_retour']);
    
    // Validation des dates - TEMPORAIRE: Permettre n'importe quelle date pour test
    $depart = new DateTime($date_depart);
    $retour = new DateTime($date_retour);
    
    if ($retour > $depart) {
        $user_id = $_SESSION['user_id'];
        
        // Récupérer les infos utilisateur
        $user_sql = "SELECT * FROM users WHERE id = ?";
        $user_stmt = $pdo->prepare($user_sql);
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        $sql = "INSERT INTO autorisations (user_id, nom, prenom, telephone, parent_telephone, chambre, date_demande, date_depart, date_retour, status) 
                VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, 'pending')";
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$user_id, $user['nom'], $user['prenom'], $user['telephone'], $user['parent_telephone'], $user['chambre'], $date_depart, $date_retour])) {
            $success = "Votre demande d'autorisation a été soumise avec succès!";
        } else {
            $error = "Erreur lors de la soumission de la demande.";
        }
    } else {
        $error = "La date de retour doit être après la date de départ.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Demande - CMC Tamsna</title>
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
        .bounce-in {
            animation: bounceIn 0.6s ease-out;
        }
        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
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
                        <h1 class="text-white font-bold text-xl">Nouvelle Demande</h1>
                        <p class="text-blue-200 text-sm">Demande d'autorisation de sortie</p>
                    </div>
                </div>
                
                <nav class="flex space-x-4">
                    <a href="index.php" class="text-white hover:bg-white/20 px-4 py-2 rounded-xl transition">Accueil</a>
                    <a href="history.php" class="text-white hover:bg-white/20 px-4 py-2 rounded-xl transition">Historique</a>
                    <a href="../auth/logout.php" class="text-white hover:bg-red-500/20 px-4 py-2 rounded-xl transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-6 py-8 max-w-2xl">
        <div class="glass-effect rounded-2xl p-8 bounce-in">
            <!-- Bannière de test temporaire -->
            <div class="bg-yellow-500/20 border border-yellow-500/50 text-yellow-100 px-6 py-4 rounded-xl mb-6">
                <div class="flex items-center">
                    <i class="fas fa-flask text-2xl mr-4"></i>
                    <div>
                        <h3 class="font-bold text-lg">Mode Test Activé</h3>
                        <p>Les demandes sont autorisées tous les jours pour test. Cette restriction sera rétablie en production.</p>
                    </div>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-500/20 border border-green-500/50 text-green-100 px-6 py-4 rounded-xl mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-2xl mr-4"></i>
                        <div>
                            <h3 class="font-bold text-lg">Succès!</h3>
                            <p><?php echo $success; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-500/20 border border-red-500/50 text-red-100 px-6 py-4 rounded-xl mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-2xl mr-4"></i>
                        <div>
                            <h3 class="font-bold text-lg">Erreur</h3>
                            <p><?php echo $error; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($isThursday): ?>
                <form method="POST" class="space-y-6">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-white font-semibold mb-3">
                                <i class="fas fa-calendar-day mr-2"></i>Date de départ
                            </label>
                            <input type="date" name="date_depart" required 
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition">
                            <p class="text-blue-200 text-sm mt-2">TEMPORAIRE: Toutes dates autorisées</p>
                        </div>
                        
                        <div>
                            <label class="block text-white font-semibold mb-3">
                                <i class="fas fa-calendar-check mr-2"></i>Date de retour
                            </label>
                            <input type="date" name="date_retour" required 
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition">
                            <p class="text-blue-200 text-sm mt-2">Doit être après la date de départ</p>
                        </div>
                    </div>

                    <div class="bg-blue-500/20 rounded-xl p-4 border border-blue-500/30">
                        <h4 class="text-white font-semibold mb-2 flex items-center">
                            <i class="fas fa-info-circle mr-2"></i>Informations importantes
                        </h4>
                        <ul class="text-blue-200 text-sm space-y-1">
                            <li>• Retour maximum avant 19h le jour du retour</li>
                            <li>• Signature obligatoire à l'administration</li>
                            <li>• Présentation de la carte d'identité requise</li>
                            <li>• <strong class="text-yellow-300">MODE TEST: Restrictions désactivées</strong></li>
                        </ul>
                    </div>

                    <button type="submit" 
                            class="w-full bg-white text-blue-600 py-4 px-6 rounded-xl font-bold hover:bg-blue-50 transform hover:scale-105 transition duration-300 shadow-lg text-lg">
                        <i class="fas fa-paper-plane mr-2"></i>Soumettre la demande (TEST)
                    </button>
                </form>
            <?php else: ?>
                <div class="text-center py-8">
                    <div class="w-24 h-24 bg-yellow-500/30 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-clock text-yellow-300 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Revenez jeudi!</h3>
                    <p class="text-blue-200 text-lg">
                        Les demandes d'autorisation sont acceptées uniquement le jeudi pour la semaine suivante.
                    </p>
                    <div class="mt-6 bg-white/10 rounded-xl p-4 inline-block">
                        <p class="text-white font-semibold">
                            Prochain jour de demande: 
                            <span class="text-yellow-300">
                                <?php echo date('d/m/Y', strtotime('next thursday')); ?>
                            </span>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Set min date for return date based on departure date
        const departInput = document.querySelector('input[name="date_depart"]');
        const retourInput = document.querySelector('input[name="date_retour"]');
        
        if (departInput) {
            departInput.addEventListener('change', function() {
                if (this.value) {
                    const minRetour = new Date(this.value);
                    minRetour.setDate(minRetour.getDate() + 1);
                    retourInput.min = minRetour.toISOString().split('T')[0];
                    
                    if (retourInput.value && new Date(retourInput.value) <= new Date(this.value)) {
                        retourInput.value = '';
                    }
                }
            });
        }
    </script>
</body>
</html>