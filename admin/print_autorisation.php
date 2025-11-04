<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../auth/login.php');
    exit;
}

$autorisation_id = intval($_GET['id'] ?? 0);

if ($autorisation_id > 0) {
    // Récupérer l'autorisation
    $sql = "SELECT a.*, u.nom, u.prenom, u.telephone, u.parent_telephone, u.chambre, 
                   COALESCE(u.filiere, '') as filiere, 
                   COALESCE(u.groupe, '') as groupe
            FROM autorisations a 
            JOIN users u ON a.user_id = u.id 
            WHERE a.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$autorisation_id]);
    $autorisation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$autorisation) {
        die("
            <div style='padding: 20px; text-align: center;'>
                <h2>Autorisation non trouvée</h2>
                <p>L'autorisation demandée n'existe pas.</p>
                <button onclick='window.close()'>Fermer</button>
            </div>
        ");
    }
    
    // Vérifier que l'autorisation est approuvée
    if ($autorisation['status'] !== 'approved') {
        die("
            <div style='padding: 20px; text-align: center;'>
                <h2>Autorisation non approuvée</h2>
                <p>Cette autorisation n'est pas approuvée et ne peut pas être imprimée.</p>
                <button onclick='window.close()'>Fermer</button>
            </div>
        ");
    }
    
    // Journalisation de l'impression par l'admin
    log_activity($_SESSION['user_id'], "Impression autorisation #$autorisation_id pour " . $autorisation['prenom'] . " " . $autorisation['nom'], 'ADMIN_PRINT');
    
    // Récupérer le template d'impression
    $template_sql = "SELECT contenu FROM print_templates WHERE est_actif = TRUE LIMIT 1";
    $template = $pdo->query($template_sql)->fetch(PDO::FETCH_ASSOC);
    $contenu_template = $template['contenu'] ?? get_default_template($autorisation);
    
    // Calculer la durée
    $date_depart = new DateTime($autorisation['date_depart']);
    $date_retour = new DateTime($autorisation['date_retour']);
    $duree = $date_depart->diff($date_retour)->days;
    
    // Fonction pour formater les champs
    function formatField($value) {
        $trimmed = trim($value);
        return [
            'value' => !empty($trimmed) ? htmlspecialchars($trimmed) : '',
            'class' => empty($trimmed) ? 'empty' : ''
        ];
    }
    
    // Formater chaque champ
    $nomPrenom = formatField($autorisation['prenom'] . ' ' . $autorisation['nom']);
    $filiere = formatField($autorisation['filiere']);
    $groupe = formatField($autorisation['groupe']);
    $telephone = formatField($autorisation['telephone']);
    $telephoneTuteur = formatField($autorisation['parent_telephone']);
    $chambre = formatField($autorisation['chambre']);
    $motif = formatField($autorisation['motif_urgence'] ?? '');
    $dateGeneration = formatField(date('d/m/Y'));
    
    // Variables de remplacement
    $replacements = [
        '{BASE_URL}' => BASE_URL,
        '{NOM_PRENOM}' => $nomPrenom['value'],
        '{NOM_PRENOM_CLASS}' => $nomPrenom['class'],
        '{FILIERE}' => $filiere['value'],
        '{FILIERE_CLASS}' => $filiere['class'],
        '{GROUPE}' => $groupe['value'],
        '{GROUPE_CLASS}' => $groupe['class'],
        '{TELEPHONE}' => $telephone['value'],
        '{TELEPHONE_CLASS}' => $telephone['class'],
        '{TELEPHONE_TUTEUR}' => $telephoneTuteur['value'],
        '{TELEPHONE_TUTEUR_CLASS}' => $telephoneTuteur['class'],
        '{CHAMBRE}' => $chambre['value'],
        '{CHAMBRE_CLASS}' => $chambre['class'],
        '{DATE_GENERATION}' => $dateGeneration['value'],
        '{DATE_GENERATION_CLASS}' => $dateGeneration['class'],
        '{MOTIF}' => $motif['value'],
        '{MOTIF_CLASS}' => $motif['class'],
        '{DATE_DEPART}' => date('d/m/Y', strtotime($autorisation['date_depart'])),
        '{DATE_RETOUR}' => date('d/m/Y', strtotime($autorisation['date_retour'])),
        '{DUREE}' => $duree
    ];
    
    $contenu_final = str_replace(array_keys($replacements), array_values($replacements), $contenu_template);
    
} else {
    die("
        <div style='padding: 20px; text-align: center;'>
            <h2>ID d'autorisation invalide</h2>
            <button onclick='window.close()'>Fermer</button>
        </div>
    ");
}

// Template par défaut
function get_default_template($autorisation = null) {
    return '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande d\'autorisation de sortie OFPPT</title>
    <style>
        /* --- BASE STYLES & CONTAINER --- */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #000;
        }

        .container {
            width: 1100px;
            min-height: 650px;
            margin: 50px auto;
            padding: 50px 80px;
            background-color: #fff;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            position: relative;
            box-sizing: border-box;
            font-size: 15px;
            line-height: 1.5;
        }
        
        /* Field content styling - NO DOTS when content exists */
        .field-content {
            display: inline-block;
            margin: 0 5px;
            min-width: 50px;
            border-bottom: 1px solid transparent;
        }
        
        .field-content.empty {
            border-bottom: 1px dotted #000;
            min-height: 16px;
        }

        /* --- HEADER SECTION (LOGOS) --- */
        .header {
            position: relative;
            height: 70px;
            margin-bottom: 40px;
        }

        /* Left Logo and Text (OFPPT) Container */
        .logo-left {
            position: absolute;
            left: 0;
            top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .ofppt-logo-img {
            width: 65px;
            height: auto;
            display: block;
        }

        .ofppt-text-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .vertical-separator {
            border-left: 1px solid #000;
            height: 50px;
            margin: 0 5px;
        }

        .ofppt-text {
            line-height: 1.2;
        }

        .ofppt-text .arabic {
            font-size: 18px;
            font-weight: bold;
            direction: rtl;
            text-align: right;
        }
        
        .ofppt-text .french {
            font-size: 11px;
            font-weight: normal;
            line-height: 1.1;
        }

        /* Right Logo (CMC/FL) Container */
        .logo-right {
            position: absolute;
            right: 0;
            top: 0;
            width: 280px; 
            text-align: right;
        }
        
        .cmc-logo-img {
            width: 80px; 
            height: auto;
            display: block;
            float: right;
            margin-bottom: 3px;
        }

        .cmc-text {
            clear: right; 
            padding-top: 5px; 
            line-height: 1.2;
        }
        
        .cmc-text div {
            text-align: right;
            margin-bottom: 2px;
        }
        
        .cmc-text .arabic-cmc {
            font-size: 14px;
            font-weight: bold;
            direction: rtl;
        }
        
        .cmc-text .amazigh-cmc {
             font-size: 10px;
             font-weight: normal;
             direction: rtl; 
        }
        
        .cmc-text .french-cmc {
             font-size: 10px;
             font-weight: normal;
             direction: ltr;
        }

        /* --- PERSONAL FIELDS (4 LEFT / 2 RIGHT) --- */
        .fields-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            margin-top: 50px;
        }

        .fields-left, .fields-right {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .field {
            display: flex;
            align-items: baseline;
            white-space: nowrap;
        }

        /* Line dimensions */
        .w-l { min-width: 400px; }
        .w-m { min-width: 200px; } 
        .w-s { min-width: 90px; } 
        .w-xs { min-width: 28px; } 

        /* --- CENTRAL RECIPIENT BLOCK --- */
        .recipient-block {
            text-align: center;
            margin-bottom: 40px;
            line-height: 1.4;
            font-size: 16px;
        }
        
        .recipient-block .line-a {
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .recipient-block .line-manager {
            font-weight: bold;
        }

        /* --- BODY TEXT --- */
        .body-sections {
            line-height: 1.5;
            padding-top: 20px;
        }

        .object-line {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .monsieur-line {
            margin-bottom: 20px;
        }
        
        .text-flow-container {
            margin-bottom: 30px;
        }

        /* Motive line */
        .motive-line {
            display: flex;
            align-items: baseline;
            margin-top: 5px;
        }
        
        .motive-line .label {
            white-space: nowrap;
            padding-right: 5px;
        }
        
        .motive-line .full-line {
            flex-grow: 1;
            margin-left: 0;
        }

        /* Print styles */
        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
            }
            .container {
                box-shadow: none;
                margin: 0;
                width: 100%;
                height: 100%;
            }
            .print-controls {
                display: none !important;
            }
        }

        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #333;
            padding: 10px;
            border-radius: 5px;
            z-index: 1000;
        }
        
        .print-controls button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 8px 12px;
            margin: 0 3px;
            border-radius: 3px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Contrôles d\'impression -->
    <div class="print-controls">
        <button onclick="window.print()">🖨️ Imprimer</button>
        <button onclick="window.close()">❌ Fermer</button>
    </div>

    <div class="container">
        <div class="header">
            <div class="logo-left">
                <img src="{BASE_URL}/assets/images/Logo_ofppt.png" alt="OFPPT Logo" class="ofppt-logo-img">
                <div class="ofppt-text-group">
                    <span class="vertical-separator"></span>
                    <div class="ofppt-text">
                        <div class="arabic">مكتب التكوين المهني وإنعاش الشغل</div>
                        <div class="french">Office de la Formation Professionnelle</div>
                        <div class="french">et de la Promotion du Travail</div>
                    </div>
                </div>
            </div>

            <div class="logo-right">
                <img src="{BASE_URL}/assets/images/LOGO-CMC.png" alt="CMC Rabat Logo" class="cmc-logo-img">
                
                <div class="cmc-text">
                    <div class="arabic-cmc">مدن المهن والكفاءات</div>
                    <div class="amazigh-cmc">ⵜⵉⵖⵔⵎⵉⵏ ⵏ ⵜⵎⵖⵓⵏⵉⵏ ⴷ ⵜⵎⴰⵢⵏⵓⵜ</div>
                    <div class="french-cmc">Cités des métiers et des compétences</div>
                </div>
            </div>
        </div>

        <div class="fields-section">
            <div class="fields-left">
                <div class="field">
                    Nom et Prénom : <span class="field-content w-l {NOM_PRENOM_CLASS}">{NOM_PRENOM}</span>
                </div>
                <div class="field">
                    Filière : <span class="field-content w-m {FILIERE_CLASS}">{FILIERE}</span> groupe : <span class="field-content w-s {GROUPE_CLASS}">{GROUPE}</span>
                </div>
                <div class="field">
                    Tél : <span class="field-content w-l {TELEPHONE_CLASS}">{TELEPHONE}</span>
                </div>
                <div class="field">
                    Tél du tuteur : <span class="field-content w-l {TELEPHONE_TUTEUR_CLASS}">{TELEPHONE_TUTEUR}</span>
                </div>
            </div>
            
            <div class="fields-right">
                <div class="field">
                    Date-le : <span class="field-content w-xs {DATE_GENERATION_CLASS}">{DATE_GENERATION}</span>
                </div>
                <div class="field">
                    N° Chambre : <span class="field-content w-m {CHAMBRE_CLASS}">{CHAMBRE}</span>
                </div>
            </div>
        </div>

        <div class="recipient-block">
            <div class="line-a">A</div>
            <div class="line-manager">M le Gestionnaire D\'internat</div>
            <div class="line-manager">De la <strong>CMC Rabat</strong>.</div>
        </div>

        <div class="body-sections">
            <div class="object-line">
                <strong>Objet : Demande d\'autorisation de sortie</strong>
            </div>
            <div class="monsieur-line">
                Monsieur
            </div>
            <div class="text-flow-container">
                J\'ai l\'honneur par la présente demande, de vous informer que je vais sortir de l\'internat
                <br>
                Le <strong>{DATE_DEPART}</strong> et arriver le <strong>{DATE_RETOUR}</strong>. Et que j\'assume ma responsabilité pendant cette période.
            </div>
            
            <div class="motive-line">
                <span class="label">Motif de la demande</span> : <span class="field-content full-line {MOTIF_CLASS}">{MOTIF}</span>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };

        document.addEventListener(\'keydown\', function(e) {
            if (e.ctrlKey && e.key === \'p\') {
                e.preventDefault();
                window.print();
            }
            if (e.key === \'Escape\') {
                window.close();
            }
        });
    </script>
</body>
</html>';
}
?>

<?php echo $contenu_final; ?>