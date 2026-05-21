<?php
$page_title = "Rechercher un trajet";
require_once 'includes/config.php';
require_login();

$user_id = $_SESSION['user_id'];
$trips = [];
$search_performed = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' || !empty($_GET)) {
    $departure = sanitize($_GET['departure'] ?? '');
    $destination = sanitize($_GET['destination'] ?? '');
    $date = $_GET['date'] ?? '';

    if (!empty($departure) || !empty($destination) || !empty($date)) {
        $search_performed = true;
        
        $query = "SELECT t.*, u.name as driver_name, COUNT(b.id) as bookings_count 
                 FROM trips t 
                 JOIN users u ON t.user_id = u.id 
                 LEFT JOIN bookings b ON t.id = b.trip_id AND b.status = 'confirmed'
                 WHERE t.status = 'active' AND t.date >= CURDATE() AND t.user_id != ?";
        $types = "i";
        $params = [$user_id];

        if (!empty($departure)) {
            $query .= " AND t.departure LIKE ?";
            $types .= "s";
            $params[] = "%$departure%";
        }
        if (!empty($destination)) {
            $query .= " AND t.destination LIKE ?";
            $types .= "s";
            $params[] = "%$destination%";
        }
        if (!empty($date)) {
            $query .= " AND DATE(t.date) = ?";
            $types .= "s";
            $params[] = $date;
        }

        $query .= " GROUP BY t.id ORDER BY t.date ASC";
        $stmt = prepare_query($conn, $query, $types, $params);
        $stmt->execute();
        $result = $stmt->fetchAll();
        $trips = [];
        foreach ($result as $row) {
            $trips[] = $row;
        }
    }
}

// Traiter les réservations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_trip'])) {
    $trip_id = intval($_POST['trip_id']);
    $seats_booked = intval($_POST['seats_booked'] ?? 1);

    // Vérifier que l'utilisateur ne s'est pas déjà réservé
    $query = "SELECT id FROM bookings WHERE user_id = ? AND trip_id = ?";
    $stmt = prepare_query($conn, $query, "ii", [$user_id, $trip_id]);
    $stmt->execute();
    $existing = $stmt->fetchAll();

    if (count($existing) > 0) {
        $error = "Vous avez déjà réservé ce trajet.";
    } else {
        // Vérifier les places disponibles
        $query = "SELECT t.seats, COUNT(b.id) as booked FROM trips t LEFT JOIN bookings b ON t.id = b.trip_id AND b.status = 'confirmed' WHERE t.id = ? GROUP BY t.id";
        $stmt = prepare_query($conn, $query, "i", [$trip_id]);
        $stmt->execute();
        $trip_info = $stmt->fetch();

        $available = $trip_info['seats'] - ($trip_info['booked'] ?? 0);
        if ($seats_booked > $available) {
            $error = "Pas assez de places disponibles. Disponible: $available";
        } else {
            $query = "INSERT INTO bookings (user_id, trip_id, seats_booked, status) VALUES (?, ?, ?, 'confirmed')";
            $stmt = prepare_query($conn, $query, "iii", [$user_id, $trip_id, $seats_booked]);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Réservation effectuée avec succès!";
                $_SESSION['message_type'] = "success";
                header("Location: my_bookings.php");
                exit();
            } else {
                $error = "Erreur lors de la réservation.";
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container">
    <h1 class="mb-4"><i class="fas fa-search"></i> Rechercher un trajet</h1>

    <!-- Formulaire de recherche -->
    <div class="card mb-4 p-4">
        <form method="GET" action="">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="departure" class="form-label">Départ</label>
                    <input type="text" class="form-control" id="departure" name="departure" 
                           placeholder="ex: Tunis" value="<?php echo isset($_GET['departure']) ? sanitize($_GET['departure']) : ''; ?>">
                </div>

                <div class="col-md-4 mb-3">
                    <label for="destination" class="form-label">Destination</label>
                    <input type="text" class="form-control" id="destination" name="destination" 
                           placeholder="ex: Sidi Bouzid" value="<?php echo isset($_GET['destination']) ? sanitize($_GET['destination']) : ''; ?>">
                </div>

                <div class="col-md-4 mb-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" 
                           value="<?php echo isset($_GET['date']) ? $_GET['date'] : ''; ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Rechercher
            </button>
            <a href="search.php" class="btn btn-secondary">
                <i class="fas fa-redo"></i> Réinitialiser
            </a>
        </form>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Résultats de recherche -->
    <?php if ($search_performed): ?>
        <?php if (count($trips) > 0): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> <strong><?php echo count($trips); ?> trajet(s) trouvé(s)</strong>
            </div>

            <?php foreach ($trips as $trip): ?>
                <div class="trip-card">
                    <div class="trip-header">
                        <div class="trip-route">
                            <i class="fas fa-map-marker-alt text-success"></i>
                            <strong><?php echo sanitize($trip['departure']); ?></strong>
                            <i class="fas fa-arrow-right"></i>
                            <strong><?php echo sanitize($trip['destination']); ?></strong>
                        </div>
                        <div class="trip-price"><?php echo number_format($trip['price'], 2, ',', ' '); ?> DT</div>
                    </div>

                    <div class="trip-details">
                        <div class="trip-detail">
                            <label><i class="fas fa-user"></i> Conducteur:</label>
                            <span class="trip-detail-value"><?php echo sanitize($trip['driver_name']); ?></span>
                        </div>
                        <div class="trip-detail">
                            <label><i class="fas fa-calendar"></i> Date:</label>
                            <span class="trip-detail-value">
                                <?php echo date('d/m/Y', strtotime($trip['date'])); ?> à 
                                <?php echo date('H:i', strtotime($trip['time'])); ?>
                            </span>
                        </div>
                        <div class="trip-detail">
                            <label><i class="fas fa-chair"></i> Places disponibles:</label>
                            <span class="trip-detail-value"><?php echo $trip['seats'] - $trip['bookings_count']; ?> / <?php echo $trip['seats']; ?></span>
                        </div>
                    </div>

                    <?php if (!empty($trip['description'])): ?>
                        <div class="alert alert-light mt-2 mb-2">
                            <strong>Description:</strong> <?php echo sanitize($trip['description']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="trip-actions">
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                            <input type="hidden" name="book_trip" value="1">
                            <input type="number" name="seats_booked" min="1" max="<?php echo $trip['seats'] - $trip['bookings_count']; ?>" 
                                   value="1" class="form-control" style="width: 80px; display: inline-block;" required>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check"></i> Réserver
                            </button>
                        </form>
                        <a href="#" class="btn btn-outline-primary">
                            <i class="fas fa-eye"></i> Plus de détails
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> Aucun trajet ne correspond à votre recherche.
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Utilisez le formulaire ci-dessus pour rechercher des trajets.
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
