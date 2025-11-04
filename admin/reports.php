<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../auth/login.php');
    exit;
}

// Statistiques pour les rapports (CORRIGÉ)
$stats_sql = "
    SELECT 
        COUNT(DISTINCT u.id) as total_stagiaires,
        COUNT(DISTINCT a.id) as total_autorisations,
        SUM(CASE WHEN a.status = 'approved' THEN 1 ELSE 0 END) as autorisations_approuvees,
        SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as autorisations_en_attente,
        SUM(CASE WHEN a.status = 'rejected' THEN 1 ELSE 0 END) as autorisations_rejetees,
        SUM(CASE WHEN a.pointage = 1 THEN 1 ELSE 0 END) as autorisations_pointees,
        SUM(CASE WHEN a.status = 'approved' AND a.pointage = 0 AND a.date_retour < CURDATE() THEN 1 ELSE 0 END) as retards
    FROM users u 
    LEFT JOIN autorisations a ON u.id = a.user_id
    WHERE u.role = 'user' AND u.status = 'active'
";
$stats = $pdo->query($stats_sql)->fetch(PDO::FETCH_ASSOC);

// Autorisations par mois (pour le graphique)
$monthly_sql = "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as mois,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approuvees,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejetees
    FROM autorisations 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY mois DESC
    LIMIT 6
";
$monthly_data = $pdo->query($monthly_sql)->fetchAll(PDO::FETCH_ASSOC);

// Top des chambres avec le plus d'autorisations
$top_chambres_sql = "
    SELECT chambre, COUNT(*) as total_autorisations
    FROM autorisations 
    WHERE status = 'approved'
    GROUP BY chambre 
    ORDER BY total_autorisations DESC 
    LIMIT 10
