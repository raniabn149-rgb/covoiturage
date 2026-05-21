<?php
$page_title = "Ajouter un trajet";
require_once 'includes/config.php';
require_login();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departure = sanitize($_POST['departure'] ?? '');
    $destination = sanitize($_POST['destination'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $seats = intval($_POST['seats'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $description = sanitize($_POST['description'] ?? '');

    // Validation
    if (empty($departure) || empty($destination) || empty($date) || empty($time) || $seats <= 0 || $price <= 0) {
        $error = "Tous les champs obligatoires doivent être remplis.";
    } elseif ($departure === $destination) {
        $error = "Le départ et la destination ne peuvent pas être identiques.";
    } elseif (strtotime("$date $time") < time()) {
        $error = "La date et l'heure du trajet doivent être dans le futur.";
    } else {
        $query = "INSERT INTO trips (user_id, departure, destination, date, time, seats, price, description) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = prepare_query($conn, $query, "issssids", [$user_id, $departure, $destination, $date, $time, $seats, $price, $description]);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Trajet ajouté avec succès!";
            $_SESSION['message_type'] = "success";
            header("Location: my_trips.php");
            exit();
        } else {
            $error = "Erreur lors de l'ajout du trajet.";
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h1 class="mb-4"><i class="fas fa-plus text-success"></i> Proposer un nouveau trajet</h1>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="card p-4">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="departure" class="form-label">Lieu de départ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="departure" name="departure" 
                               placeholder="ex: Tunis" required value="<?php echo isset($_POST['departure']) ? sanitize($_POST['departure']) : ''; ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="destination" class="form-label">Destination <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="destination" name="destination" 
                               placeholder="ex: Sidi Bouzid" required value="<?php echo isset($_POST['destination']) ? sanitize($_POST['destination']) : ''; ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="date" class="form-label">Date du trajet <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="date" name="date" required 
                               value="<?php echo isset($_POST['date']) ? $_POST['date'] : ''; ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="time" class="form-label">Heure du départ <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="time" name="time" required 
                               value="<?php echo isset($_POST['time']) ? $_POST['time'] : ''; ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="seats" class="form-label">Nombre de places <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="seats" name="seats" min="1" max="8" 
                               placeholder="1" required value="<?php echo isset($_POST['seats']) ? $_POST['seats'] : ''; ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="price" class="form-label">Prix par passager (DT) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="price" name="price" min="0.01" step="0.01" 
                               placeholder="25.50" required value="<?php echo isset($_POST['price']) ? $_POST['price'] : ''; ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description (optionnel)</label>
                    <textarea class="form-control" id="description" name="description" rows="4" 
                              placeholder="Décrivez votre trajet, vos préférences, etc..."><?php echo isset($_POST['description']) ? sanitize($_POST['description']) : ''; ?></textarea>
                    <small class="form-text text-muted">Musique, pauses, fumeurs, animaux, etc.</small>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-check"></i> Publier le trajet
                    </button>
                    <a href="my_trips.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>

            <!-- Conseils -->
            <div class="card mt-4 bg-light">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-lightbulb"></i> Conseils pour publier un bon trajet</h5>
                    <ul>
                        <li>Soyez honnête sur l'heure de départ et la durée</li>
                        <li>Fixez un prix raisonnable et compétitif</li>
                        <li>Décrivez clairement vos préférences et conditions</li>
                        <li>Répondez rapidement aux demandes de réservation</li>
                        <li>Arrivez à l'heure de rendez-vous</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
