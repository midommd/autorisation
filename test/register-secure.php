<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/security.php'; // NEW SECURITY FILE

// Set security headers
MaxSecurity::setSecurityHeaders();

if ($auth->isLoggedIn()) {
    header('Location: ../dashboard/');
    exit;
}

$error = '';
$success = '';

// Rate limiting by IP
try {
    MaxSecurity::checkRateLimit($_SERVER['REMOTE_ADDR'] . '_register', 3, 3600);
} catch (Exception $e) {
    $error = $e->getMessage();
}

if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !MaxSecurity::validateCSRF($_POST['csrf_token'])) {
        $error = "Token de sécurité invalide. Veuillez rafraîchir la page.";
    } else {
        $nom = MaxSecurity::sanitize($_POST['nom']);
        $prenom = MaxSecurity::sanitize($_POST['prenom']);
        $email = MaxSecurity::sanitize($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $telephone = MaxSecurity::sanitize($_POST['telephone']);
        $chambre = MaxSecurity::sanitize($_POST['chambre']);
        $parent_telephone = MaxSecurity::sanitize($_POST['parent_telephone']);
        
        // Advanced validation
        if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
            $error = "Tous les champs obligatoires doivent être remplis";
        } elseif (!MaxSecurity::validateEmail($email)) {
            $error = "Adresse email invalide";
        } elseif ($password !== $confirm_password) {
            $error = "Les mots de passe ne correspondent pas";
        } elseif (!MaxSecurity::validatePassword($password)) {
            $error = "Le mot de passe doit contenir au moins 12 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial";
        } elseif (strlen($telephone) < 10) {
            $error = "Numéro de téléphone invalide";
        } else {
            if ($auth->register($nom, $prenom, $email, $password, $telephone, $chambre, $parent_telephone)) {
                $success = "Inscription réussie! Votre compte est en attente d'approbation par l'administration.";
                
                // Clear form
                $nom = $prenom = $email = $telephone = $chambre = $parent_telephone = '';
            } else {
                $error = "Erreur lors de l'inscription. L'email existe peut-être déjà.";
            }
        }
    }
}

