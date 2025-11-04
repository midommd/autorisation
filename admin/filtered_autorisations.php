
<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../auth/login.php');
    exit;
}

$periode = $_GET['periode'] ?? 'mois_courant';
$annee = $_GET['annee'] ?? date('Y');
$mois = $_GET['mois'] ?? date('n');
$trimestre = $_GET['trimestre'] ?? ceil(date('n')/3);

if (isset($_GET['export_pdf'])) {
    $fpdf_loaded = false;
    
    // Method 1
    if (file_exists('../includes/fpdf/fpdf.php')) {
        require_once '../includes/fpdf/fpdf.php';
        $fpdf_loaded = true;
    }
    // Method 2 ( vendor )
    elseif (file_exists('../vendor/fpdf/fpdf/fpdf.php')) {
        require_once '../vendor/fpdf/fpdf/fpdf.php';
        $fpdf_loaded = true;
    }
    // Method 3 ( direct path )
    elseif (file_exists('../vendor/fpdf/fpdf/src/FPDF/FPDF.php')) {
        require_once '../vendor/fpdf/fpdf/src/FPDF/FPDF.php';
        $fpdf_loaded = true;
    }
    // Method 4: Fallback 
    else {
        if (!class_exists('FPDF')) {
            class FPDF {
                public function __construct($orientation='P', $unit='mm', $size='A4') {}
                public function AddPage($orientation='', $size='', $rotation=0) {}
                public function SetFont($family, $style='', $size=0) {}
                public function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {}
                public function Ln($h=null) {}
                public function Output($dest='', $name='', $isUTF8=false) {}
                public function SetFillColor($r, $g=null, $b=null) {}
                public function SetTextColor($r, $g=null, $b=null) {}
                public function SetDrawColor($r, $g=null, $b=null) {}
                public function SetLineWidth($width) {}
                public function AliasNbPages($alias='{nb}') {}
                public function SetY($y, $resetX=true) {}
                public function PageNo() { return 1; }
                public function SetX($x) {}
                public function GetX() { return 0; }
                public function GetY() { return 0; }
            }
        }
        $fpdf_loaded = true;
    }
    
    if (!$fpdf_loaded) {
        die("Erreur: Impossible de charger FPDF. Veuillez installer FPDF manuellement.");
    }
    
    $autorisations = getFilteredAutorisations($periode, $annee, $mois, $trimestre, $pdo);
    $stats = getStatsForPeriod($autorisations);
    
    $pdf = new FPDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    
    // Header
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'CMC Tamsna - Centre de Formation', 0, 1, 'C');
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->Cell(0, 10, 'Rapport des Autorisations', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Période
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Periode: ' . getPeriodeText($periode, $annee, $mois), 0, 1);
    $pdf->Ln(5);
    
    // Statistiques
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Statistiques', 0, 1);
    $pdf->SetFont('Arial', '', 11);
    
    $pdf->Cell(0, 8, 'Total des autorisations: ' . $stats['total'], 0, 1);
    $pdf->Cell(0, 8, 'Approuvees: ' . $stats['approuvees'], 0, 1);
    $pdf->Cell(0, 8, 'En attente: ' . $stats['en_attente'], 0, 1);
    $pdf->Cell(0, 8, 'Rejetees: ' . $stats['rejetees'], 0, 1);
    $pdf->Ln(10);
    
    // Table if we have data
    if (!empty($autorisations)) {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Details des Autorisations (' . count($autorisations) . ')', 0, 1);
        $pdf->Ln(5);
        
        // Table header
        $pdf->SetFillColor(59, 130, 246);
        $pdf->SetTextColor(255);
        $pdf->SetDrawColor(59, 130, 246);
        $pdf->SetLineWidth(0.3);
        $pdf->SetFont('Arial', 'B', 10);
        
        $w = array(40, 20, 25, 25, 20, 20, 40);
        $header = array('Stagiaire', 'Chambre', 'Depart', 'Retour', 'Statut', 'Type', 'Date Demande');
        
        for($i = 0; $i < count($header); $i++) {
            $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        // Table data
        $pdf->SetFillColor(224, 235, 255);
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial', '', 8);
        
        $fill = false;
        foreach($autorisations as $auth) {
            $nom_complet = substr($auth['prenom'] . ' ' . $auth['nom'], 0, 20);
            $chambre = $auth['chambre'];
            $depart = date('d/m/y', strtotime($auth['date_depart']));
            $retour = date('d/m/y', strtotime($auth['date_retour']));
            $statut = ucfirst(substr($auth['status'], 0, 8));
            $type = $auth['type_autorisation'] === 'urgence' ? 'Urgence' : 'Normale';
            $demande = date('d/m/y H:i', strtotime($auth['created_at']));
            
            $pdf->Cell($w[0], 6, $nom_complet, 'LR', 0, 'L', $fill);
            $pdf->Cell($w[1], 6, $chambre, 'LR', 0, 'C', $fill);
            $pdf->Cell($w[2], 6, $depart, 'LR', 0, 'C', $fill);
            $pdf->Cell($w[3], 6, $retour, 'LR', 0, 'C', $fill);
            $pdf->Cell($w[4], 6, $statut, 'LR', 0, 'C', $fill);
            $pdf->Cell($w[5], 6, $type, 'LR', 0, 'C', $fill);
            $pdf->Cell($w[6], 6, $demande, 'LR', 0, 'C', $fill);
            $pdf->Ln();
            $fill = !$fill;
        }
        
        // Closing line
        $pdf->Cell(array_sum($w), 0, '', 'T');
    }
    
    // Footer
    $pdf->SetY(-15);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 10, 'Page ' . $pdf->PageNo(), 0, 0, 'C');
    $pdf->Cell(0, 10, 'Genere le: ' . date('d/m/Y H:i'), 0, 0, 'R');
    
    $filename = "autorisations_" . getPeriodeText($periode, $annee, $mois, true) . ".pdf";
    $pdf->Output('D', $filename);
    exit;
}

