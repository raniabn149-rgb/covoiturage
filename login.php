<?php
$page_title = "Connexion";
require_once 'includes/config.php';

// Si déjà connecté, rediriger
if (is_logged_in()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($email) || empty($password)) {
        $error = "Email et mot de passe sont obligatoires.";
    } else {
        // Préparer la requête (protection SQL Injection)
        $query = "SELECT id, name, password FROM users WHERE email = ?";
        $stmt = prepare_query($conn, $query, "s", [$email]);
        $stmt->execute();
        $result = $stmt->fetchAll();

        if (count($result) === 1) {
            $user = $result[0];
            // Vérifier le mot de passe
            if (verify_password($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['LAST_ACTIVITY'] = time();

                if (isset($_POST['remember'])) {
                    set_remember_me_cookie($user['id']);
                } else {
                    clear_remember_me_cookie();
                }
                
                $_SESSION['message'] = "Connexion réussie!";
                $_SESSION['message_type'] = "success";
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Email ou mot de passe incorrect.";
            }
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body p-5">
                    <h2 class="card-title text-center mb-4">
                        <i class="fas fa-sign-in-alt text-primary"></i> Connexion
                    </h2>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['timeout'])): ?>
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-clock"></i> Votre session a expiré. Veuillez vous reconnecter.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="email@example.com" required autofocus 
                                   value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Votre mot de passe" required>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Se souvenir de moi
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-check"></i> Se connecter
                        </button>
                    </form>

                    <hr>

                    <p class="text-center mb-0">
                        Vous n'avez pas de compte? 
                        <a href="register.php" class="text-decoration-none">S'inscrire</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
