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

// Rate limiting by IP
try {
    MaxSecurity::checkRateLimit($_SERVER['REMOTE_ADDR'] . '_login', 5, 900);
} catch (Exception $e) {
    $error = $e->getMessage();
}

if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !MaxSecurity::validateCSRF($_POST['csrf_token'])) {
        $error = "Token de sécurité invalide. Veuillez rafraîchir la page.";
    } else {
        $email = MaxSecurity::sanitize($_POST['email']);
        $password = $_POST['password'];
        
        // Advanced validation
        if (empty($email) || empty($password)) {
            $error = "Email et mot de passe requis";
        } elseif (!MaxSecurity::validateEmail($email)) {
            $error = "Adresse email invalide";
        } else {
            if ($auth->login($email, $password)) {
                // Secure the session
                MaxSecurity::secureSession();
                
                if ($auth->isAdmin()) {
                    header('Location: ../admin/');
                } else {
                    header('Location: ../dashboard/');
                }
                exit;
            } else {
                $error = "Email ou mot de passe incorrect, ou compte non approuvé";
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
    <title>Connexion - CMC Tamsna</title>
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
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Animated Background Elements -->
        <div class="absolute top-10 left-10 w-20 h-20 bg-white/10 rounded-full floating"></div>
        <div class="absolute bottom-10 right-10 w-16 h-16 bg-white/10 rounded-full floating" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/4 w-12 h-12 bg-white/10 rounded-full floating" style="animation-delay: 2s;"></div>
        
        <div class="glass-effect rounded-3xl p-8 shadow-2xl">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <i class="fas fa-shield-alt text-blue-600 text-3xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-white mb-2">Connexion Sécurisée</h2>
                <p class="text-blue-100">Accédez à votre espace personnel</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-500/20 border border-red-500/50 text-red-100 px-4 py-3 rounded-xl mb-6 backdrop-blur-sm">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-3"></i>
                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div>
                    <label class="block text-white font-medium mb-2">
                        <i class="fas fa-envelope mr-2"></i>Email
                    </label>
                    <input type="email" name="email" required 
                           value="<?php echo isset($email) ? htmlspecialchars($email, ENT_QUOTES, 'UTF-8') : ''; ?>"
                           class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block text-white font-medium mb-2">
                        <i class="fas fa-key mr-2"></i>Mot de passe
                    </label>
                    <input type="password" name="password" required 
                           class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition">
                </div>

                <button type="submit" 
                        class="w-full bg-white text-blue-600 py-3 px-4 rounded-xl font-bold hover:bg-blue-50 transform hover:scale-105 transition duration-300 shadow-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i>Se Connecter
                </button>
            </form>

            <div class="text-center mt-6 pt-6 border-t border-white/20">
                <p class="text-blue-200">
                    Pas encore de compte? 
                    <a href="register.php" class="text-white font-semibold hover:underline ml-1">
                        S'inscrire maintenant
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

        // Auto-hide error messages after 5 seconds
        setTimeout(() => {
            const errorDiv = document.querySelector('.bg-red-500\\/20');
            if (errorDiv) {
                errorDiv.style.opacity = '0';
                errorDiv.style.transition = 'opacity 0.5s ease';
                setTimeout(() => errorDiv.remove(), 500);
            }
        }, 5000);
    </script>
</body>
</html>