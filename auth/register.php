<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if ($auth->isLoggedIn()) {
    header('Location: ../dashboard/');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = sanitize($_POST['nom']);
    $prenom = sanitize($_POST['prenom']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $telephone = sanitize($_POST['telephone']);
    $chambre = sanitize($_POST['chambre']);
    $parent_telephone = sanitize($_POST['parent_telephone']);
    
    if ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères";
    } else {
        if ($auth->register($nom, $prenom, $email, $password, $telephone, $chambre, $parent_telephone)) {
            $success = "Inscription réussie! Votre compte est en attente d'approbation par l'administration.";
        } else {
            $error = "Erreur lors de l'inscription. L'email existe peut-être déjà.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - CMC Tamsna</title>
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
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        @keyframes floating {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .slide-in {
            animation: slideIn 0.8s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateX(-100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <!-- Animated Background Elements -->
        <div class="absolute top-10 left-10 w-20 h-20 bg-white/10 rounded-full floating"></div>
        <div class="absolute bottom-10 right-10 w-16 h-16 bg-white/10 rounded-full floating" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/4 w-12 h-12 bg-white/10 rounded-full floating" style="animation-delay: 2s;"></div>
        <div class="absolute bottom-1/3 right-1/4 w-14 h-14 bg-white/10 rounded-full floating" style="animation-delay: 1.5s;"></div>
        
        <div class="glass-effect rounded-3xl p-8 shadow-2xl">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <i class="fas fa-user-plus text-blue-600 text-3xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-white mb-2">Créer un compte</h2>
                <p class="text-blue-100">Rejoignez la communauté CMC Tamsna</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-500/20 border border-red-500/50 text-red-100 px-4 py-3 rounded-xl mb-6 backdrop-blur-sm slide-in">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-3"></i>
                        <?php echo $error; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-500/20 border border-green-500/50 text-green-100 px-4 py-3 rounded-xl mb-6 backdrop-blur-sm slide-in">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-3"></i>
                        <?php echo $success; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-user mr-2"></i>Nom
                        </label>
                        <input type="text" name="nom" required 
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition">
                    </div>
                    
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-user mr-2"></i>Prénom
                        </label>
                        <input type="text" name="prenom" required 
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition">
                    </div>
                </div>

                <div>
                    <label class="block text-white font-medium mb-2">
                        <i class="fas fa-envelope mr-2"></i>Email
                    </label>
                    <input type="email" name="email" required 
                           class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition">
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-phone mr-2"></i>Téléphone
                        </label>
                        <input type="tel" name="telephone" required 
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition">
                    </div>
                    
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-door-closed mr-2"></i>Numéro de chambre
                        </label>
                        <input type="text" name="chambre" required 
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition">
                    </div>
                </div>

                <div>
                    <label class="block text-white font-medium mb-2">
                        <i class="fas fa-phone-alt mr-2"></i>Téléphone du parent
                    </label>
                    <input type="tel" name="parent_telephone" required 
                           class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition">
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-lock mr-2"></i>Mot de passe
                        </label>
                        <input type="password" name="password" required 
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition">
                    </div>
                    
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-lock mr-2"></i>Confirmer le mot de passe
                        </label>
                        <input type="password" name="confirm_password" required 
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition">
                    </div>
                </div>

                <div class="bg-blue-500/20 rounded-xl p-4 border border-blue-500/30">
                    <h4 class="text-white font-semibold mb-2 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i>Informations importantes
                    </h4>
                    <ul class="text-blue-200 text-sm space-y-1">
                        <li>• Votre compte doit être approuvé par l'administration</li>
                        <li>• Vous recevrez une notification par email</li>
                        <li>• Les demandes d'autorisation se font uniquement le jeudi</li>
                    </ul>
                </div>

                <button type="submit" 
                        class="w-full bg-white text-blue-600 py-3 px-4 rounded-xl font-bold hover:bg-blue-50 transform hover:scale-105 transition duration-300 shadow-lg">
                    <i class="fas fa-user-plus mr-2"></i>Créer mon compte
                </button>
            </form>

            <div class="text-center mt-6 pt-6 border-t border-white/20">
                <p class="text-blue-200">
                    Déjà un compte? 
                    <a href="login.php" class="text-white font-semibold hover:underline ml-1">
                        Se connecter
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Add input focus effects
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('ring-2', 'ring-white/30');
            });
            input.addEventListener('blur', () => {
                input.parentElement.classList.remove('ring-2', 'ring-white/30');
            });
        });

        // Password strength indicator
        const passwordInput = document.querySelector('input[name="password"]');
        const confirmInput = document.querySelector('input[name="confirm_password"]');
        
        function checkPasswordMatch() {
            if (passwordInput.value && confirmInput.value) {
                if (passwordInput.value !== confirmInput.value) {
                    confirmInput.parentElement.classList.add('ring-2', 'ring-red-500/50');
                } else {
                    confirmInput.parentElement.classList.remove('ring-2', 'ring-red-500/50');
                    confirmInput.parentElement.classList.add('ring-2', 'ring-green-500/50');
                }
            }
        }
        
        passwordInput.addEventListener('input', checkPasswordMatch);
        confirmInput.addEventListener('input', checkPasswordMatch);
    </script>
</body>
</html>