<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if ($auth->isLoggedIn()) {
    header('Location: ../dashboard/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>En attente - CMC Tamsna</title>
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
        .pulse-slow {
            animation: pulse 3s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full text-center">
        <div class="glass-effect rounded-3xl p-8 shadow-2xl pulse-slow">
            <div class="w-24 h-24 bg-yellow-500/20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-clock text-yellow-400 text-3xl"></i>
            </div>
            
            <h1 class="text-3xl font-bold text-white mb-4">En attente d'approbation</h1>
            
            <p class="text-blue-200 text-lg mb-6 leading-relaxed">
                Votre compte est en cours de vérification par l'administration du CMC Tamsna. 
                Vous recevrez une notification par email dès que votre compte sera activé.
            </p>
            
            <div class="bg-blue-500/20 rounded-xl p-4 border border-blue-500/30 mb-6">
                <h3 class="text-white font-semibold mb-2 flex items-center justify-center">
                    <i class="fas fa-info-circle mr-2"></i>Prochaines étapes
                </h3>
                <ul class="text-blue-200 text-sm space-y-1 text-left">
                    <li>• Vérification de vos informations par l'administration</li>
                    <li>• Activation de votre compte sous 24-48 heures</li>
                    <li>• Notification par email de confirmation</li>
                </ul>
            </div>
            
            <div class="flex space-x-4 justify-center">
                <a href="login.php" class="bg-white text-blue-600 px-6 py-3 rounded-xl font-semibold hover:bg-blue-50 transition">
                    <i class="fas fa-sign-in-alt mr-2"></i>Se connecter
                </a>
                <a href="../index.php" class="glass-effect text-white px-6 py-3 rounded-xl font-semibold hover:bg-white/20 transition border border-white/20">
                    <i class="fas fa-home mr-2"></i>Accueil
                </a>
            </div>
        </div>
        
        <div class="glass-effect rounded-2xl p-4 mt-6">
            <p class="text-blue-200 text-sm">
                Questions? Contactez l'administration au 
                <span class="text-white font-semibold">+212 5 XX XX XX XX</span>
            </p>
        </div>
    </div>
</body>
</html>