<?php
class MaxSecurity {
    
    // Generate CSRF Token
    public static function generateCSRF() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
    
    // Validate CSRF Token
    public static function validateCSRF($token, $timeout = 3600) {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        $valid = hash_equals($_SESSION['csrf_token'], $token);
        $fresh = (time() - $_SESSION['csrf_token_time']) < $timeout;
        
        // One-time use token
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        
        return $valid && $fresh;
    }
    
    // Advanced Input Sanitization
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        // Trim whitespace
        $input = trim($input);
        // Convert special characters
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Remove extra whitespace
        $input = preg_replace('/\s+/', ' ', $input);
        
        return $input;
    }
    
    // Advanced Email Validation
    public static function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Check DNS records
        $domain = explode('@', $email)[1];
        return checkdnsrr($domain, 'MX');
    }
    
    // Strong Password Validation
    public static function validatePassword($password) {
        $min_length = 12;
        $has_uppercase = preg_match('@[A-Z]@', $password);
        $has_lowercase = preg_match('@[a-z]@', $password);
        $has_number = preg_match('@[0-9]@', $password);
        $has_special = preg_match('@[^\w]@', $password);
        $no_spaces = !preg_match('@\s@', $password);
        
        return strlen($password) >= $min_length && 
               $has_uppercase && 
               $has_lowercase && 
               $has_number && 
               $has_special &&
               $no_spaces;
    }
    
    // Rate Limiting
    public static function checkRateLimit($key, $max_attempts = 5, $time_window = 900) {
        $now = time();
        $attempts_key = "rate_limit_{$key}";
        
        if (!isset($_SESSION[$attempts_key])) {
            $_SESSION[$attempts_key] = [
                'attempts' => 0,
                'first_attempt' => $now
            ];
        }
        
        $data = &$_SESSION[$attempts_key];
        
        // Reset if time window passed
        if ($now - $data['first_attempt'] > $time_window) {
            $data['attempts'] = 0;
            $data['first_attempt'] = $now;
        }
        
        $data['attempts']++;
        
        if ($data['attempts'] > $max_attempts) {
            $wait_time = $time_window - ($now - $data['first_attempt']);
            throw new Exception("Trop de tentatives. Veuillez réessayer dans " . ceil($wait_time/60) . " minutes.");
        }
        
        return true;
    }
    
    // Secure Headers
    public static function setSecurityHeaders() {
        header("X-Frame-Options: DENY");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; style-src 'self' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; img-src 'self' data: https:;");
    }
    
    // Session Security
    public static function secureSession() {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        
        session_regenerate_id(true);
    }
}

// Advanced sanitize function replacement
function secure_sanitize($input) {
    return MaxSecurity::sanitize($input);
}
?>