// Generate CSRF token for form
$csrf_token = MaxSecurity::generateCSRF();
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
        .password-strength {
            transition: all 0.3s ease;
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
                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-500/20 border border-green-500/50 text-green-100 px-4 py-3 rounded-xl mb-6 backdrop-blur-sm slide-in">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-3"></i>
                        <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-user mr-2"></i>Nom *
                        </label>
                        <input type="text" name="nom" required 
                               value="<?php echo isset($nom) ? htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') : ''; ?>"
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition"
                               minlength="2" maxlength="50">
                    </div>
                    
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-user mr-2"></i>Prénom *
                        </label>
                        <input type="text" name="prenom" required 
                               value="<?php echo isset($prenom) ? htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8') : ''; ?>"
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition"
                               minlength="2" maxlength="50">
                    </div>
                </div>

                <div>
                    <label class="block text-white font-medium mb-2">
                        <i class="fas fa-envelope mr-2"></i>Email *
                    </label>
                    <input type="email" name="email" required 
                           value="<?php echo isset($email) ? htmlspecialchars($email, ENT_QUOTES, 'UTF-8') : ''; ?>"
                           class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition"
                           pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-phone mr-2"></i>Téléphone *
                        </label>
                        <input type="tel" name="telephone" required 
                               value="<?php echo isset($telephone) ? htmlspecialchars($telephone, ENT_QUOTES, 'UTF-8') : ''; ?>"
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition"
                               pattern="[0-9]{10,15}" title="Numéro de téléphone valide requis">
                    </div>
                    
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-door-closed mr-2"></i>Numéro de chambre *
                        </label>
                        <input type="text" name="chambre" required 
                               value="<?php echo isset($chambre) ? htmlspecialchars($chambre, ENT_QUOTES, 'UTF-8') : ''; ?>"
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition"
                               maxlength="10">
                    </div>
                </div>

                <div>
                    <label class="block text-white font-medium mb-2">
                        <i class="fas fa-phone-alt mr-2"></i>Téléphone du parent *
                    </label>
                    <input type="tel" name="parent_telephone" required 
                           value="<?php echo isset($parent_telephone) ? htmlspecialchars($parent_telephone, ENT_QUOTES, 'UTF-8') : ''; ?>"
                           class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition"
                           pattern="[0-9]{10,15}" title="Numéro de téléphone valide requis">
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-lock mr-2"></i>Mot de passe *
                        </label>
                        <input type="password" name="password" required 
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition"
                               minlength="12" id="password">
                        <div id="password-strength" class="mt-2 text-sm hidden">
                            <div class="flex items-center space-x-2 text-white">
                                <div id="strength-bar" class="h-2 flex-1 bg-gray-400 rounded-full overflow-hidden">
                                    <div id="strength-progress" class="h-full bg-red-500 w-0 transition-all duration-300"></div>
                                </div>
                                <span id="strength-text" class="text-xs"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-lock mr-2"></i>Confirmer le mot de passe *
                        </label>
                        <input type="password" name="confirm_password" required 
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition"
                               minlength="12" id="confirm_password">
                        <div id="password-match" class="mt-2 text-sm hidden"></div>
                    </div>
                </div>

                <div class="bg-blue-500/20 rounded-xl p-4 border border-blue-500/30">
                    <h4 class="text-white font-semibold mb-2 flex items-center">
                        <i class="fas fa-shield-alt mr-2"></i>Exigences de sécurité
                    </h4>
                    <ul class="text-blue-200 text-sm space-y-1">
                        <li>• Minimum 12 caractères</li>
                        <li>• Au moins une majuscule et une minuscule</li>
                        <li>• Au moins un chiffre et un caractère spécial</li>
                        <li>• Votre compte doit être approuvé par l'administration</li>
                    </ul>
                </div>

                <button type="submit" id="submit-btn"
                        class="w-full bg-white text-blue-600 py-3 px-4 rounded-xl font-bold hover:bg-blue-50 transform transition duration-300 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                    <i class="fas fa-user-plus mr-2"></i>Créer mon compte sécurisé
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
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strength-progress');
        const strengthText = document.getElementById('strength-text');
        const strengthContainer = document.getElementById('password-strength');
        const matchContainer = document.getElementById('password-match');
        const submitBtn = document.getElementById('submit-btn');

        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = [];

            // Length check
            if (password.length >= 12) strength += 25;
            else feedback.push("12 caractères minimum");

            // Upper case check
            if (/[A-Z]/.test(password)) strength += 25;
            else feedback.push("une majuscule");

            // Lower case check
            if (/[a-z]/.test(password)) strength += 25;
            else feedback.push("une minuscule");

            // Number check
            if (/[0-9]/.test(password)) strength += 15;
            else feedback.push("un chiffre");

            // Special char check
            if (/[^A-Za-z0-9]/.test(password)) strength += 10;
            else feedback.push("un caractère spécial");

            // Update UI
            strengthBar.style.width = strength + '%';
            
            if (strength < 50) {
                strengthBar.style.backgroundColor = '#ef4444';
                strengthText.textContent = 'Faible';
                strengthText.className = 'text-xs text-red-300';
            } else if (strength < 80) {
                strengthBar.style.backgroundColor = '#f59e0b';
                strengthText.textContent = 'Moyen';
                strengthText.className = 'text-xs text-yellow-300';
            } else {
                strengthBar.style.backgroundColor = '#10b981';
                strengthText.textContent = 'Fort';
                strengthText.className = 'text-xs text-green-300';
            }

            return { strength, feedback };
        }

        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (!confirm) {
                matchContainer.className = 'mt-2 text-sm hidden';
                return;
            }

            if (password === confirm && password.length >= 12) {
                matchContainer.innerHTML = '<i class="fas fa-check text-green-400 mr-1"></i><span class="text-green-300">Mots de passe identiques</span>';
                matchContainer.className = 'mt-2 text-sm';
                confirmInput.parentElement.classList.add('ring-2', 'ring-green-500/50');
                confirmInput.parentElement.classList.remove('ring-2', 'ring-red-500/50');
            } else {
                matchContainer.innerHTML = '<i class="fas fa-times text-red-400 mr-1"></i><span class="text-red-300">Mots de passe différents</span>';
                matchContainer.className = 'mt-2 text-sm';
                confirmInput.parentElement.classList.add('ring-2', 'ring-red-500/50');
                confirmInput.parentElement.classList.remove('ring-2', 'ring-green-500/50');
            }
        }

        function validateForm() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            const { strength } = checkPasswordStrength(password);
            
            const isStrong = strength >= 80;
            const isMatching = password === confirm && password.length >= 12;
            
            submitBtn.disabled = !isStrong || !isMatching;
        }

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
            if (password) {
                strengthContainer.classList.remove('hidden');
                checkPasswordStrength(password);
            } else {
                strengthContainer.classList.add('hidden');
            }
            
            checkPasswordMatch();
            validateForm();
        });

        confirmInput.addEventListener('input', function() {
            checkPasswordMatch();
            validateForm();
        });

        // Input focus effects
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('ring-2', 'ring-white/30');
            });
            input.addEventListener('blur', () => {
                input.parentElement.classList.remove('ring-2', 'ring-white/30');
            });
        });

        // Form submission protection
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const { strength } = checkPasswordStrength(password);
            
            if (strength < 80) {
                e.preventDefault();
                alert('Veuillez choisir un mot de passe plus fort.');
                return false;
            }
        });
    </script>
</body>
</html>