";
$top_chambres = $pdo->query($top_chambres_sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports - CMC Tamsna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .flip-in {
            animation: flipIn 0.8s ease-out;
        }
        @keyframes flipIn {
            from { 
                transform: rotateX(90deg);
                opacity: 0;
            }
            to { 
                transform: rotateX(0);
                opacity: 1;
            }
        }
        .glow {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
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
                        <h1 class="text-white font-bold text-xl">Rapports et Statistiques</h1>
                        <p class="text-gray-400 text-sm">Analyses détaillées du système</p>
                    </div>
                </div>
                
                <nav class="flex space-x-2">
                    <a href="index.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Tableau de Bord</a>
                    <a href="users.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Utilisateurs</a>
                    <a href="approvals.php" class="text-gray-400 hover:bg-white/10 px-4 py-2 rounded-xl transition">Autorisations</a>
                    <a href="reports.php" class="bg-red-500/20 text-white px-4 py-2 rounded-xl">Rapports</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-6 py-8">
        <!-- Statistiques principales -->
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="glass-effect rounded-2xl p-6 text-white flip-in glow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Stagiaires actifs</p>
                        <h3 class="text-3xl font-bold"><?php echo $stats['total_stagiaires']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-users text-blue-400"></i>
                    </div>
                </div>
            </div>
            
            <div class="glass-effect rounded-2xl p-6 text-white flip-in" style="animation-delay: 0.1s;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Autorisations totales</p>
                        <h3 class="text-3xl font-bold"><?php echo $stats['total_autorisations']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-file-alt text-green-400"></i>
                    </div>
                </div>
            </div>
            
            <div class="glass-effect rounded-2xl p-6 text-white flip-in" style="animation-delay: 0.2s;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Taux d'approbation</p>
                        <h3 class="text-3xl font-bold">
                            <?php 
                                $taux = $stats['total_autorisations'] > 0 ? 
                                    round(($stats['autorisations_approuvees'] / $stats['total_autorisations']) * 100, 1) : 0;
                                echo $taux . '%';
                            ?>
                        </h3>
                    </div>
                    <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-pie text-purple-400"></i>
                    </div>
                </div>
            </div>
            
            <div class="glass-effect rounded-2xl p-6 text-white flip-in" style="animation-delay: 0.3s;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Retards actuels</p>
                        <h3 class="text-3xl font-bold"><?php echo $stats['retards']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-400"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Graphique des autorisations -->
            <div class="glass-effect rounded-2xl p-6 flip-in" style="animation-delay: 0.4s;">
                <h2 class="text-xl font-bold text-white mb-6">Autorisations par mois</h2>
                <div class="h-80">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>

            <!-- Répartition des statuts -->
            <div class="glass-effect rounded-2xl p-6 flip-in" style="animation-delay: 0.5s;">
                <h2 class="text-xl font-bold text-white mb-6">Répartition des autorisations</h2>
                <div class="h-80">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top chambres -->
        <div class="glass-effect rounded-2xl p-6 mt-8 flip-in" style="animation-delay: 0.6s;">
            <h2 class="text-xl font-bold text-white mb-6">Top 10 des chambres les plus actives</h2>
            <div class="space-y-4">
                <?php if (empty($top_chambres)): ?>
                    <div class="text-center py-8 text-gray-400">
                        <i class="fas fa-home text-3xl mb-3"></i>
                        <p>Aucune donnée disponible</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($top_chambres as $index => $chambre): ?>
                        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl hover:bg-white/10 transition">
                            <div class="flex items-center space-x-4">
                                <div class="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center">
                                    <span class="text-blue-400 font-bold">#<?php echo $index + 1; ?></span>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold">Chambre <?php echo $chambre['chambre']; ?></h3>
                                    <p class="text-gray-400 text-sm"><?php echo $chambre['total_autorisations']; ?> autorisation(s)</p>
                                </div>
                            </div>
                            <div class="w-32 bg-gray-600 rounded-full h-3">
                                <div class="bg-blue-500 h-3 rounded-full" 
                                     style="width: <?php echo min(($chambre['total_autorisations'] / $top_chambres[0]['total_autorisations']) * 100, 100); ?>%">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Export des données -->
        <div class="glass-effect rounded-2xl p-6 mt-8 flip-in" style="animation-delay: 0.7s;">
            <h2 class="text-xl font-bold text-white mb-6">Export des données</h2>
            <div class="grid md:grid-cols-3 gap-6">
                <a href="#" class="bg-blue-500/20 text-blue-300 p-6 rounded-xl hover:bg-blue-500/30 transition border border-blue-500/30 text-center group">
                    <i class="fas fa-file-excel text-3xl mb-3 group-hover:scale-110 transition"></i>
                    <div class="font-semibold">Export Excel</div>
                    <p class="text-blue-200 text-sm mt-2">Données complètes</p>
                </a>
                <a href="#" class="bg-green-500/20 text-green-300 p-6 rounded-xl hover:bg-green-500/30 transition border border-green-500/30 text-center group">
                    <i class="fas fa-file-pdf text-3xl mb-3 group-hover:scale-110 transition"></i>
                    <div class="font-semibold">Rapport PDF</div>
                    <p class="text-green-200 text-sm mt-2">Synthèse mensuelle</p>
                </a>
                <a href="#" class="bg-purple-500/20 text-purple-300 p-6 rounded-xl hover:bg-purple-500/30 transition border border-purple-500/30 text-center group">
                    <i class="fas fa-chart-bar text-3xl mb-3 group-hover:scale-110 transition"></i>
                    <div class="font-semibold">Statistiques</div>
                    <p class="text-purple-200 text-sm mt-2">Graphiques détaillés</p>
                </a>
            </div>
        </div>
        <div class="glass-effect rounded-2xl p-6 mt-8 flip-in" style="animation-delay: 0.8s;">
            <h2 class="text-xl font-bold text-white mb-6">Rapports des Autorisations par Période</h2>
            <div class="grid md:grid-cols-2 gap-6">
                <a href="filtered_autorisations.php?periode=mois_courant" class="bg-blue-500/20 text-blue-300 p-6 rounded-xl hover:bg-blue-500/30 transition border border-blue-500/30 text-center group">
                    <i class="fas fa-calendar-day text-3xl mb-3 group-hover:scale-110 transition"></i>
                    <div class="font-semibold">Rapport Mensuel</div>
                    <p class="text-blue-200 text-sm mt-2">Autorisations du mois</p>
                </a>
                <a href="filtered_autorisations.php?periode=trimestre_courant" class="bg-green-500/20 text-green-300 p-6 rounded-xl hover:bg-green-500/30 transition border border-green-500/30 text-center group">
                    <i class="fas fa-calendar-week text-3xl mb-3 group-hover:scale-110 transition"></i>
                    <div class="font-semibold">Rapport Trimestriel</div>
                    <p class="text-green-200 text-sm mt-2">Autorisations du trimestre</p>
                </a>
                <a href="filtered_autorisations.php?periode=3_mois" class="bg-purple-500/20 text-purple-300 p-6 rounded-xl hover:bg-purple-500/30 transition border border-purple-500/30 text-center group">
                    <i class="fas fa-chart-line text-3xl mb-3 group-hover:scale-110 transition"></i>
                    <div class="font-semibold">Rapport 3 Mois</div>
                    <p class="text-purple-200 text-sm mt-2">Évolution sur 3 mois</p>
                </a>
                <a href="filtered_autorisations.php?periode=annee_complete" class="bg-orange-500/20 text-orange-300 p-6 rounded-xl hover:bg-orange-500/30 transition border border-orange-500/30 text-center group">
                    <i class="fas fa-calendar-alt text-3xl mb-3 group-hover:scale-110 transition"></i>
                    <div class="font-semibold">Rapport Annuel</div>
                    <p class="text-orange-200 text-sm mt-2">Bilan annuel complet</p>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Graphique des autorisations par mois
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: [<?php 
                    echo implode(', ', array_map(function($item) {
                        $date = DateTime::createFromFormat('Y-m', $item['mois']);
                        return "'" . $date->format('M Y') . "'";
                    }, array_reverse($monthly_data)));
                ?>],
                datasets: [
                    {
                        label: 'Approuvées',
                        data: [<?php echo implode(', ', array_map(function($item) { return $item['approuvees']; }, array_reverse($monthly_data))); ?>],
                        backgroundColor: 'rgba(34, 197, 94, 0.8)',
                        borderColor: 'rgba(34, 197, 94, 1)',
                        borderWidth: 2
                    },
                    {
                        label: 'Rejetées',
                        data: [<?php echo implode(', ', array_map(function($item) { return $item['rejetees']; }, array_reverse($monthly_data))); ?>],
                        backgroundColor: 'rgba(239, 68, 68, 0.8)',
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: 'white'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: 'white'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: 'white'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    }
                }
            }
        });

        // Graphique des statuts
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Approuvées', 'En attente', 'Rejetées'],
                datasets: [{
                    data: [
                        <?php echo $stats['autorisations_approuvees']; ?>,
                        <?php echo $stats['autorisations_en_attente']; ?>,
                        <?php echo $stats['autorisations_rejetees']; ?>
                    ],
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(234, 179, 8, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ],
                    borderColor: [
                        'rgba(34, 197, 94, 1)',
                        'rgba(234, 179, 8, 1)',
                        'rgba(239, 68, 68, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: 'white',
                            padding: 20
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>