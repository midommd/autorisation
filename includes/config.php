<?php
session_start();

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'cmc_tamsna_auth');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration de l'application
define('APP_NAME', 'CMC Tamsna - Système d\'Autorisation');
define('MAX_AUTHORIZATION_HOURS', 19); // 19h maximum

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion: " . $e->getMessage());
}

// Base URL configuration - FORCE HTTP for XAMPP
$host = $_SERVER['HTTP_HOST'];
$project_path = dirname(dirname($_SERVER['SCRIPT_NAME']));
// Always use HTTP in XAMPP to avoid mixed content issues
define('BASE_URL', 'http://' . $host . rtrim($project_path, '/'));

// Fonction de sécurité
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
function log_activity($user_id, $action, $type) {
    global $pdo;
    try {
        // Vérifier si la table security_logs existe
        $table_exists = $pdo->query("SHOW TABLES LIKE 'security_logs'")->fetch();
        
        if (!$table_exists) {
            // Créer la table si elle n'existe pas
            $pdo->exec("CREATE TABLE IF NOT EXISTS security_logs (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NULL,
                action VARCHAR(255) NOT NULL,
                type VARCHAR(50) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT,
                timestamp INT NOT NULL,
                INDEX idx_user_timestamp (user_id, timestamp)
            )");
        }
        
        $sql = "INSERT INTO security_logs (user_id, action, type, ip_address, user_agent, timestamp) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            $user_id,
            $action,
            $type,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            time()
        ]);
    } catch (PDOException $e) {
        error_log("Activity log error: " . $e->getMessage());
        return false;
    }
}

// Fonction pour logger les tentatives de connexion
function log_attempt($user_id, $success, $pdo) {
    try {
        // Vérifier si la table existe
        $table_exists = $pdo->query("SHOW TABLES LIKE 'login_attempts'")->fetch();
        
        if (!$table_exists) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT,
                attempt_time INT NOT NULL,
                success BOOLEAN NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT,
                INDEX idx_user_time (user_id, attempt_time)
            )");
        }
        
        $sql = "INSERT INTO login_attempts (user_id, attempt_time, success, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $user_id, 
            time(), 
            $success ? 1 : 0,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    } catch (PDOException $e) {
        error_log("Login attempt log error: " . $e->getMessage());
    }
}

// Fonction pour vérifier les tentatives de brute force
function check_brute_force($user_id, $pdo) {
    try {
        $window_time = time() - 900; // 15 minutes
        $sql = "SELECT COUNT(*) as attempts FROM login_attempts 
                WHERE user_id = ? AND attempt_time > ? AND success = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $window_time]);
        $result = $stmt->fetch();
        
        return $result['attempts'] >= 5; // 5 tentatives max en 15 minutes
    } catch (PDOException $e) {
        error_log("Brute force check error: " . $e->getMessage());
        return false;
    }
}
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

// Fonction pour valider le token CSRF
function validate_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
?>