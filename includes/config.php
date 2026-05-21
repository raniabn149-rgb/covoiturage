<?php
/**
 * Configuration de la base de données
 * Plateforme de Covoiturage
 */

// Paramètres de connexion à la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'carpooling_db');

// Connexion à la base de données avec PDO
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $conn = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // lance des exceptions en cas d'erreur
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // retourne des tableaux associatifs
        PDO::ATTR_EMULATE_PREPARES => false, // vraies requêtes préparées (sécurité)
    ]);
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// Paramètres de sécurité
define('SESSION_LIFETIME', 3600); // 1 heure

// Fonction pour nettoyer les entrées (prévention XSS)
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Fonction pour valider une adresse email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Fonction pour hasher un mot de passe
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Fonction pour vérifier un mot de passe
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Fonction pour préparer les requêtes SQL (prévention SQL Injection)
function prepare_query($conn, $query, $types = '', $params = []) {
    $stmt = $conn->prepare($query); //méthode PDO qui prépare la requête SQL

    if (!$stmt) {
        $errorInfo = $conn->errorInfo();
        die("Erreur de préparation : " . ($errorInfo[2] ?? 'Erreur PDO inconnue'));
    } //die :arrête tout le script et affiche le message


    if (!empty($types) && !empty($params)) {
        for ($i = 0; $i < strlen($types); $i++) {
            $type = $types[$i];
            $value = $params[$i] ?? null;
            $paramType = PDO::PARAM_STR;

            switch ($type) {
                case 'i':
                    $paramType = PDO::PARAM_INT;
                    $value = (int)$value;
                    break;
                case 'd':
                    $paramType = PDO::PARAM_STR;
                    $value = (float)$value;
                    break;
                case 's':
                default:
                    $paramType = PDO::PARAM_STR;
                    $value = (string)$value;
                    break;
            }

            $stmt->bindValue($i + 1, $value, $paramType);
        }
    }

    return $stmt;
}

// Configuration du cookie « se souvenir de moi »
define('REMEMBER_ME_COOKIE', 'remember_me');
define('REMEMBER_ME_DAYS', 30);
define('REMEMBER_ME_LIFETIME', REMEMBER_ME_DAYS * 24 * 60 * 60);
define('COOKIE_SECRET', 'change_this_secret_to_a_random_string');

function create_remember_me_token($user_id) {
    $payload = $user_id . ':' . hash_hmac('sha256', $user_id, COOKIE_SECRET);
    return base64_encode($payload);
}

function validate_remember_me_token($token) {
    $decoded = base64_decode($token, true);
    if ($decoded === false) {
        return false;
    }

    $parts = explode(':', $decoded, 2);
    if (count($parts) !== 2 || !ctype_digit($parts[0])) {
        return false;
    }

    [$user_id, $hash] = $parts;
    $expected_hash = hash_hmac('sha256', $user_id, COOKIE_SECRET);
    return hash_equals($expected_hash, $hash) ? (int)$user_id : false;
}

function set_remember_me_cookie($user_id) {
    setcookie(REMEMBER_ME_COOKIE, create_remember_me_token($user_id), time() + REMEMBER_ME_LIFETIME, '/', '', false, true);
}

function clear_remember_me_cookie() {
    setcookie(REMEMBER_ME_COOKIE, '', time() - 3600, '/', '', false, true);
    if (isset($_COOKIE[REMEMBER_ME_COOKIE])) {
        unset($_COOKIE[REMEMBER_ME_COOKIE]);
    }
}

function login_from_cookie($conn) {
    if (is_logged_in() || !isset($_COOKIE[REMEMBER_ME_COOKIE])) {
        return;
    }

    $user_id = validate_remember_me_token($_COOKIE[REMEMBER_ME_COOKIE]);
    if (!$user_id) {
        clear_remember_me_cookie();
        return;
    }

    $query = "SELECT id, name FROM users WHERE id = ?";
    $stmt = prepare_query($conn, $query, "i", [$user_id]);
    $stmt->execute();
    $result = $stmt->fetchAll();

    if (count($result) === 1) {
        $user = $result[0];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['LAST_ACTIVITY'] = time();
        set_remember_me_cookie($user['id']);
    } else {
        clear_remember_me_cookie();
    }
}

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    login_from_cookie($conn);

    // Vérifier l'inactivité
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_LIFETIME)) {
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}

// Vérifier si l'utilisateur est connecté
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Rediriger si non connecté
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit();
    }
}

// Obtenir les informations de l'utilisateur
function get_user_info($conn, $user_id) {
    $query = "SELECT id, name, email FROM users WHERE id = ?";
    $stmt = prepare_query($conn, $query, "i", [$user_id]);
    $stmt->execute();
    $result = $stmt->fetch();
    return $result;
}
?>
