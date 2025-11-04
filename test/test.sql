UPDATE print_templates SET contenu = '<!DOCTYPE html>
<html lang=\"fr\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Demande d''autorisation de sortie OFPPT</title>
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
        
        /* Utility for dotted lines with flex alignment */
        .line-wrapper {
            display: inline-flex;
            align-items: flex-end;
            padding-bottom: 2px;
        }
        
        .line {
            display: inline-block;
            border-bottom: 1px dotted #000;
            margin: 0 5px;
            height: 10px;
            box-sizing: border-box;
        }

        .line-content {
            display: inline-block;
            border-bottom: 1px dotted #000;
            margin: 0 5px;
            height: 10px;
            box-sizing: border-box;
            min-width: 50px;
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
            opacity: 0.7; 
            margin-bottom: 3px;
            float: right;
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
        .w-l { width: 400px; }
        .w-m { width: 200px; } 
        .w-s { width: 90px; } 
        .w-xs { width: 28px; } 

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
    <!-- Contrôles d''impression -->
    <div class=\"print-controls\">
        <button onclick=\"window.print()\">🖨️ Imprimer</button>
        <button onclick=\"window.close()\">❌ Fermer</button>
    </div>

    <div class=\"container\">
        <div class=\"header\">
            <div class=\"logo-left\">
                <img src=\"Logo_ofppt.png\" alt=\"OFPPT Logo\" class=\"ofppt-logo-img\">
                <div class=\"ofppt-text-group\">
                    <span class=\"vertical-separator\"></span>
                    <div class=\"ofppt-text\">
                        <div class=\"arabic\">مكتب التكوين المهني وإنعاش الشغل</div>
                        <div class=\"french\">Office de la Formation Professionnelle</div>
                        <div class=\"french\">et de la Promotion du Travail</div>
                    </div>
                </div>
            </div>

            <div class=\"logo-right\">
                <img src=\"LOGO-CMC.png\" alt=\"CMC Rabat Logo\" class=\"cmc-logo-img\">
                
                <div class=\"cmc-text\">
                    <div class=\"arabic-cmc\">مدن المهن والكفاءات</div>
                    <div class=\"amazigh-cmc\">ⵜⵉⵖⵔⵎⵉⵏ ⵏ ⵜⵎⵖⵓⵏⵉⵏ ⴷ ⵜⵎⴰⵢⵏⵓⵜ</div>
                    <div class=\"french-cmc\">Cités des métiers et des compétences</div>
                </div>
            </div>
        </div>

        <div class=\"fields-section\">
            <div class=\"fields-left\">
                <div class=\"field\">
                    Nom et Prénom : <span class=\"line-wrapper\"><span class=\"line-content w-l\">{NOM_PRENOM}</span></span>
                </div>
                <div class=\"field\">
                    Filière : <span class=\"line-wrapper\"><span class=\"line-content w-m\">{FILIERE}</span></span> groupe : <span class=\"line-wrapper\"><span class=\"line-content w-s\">{GROUPE}</span></span>
                </div>
                <div class=\"field\">
                    Tél : <span class=\"line-wrapper\"><span class=\"line-content w-l\">{TELEPHONE}</span></span>
                </div>
                <div class=\"field\">
                    Tél du tuteur : <span class=\"line-wrapper\"><span class=\"line-content w-l\">{TELEPHONE_TUTEUR}</span></span>
                </div>
            </div>
            
            <div class=\"fields-right\">
                <div class=\"field\">
                    Date-le : <span class=\"line-wrapper\"><span class=\"line-content w-xs\"></span></span>
                    {DATE_GENERATION}
                </div>
                <div class=\"field\">
                    N° Chambre : <span class=\"line-wrapper\"><span class=\"line-content w-m\">{CHAMBRE}</span></span>
                </div>
            </div>
        </div>

        <div class=\"recipient-block\">
            <div class=\"line-a\">A</div>
            <div class=\"line-manager\">M le Gestionnaire D''internat</div>
            <div class=\"line-manager\">De la <strong>CMC Rabat</strong>.</div>
        </div>

        <div class=\"body-sections\">
            <div class=\"object-line\">
                <strong>Objet : Demande d''autorisation de sortie</strong>
            </div>
            <div class=\"monsieur-line\">
                Monsieur
            </div>
            <div class=\"text-flow-container\">
                J''ai l''honneur par la présente demande, de vous informer que je vais sortir de l''internat
                <br>
                Le <strong>{DATE_DEPART}</strong> et arriver le <strong>{DATE_RETOUR}</strong>. Et que j''assume ma responsabilité pendant cette période.
            </div>
            
            <div class=\"motive-line\">
                <span class=\"label\">Motif de la demande</span> : <span class=\"line-content full-line\">{MOTIF}</span>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };

        document.addEventListener(''keydown'', function(e) {
            if (e.ctrlKey && e.key === ''p'') {
                e.preventDefault();
                window.print();
            }
            if (e.key === ''Escape'') {
                window.close();
            }
        });
    </script>
</body>
</html>' 
WHERE id = 1;