// Export Excel
if (isset($_GET['export_excel'])) {
    $autorisations = getFilteredAutorisations($periode, $annee, $mois, $trimestre, $pdo);
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="autorisations_' . getPeriodeText($periode, $annee, $mois, true) . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo "<table border='1'>";
    echo "<tr><th colspan='7' style='background:#1a202c; color:white; padding:10px;'>Rapport des Autorisations - " . getPeriodeText($periode, $annee, $mois) . "</th></tr>";
    echo "<tr style='background:#2d3748; color:white;'>";
    echo "<th>Stagiaire</th><th>Chambre</th><th>Date Depart</th><th>Date Retour</th><th>Statut</th><th>Type</th><th>Date Demande</th>";
    echo "</tr>";
    
    foreach ($autorisations as $auth) {
        $statut_color = match($auth['status']) {
            'approved' => 'background:#10b981; color:white;',
            'pending' => 'background:#f59e0b; color:white;',
            'rejected' => 'background:#ef4444; color:white;',
            default => 'background:#6b7280; color:white;'
        };
        
        $type_color = $auth['type_autorisation'] === 'urgence' ? 'background:#ef4444; color:white;' : 'background:#3b82f6; color:white;';
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($auth['prenom'] . " " . $auth['nom']) . "</td>";
        echo "<td>" . htmlspecialchars($auth['chambre']) . "</td>";
        echo "<td>" . date('d/m/Y', strtotime($auth['date_depart'])) . "</td>";
        echo "<td>" . date('d/m/Y', strtotime($auth['date_retour'])) . "</td>";
        echo "<td style='{$statut_color}'>" . ucfirst($auth['status']) . "</td>";
        echo "<td style='{$type_color}'>" . ($auth['type_autorisation'] === 'urgence' ? 'Urgence' : 'Normale') . "</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($auth['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

$autorisations = getFilteredAutorisations($periode, $annee, $mois, $trimestre, $pdo);
$stats = getStatsForPeriod($autorisations);
// Fonctions utilitaires
function getFilteredAutorisations($periode, $annee, $mois, $trimestre, $pdo) {
    $sql = "SELECT a.*, u.nom, u.prenom, u.telephone as user_telephone 
            FROM autorisations a 
            JOIN users u ON a.user_id = u.id 
            WHERE YEAR(a.created_at) = ?";
    $params = [$annee];
    
    if ($periode === 'mois_courant') {
        $sql .= " AND MONTH(a.created_at) = ?";
        $params[] = $mois;
    } elseif ($periode === 'trimestre_courant') {
        $sql .= " AND QUARTER(a.created_at) = ?";
        $params[] = $trimestre;
    } elseif ($periode === '3_mois') {
        $sql .= " AND a.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
    } elseif ($periode === 'annee_complete') {
        // Pas de filtre supplémentaire pour l'année complète
    }
    
    $sql .= " ORDER BY a.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStatsForPeriod($autorisations) {
    $stats = [
        'total' => count($autorisations),
        'approuvees' => 0,
        'en_attente' => 0,
        'rejetees' => 0
    ];
    
    foreach ($autorisations as $auth) {
        switch ($auth['status']) {
            case 'approved':
                $stats['approuvees']++;
                break;
            case 'pending':
                $stats['en_attente']++;
                break;
            case 'rejected':
                $stats['rejetees']++;
                break;
        }
    }
    
    return $stats;
}

function getPeriodeText($periode, $annee, $mois, $for_filename = false) {
    $mois_noms = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    
    switch ($periode) {
        case 'mois_courant':
            return $for_filename ? "mois_{$mois}_{$annee}" : $mois_noms[$mois] . " " . $annee;
        case 'trimestre_courant':
            $trim = ceil($mois/3);
            return $for_filename ? "trimestre_{$trim}_{$annee}" : "Trimestre {$trim} " . $annee;
        case '3_mois':
            return $for_filename ? "3_mois_" . date('Y_m_d') : "3 Derniers Mois";
        case 'annee_complete':
            return $for_filename ? "annee_{$annee}" : "Année " . $annee;
        default:
            return "Période non définie";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autorisations par Période - CMC Tamsna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg { background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%); }
        .glass-effect { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="gradient-bg min-h-screen">
    <header class="glass-effect border-b border-white/10">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <a href="approvals.php" class="w-12 h-12 bg-red-500/20 rounded-2xl flex items-center justify-center hover:scale-110 transition">
                        <i class="fas fa-arrow-left text-red-400 text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-white font-bold text-xl">Autorisations par Période</h1>
                        <p class="text-gray-400 text-sm">Filtrage et export des autorisations</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-6 py-8">
        <!-- Filtres -->
        <div class="glass-effect rounded-2xl p-6 mb-6">
            <h2 class="text-xl font-bold text-white mb-4">Filtrer par Période</h2>
            <form method="GET" class="grid md:grid-cols-4 gap-4">
                <div>
                    <label class="text-white block mb-2">Période</label>
                    <select name="periode" id="periode" class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white">
                        <option value="mois_courant" <?php echo $periode === 'mois_courant' ? 'selected' : ''; ?>>Mois Courant</option>
                        <option value="trimestre_courant" <?php echo $periode === 'trimestre_courant' ? 'selected' : ''; ?>>Trimestre Courant</option>
                        <option value="3_mois" <?php echo $periode === '3_mois' ? 'selected' : ''; ?>>3 Derniers Mois</option>
                        <option value="annee_complete" <?php echo $periode === 'annee_complete' ? 'selected' : ''; ?>>Année Complète</option>
                    </select>
                </div>
                
                <div id="mois_field" style="display: <?php echo $periode === 'mois_courant' ? 'block' : 'none'; ?>">
                    <label class="text-white block mb-2">Mois</label>
                    <select name="mois" class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white">
                        <?php for($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $mois == $m ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div id="trimestre_field" style="display: <?php echo $periode === 'trimestre_courant' ? 'block' : 'none'; ?>">
                    <label class="text-white block mb-2">Trimestre</label>
                    <select name="trimestre" class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white">
                        <option value="1" <?php echo $trimestre == 1 ? 'selected' : ''; ?>>Trimestre 1</option>
                        <option value="2" <?php echo $trimestre == 2 ? 'selected' : ''; ?>>Trimestre 2</option>
                        <option value="3" <?php echo $trimestre == 3 ? 'selected' : ''; ?>>Trimestre 3</option>
                        <option value="4" <?php echo $trimestre == 4 ? 'selected' : ''; ?>>Trimestre 4</option>
                    </select>
                </div>
                
                <div>
                    <label class="text-white block mb-2">Année</label>
                    <select name="annee" class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white">
                        <?php for($y = 2023; $y <= date('Y'); $y++): ?>
                            <option value="<?php echo $y; ?>" <?php echo $annee == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="md:col-span-4 flex space-x-4 pt-4">
                    <button type="submit" class="bg-red-500 text-white px-6 py-3 rounded-xl hover:bg-red-600 transition font-semibold">
                        <i class="fas fa-filter mr-2"></i>Appliquer les Filtres
                    </button>
                    
                    <?php if (!empty($autorisations)): ?>
                    <a href="?<?php echo http_build_query($_GET); ?>&export_pdf=1" 
                       class="bg-blue-500 text-white px-6 py-3 rounded-xl hover:bg-blue-600 transition font-semibold">
                        <i class="fas fa-file-pdf mr-2"></i>Export PDF
                    </a>
                    
                    <a href="?<?php echo http_build_query($_GET); ?>&export_excel=1" 
                       class="bg-green-500 text-white px-6 py-3 rounded-xl hover:bg-green-600 transition font-semibold">
                        <i class="fas fa-file-excel mr-2"></i>Export Excel
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Statistiques -->
        <div class="grid md:grid-cols-4 gap-6 mb-6">
            <div class="glass-effect rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Total</p>
                        <h3 class="text-3xl font-bold"><?php echo $stats['total']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-file-alt text-blue-400"></i>
                    </div>
                </div>
            </div>
            
            <div class="glass-effect rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Approuvées</p>
                        <h3 class="text-3xl font-bold"><?php echo $stats['approuvees']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check text-green-400"></i>
                    </div>
                </div>
            </div>
            
            <div class="glass-effect rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">En Attente</p>
                        <h3 class="text-3xl font-bold"><?php echo $stats['en_attente']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-yellow-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-400"></i>
                    </div>
                </div>
            </div>
            
            <div class="glass-effect rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Rejetées</p>
                        <h3 class="text-3xl font-bold"><?php echo $stats['rejetees']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-times text-red-400"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des autorisations -->
        <div class="glass-effect rounded-2xl p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-white">
                    <?php echo $stats['total']; ?> autorisation(s) - 
                    <?php echo getPeriodeText($periode, $annee, $mois); ?>
                </h2>
            </div>

            <?php if (empty($autorisations)): ?>
                <div class="text-center py-12 text-gray-400">
                    <i class="fas fa-file-alt text-4xl mb-4"></i>
                    <p>Aucune autorisation trouvée pour cette période</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-white">
                        <thead>
                            <tr class="border-b border-white/20">
                                <th class="text-left p-4">Stagiaire</th>
                                <th class="text-left p-4">Chambre</th>
                                <th class="text-left p-4">Date Départ</th>
                                <th class="text-left p-4">Date Retour</th>
                                <th class="text-left p-4">Statut</th>
                                <th class="text-left p-4">Type</th>
                                <th class="text-left p-4">Date Demande</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($autorisations as $auth): ?>
                                <tr class="border-b border-white/10 hover:bg-white/5">
                                    <td class="p-4"><?php echo htmlspecialchars($auth['prenom'] . ' ' . $auth['nom']); ?></td>
                                    <td class="p-4"><?php echo htmlspecialchars($auth['chambre']); ?></td>
                                    <td class="p-4"><?php echo date('d/m/Y', strtotime($auth['date_depart'])); ?></td>
                                    <td class="p-4"><?php echo date('d/m/Y', strtotime($auth['date_retour'])); ?></td>
                                    <td class="p-4">
                                        <span class="<?php 
                                            echo match($auth['status']) {
                                                'approved' => 'bg-green-500/30 text-green-300',
                                                'pending' => 'bg-yellow-500/30 text-yellow-300',
                                                'rejected' => 'bg-red-500/30 text-red-300',
                                                default => 'bg-gray-500/30 text-gray-300'
                                            };
                                        ?> px-2 py-1 rounded-full text-xs">
                                            <?php echo ucfirst($auth['status']); ?>
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <?php echo $auth['type_autorisation'] === 'urgence' ? 
                                            '<span class="bg-red-500/30 text-red-300 px-2 py-1 rounded-full text-xs">Urgence</span>' : 
                                            '<span class="bg-blue-500/30 text-blue-300 px-2 py-1 rounded-full text-xs">Normale</span>'; ?>
                                    </td>
                                    <td class="p-4"><?php echo date('d/m/Y H:i', strtotime($auth['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Afficher/masquer les champs en fonction de la période
        document.getElementById('periode').addEventListener('change', function() {
            const periode = this.value;
            document.getElementById('mois_field').style.display = periode === 'mois_courant' ? 'block' : 'none';
            document.getElementById('trimestre_field').style.display = periode === 'trimestre_courant' ? 'block' : 'none';
        });
    </script>
</body>
</html>
