<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../auth/login.php');
    exit;
}

// Paramètres de période
$periode = $_GET['periode'] ?? 'mois_courant';
$annee = $_GET['annee'] ?? date('Y');

// Statistiques par période
$stats_sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approuvees,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejetees,
        AVG(duree_jours) as duree_moyenne,
        SUM(CASE WHEN pointage = 1 THEN 1 ELSE 0 END) as pointees
    FROM autorisations 
    WHERE annee = ?
";
$urgences_sql = "SELECT 
    COUNT(*) as total_urgences,
    SUM(CASE WHEN a.status = 'approved' THEN 1 ELSE 0 END) as urgences_approuvees
    FROM autorisations a 
    WHERE a.type_autorisation = 'urgence' 
    AND a.annee = ?";
    
if ($periode === 'mois_courant') {
    $stats_sql .= " AND mois = ?";
    $params = [$annee, date('n')];
} elseif ($periode === 'trimestre_courant') {
    $stats_sql .= " AND trimestre = ?";
    $params = [$annee, ceil(date('n')/3)];
} elseif ($periode === '6_mois') {
    $stats_sql .= " AND mois >= ?";
    $params = [$annee, date('n') - 5];
} elseif ($periode === 'annee_complete') {
    $params = [$annee];
}

$stats = $pdo->prepare($stats_sql);
$stats->execute($params);
$statistiques = $stats->fetch(PDO::FETCH_ASSOC);

// Évolution mensuelle
$evolution_sql = "
    SELECT mois, COUNT(*) as total
    FROM autorisations 
    WHERE annee = ? 
    GROUP BY mois 
    ORDER BY mois
";
$evolution = $pdo->prepare($evolution_sql);
$evolution->execute([$annee]);
$evolution_data = $evolution->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques Avancées - CMC Tamsna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 min-h-screen">
    <div class="container mx-auto px-6 py-8">
        <h1 class="text-3xl font-bold text-white mb-8">Statistiques Avancées</h1>
        
        <!-- Filtres -->
        <div class="bg-gray-800 rounded-lg p-6 mb-6">
            <form method="GET" class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="text-white block mb-2">Période</label>
                    <select name="periode" class="w-full p-2 rounded bg-gray-700 text-white">
                        <option value="mois_courant" <?php echo $periode === 'mois_courant' ? 'selected' : ''; ?>>Mois Courant</option>
                        <option value="trimestre_courant" <?php echo $periode === 'trimestre_courant' ? 'selected' : ''; ?>>Trimestre Courant</option>
                        <option value="6_mois" <?php echo $periode === '6_mois' ? 'selected' : ''; ?>>6 Derniers Mois</option>
                        <option value="annee_complete" <?php echo $periode === 'annee_complete' ? 'selected' : ''; ?>>Année Complète</option>
                    </select>
                </div>
                <div>
                    <label class="text-white block mb-2">Année</label>
                    <select name="annee" class="w-full p-2 rounded bg-gray-700 text-white">
                        <?php for($y = 2023; $y <= date('Y'); $y++): ?>
                            <option value="<?php echo $y; ?>" <?php echo $annee == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                        Appliquer
                    </button>
                </div>
            </form>
        </div>

        <!-- Cartes de statistiques -->
        <div class="grid md:grid-cols-4 gap-6 mb-8">
            <div class="bg-blue-500/20 rounded-lg p-6 text-white">
                <div class="text-3xl font-bold"><?php echo $statistiques['total'] ?? 0; ?></div>
                <div class="text-blue-300">Total Autorisations</div>
            </div>
            <div class="bg-green-500/20 rounded-lg p-6 text-white">
                <div class="text-3xl font-bold"><?php echo $statistiques['approuvees'] ?? 0; ?></div>
                <div class="text-green-300">Approuvées</div>
            </div>
            <div class="bg-yellow-500/20 rounded-lg p-6 text-white">
                <div class="text-3xl font-bold"><?php echo round($statistiques['duree_moyenne'] ?? 0, 1); ?>j</div>
                <div class="text-yellow-300">Durée Moyenne</div>
            </div>
            <div class="bg-purple-500/20 rounded-lg p-6 text-white">
                <div class="text-3xl font-bold"><?php echo $statistiques['pointees'] ?? 0; ?></div>
                <div class="text-purple-300">Pointées</div>
            </div>
        </div>

        <!-- Graphiques -->
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="bg-gray-800 rounded-lg p-6">
                <h3 class="text-white text-xl mb-4">Évolution Mensuelle</h3>
                <canvas id="evolutionChart" height="300"></canvas>
            </div>
            <div class="bg-gray-800 rounded-lg p-6">
                <h3 class="text-white text-xl mb-4">Répartition par Statut</h3>
                <canvas id="statusChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Graphique d'évolution
        const evolutionCtx = document.getElementById('evolutionChart').getContext('2d');
        const evolutionChart = new Chart(evolutionCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(fn($m) => "'Mois " . $m['mois'] . "'", $evolution_data)); ?>],
                datasets: [{
                    label: 'Autorisations',
                    data: [<?php echo implode(',', array_column($evolution_data, 'total')); ?>],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { labels: { color: 'white' } }
                },
                scales: {
                    y: { ticks: { color: 'white' }, grid: { color: 'rgba(255,255,255,0.1)' } },
                    x: { ticks: { color: 'white' }, grid: { color: 'rgba(255,255,255,0.1)' } }
                }
            }
        });

        // Graphique des statuts
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Approuvées', 'En Attente', 'Rejetées'],
                datasets: [{
                    data: [
                        <?php echo $statistiques['approuvees'] ?? 0; ?>,
                        <?php echo $statistiques['en_attente'] ?? 0; ?>,
                        <?php echo $statistiques['rejetees'] ?? 0; ?>
                    ],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { labels: { color: 'white' } }
                }
            }
        });
    </script>
</body>
</html>