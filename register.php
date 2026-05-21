<?php
$page_title = "Inscription";
require_once 'includes/config.php';

// Si déjà connecté, rediriger
if (is_logged_in()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $phone = sanitize($_POST['phone'] ?? '');

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($password_confirm)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (!validate_email($email)) {
        $error = "L'adresse email n'est pas valide.";
    } elseif (strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif ($password !== $password_confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // Vérifier si l'email existe déjà
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = prepare_query($conn, $query, "s", [$email]);
        $stmt->execute();
        $result = $stmt->fetchAll();

        if (count($result) > 0) {
            $error = "Cet email est déjà utilisé.";
        } else {
            // Hasher le mot de passe et insérer l'utilisateur
            $hashed_password = hash_password($password);
            $query = "INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)";
            $stmt = prepare_query($conn, $query, "ssss", [$name, $email, $hashed_password, $phone]);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Inscription réussie! Veuillez vous connecter.";
                $_SESSION['message_type'] = "success";
                header("Location: login.php");
                exit();
            } else {
                $error = "Erreur lors de l'inscription. Veuillez réessayer.";
            }
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
                        <i class="fas fa-user-plus text-success"></i> Inscription
                    </h2>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom complet</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   placeholder="Votre nom complet" required value="<?php echo isset($_POST['name']) ? sanitize($_POST['name']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="email@example.com" required value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Téléphone (optionnel)</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   placeholder="XX XXX XXX" value="<?php echo isset($_POST['phone']) ? sanitize($_POST['phone']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Minimum 8 caractères" required>
                            <small class="form-text text-muted">Minimum 8 caractères avec lettres et chiffres</small>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Confirmer le mot de passe</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" 
                                   placeholder="Répétez le mot de passe" required>
                        </div>

                        <button type="submit" class="btn btn-success w-100 mb-3">
                            <i class="fas fa-check"></i> S'inscrire
                        </button>
                    </form>

                    <hr>

                    <p class="text-center mb-0">
                        Vous avez déjà un compte? 
                        <a href="login.php" class="text-decoration-none">Se connecter</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
