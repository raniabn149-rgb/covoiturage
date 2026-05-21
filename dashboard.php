<?php
$page_title = "Tableau de bord";
require_once 'includes/config.php';
require_login();

$user_id = $_SESSION['user_id'];
$user_info = get_user_info($conn, $user_id);

// Statistiques
$query_trips = "SELECT COUNT(*) as count FROM trips WHERE user_id = ? AND status = 'active'";
$stmt = prepare_query($conn, $query_trips, "i", [$user_id]);
$stmt->execute();
$trips_result = $stmt->fetch();

$query_bookings = "SELECT COUNT(b.id) as count FROM bookings b JOIN trips t ON b.trip_id = t.id WHERE t.user_id = ? AND b.status = 'confirmed'";
$stmt = prepare_query($conn, $query_bookings, "i", [$user_id]);
$stmt->execute();
$bookings_result = $stmt->fetch();

$query_earnings = "SELECT SUM(b.seats_booked * t.price) as total FROM bookings b JOIN trips t ON b.trip_id = t.id WHERE t.user_id = ? AND b.status = 'confirmed'";
$stmt = prepare_query($conn, $query_earnings, "i", [$user_id]);
$stmt->execute();
$earnings_result = $stmt->fetch();

require_once 'includes/header.php';
?>

<div class="container">
    <div class="dashboard-header">
        <h1><i class="fas fa-tachometer-alt"></i> Tableau de bord</h1>
        <p class="text-muted">Bienvenue, <?php echo sanitize($user_info['name']); ?>!</p>
    </div>

    <!-- Statistiques -->
    <div class="row mb-5">
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $trips_result['count']; ?></div>
                <div class="stat-label">Trajets actifs</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $bookings_result['count']; ?></div>
                <div class="stat-label">Réservations confirmées</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($earnings_result['total'] ?? 0, 2, ',', ' '); ?> DT</div>
                <div class="stat-label">Revenus totaux</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format(($bookings_result['count'] ?? 0), 0, ',', ' '); ?></div>
                <div class="stat-label">Réservations totales</div>
            </div>
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="row mb-5">
        <div class="col-md-12">
            <h3 class="mb-3">Actions rapides</h3>
        </div>
        <div class="col-md-4 mb-3">
            <a href="add_trip.php" class="btn btn-primary w-100 py-3">
                <i class="fas fa-plus fa-2x"></i><br>
                Proposer un trajet
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="search.php" class="btn btn-info w-100 py-3">
                <i class="fas fa-search fa-2x"></i><br>
                Rechercher un trajet
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="my_bookings.php" class="btn btn-secondary w-100 py-3">
                <i class="fas fa-ticket fa-2x"></i><br>
                Voir mes réservations
            </a>
        </div>
    </div>

    <!-- Prochains trajets -->
    <div class="row">
        <div class="col-md-6">
            <h3 class="mb-3">Mes prochains trajets</h3>
            <?php
            $query = "SELECT * FROM trips WHERE user_id = ? AND date >= CURDATE() AND status = 'active' ORDER BY date ASC LIMIT 3";
            $stmt = prepare_query($conn, $query, "i", [$user_id]);
            $stmt->execute();
            $result = $stmt->fetchAll();

            if (count($result) > 0):
                foreach ($result as $trip):
            ?>
                <div class="trip-card">
                    <div class="trip-header">
                        <div class="trip-route">
                            <strong><?php echo sanitize($trip['departure']); ?></strong> → 
                            <strong><?php echo sanitize($trip['destination']); ?></strong>
                        </div>
                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($trip['date'] . ' ' . $trip['time'])); ?></small>
                    </div>
                    <div class="trip-details">
                        <div class="trip-detail">
                            <label>Places:</label>
                            <span class="trip-detail-value"><?php echo $trip['seats']; ?></span>
                        </div>
                        <div class="trip-detail">
                            <label>Prix:</label>
                            <span class="trip-detail-value"><?php echo number_format($trip['price'], 2, ',', ' '); ?> €</span>
                        </div>
                    </div>
                    <div class="trip-actions">
                        <a href="my_trips.php?view=<?php echo $trip['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye"></i> Détails
                        </a>
                    </div>
                </div>
            <?php
                endforeach;
            else:
            ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Vous n'avez pas de trajets prévus.
                    <a href="add_trip.php" class="alert-link">Proposer un trajet</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Prochaines réservations -->
        <div class="col-md-6">
            <h3 class="mb-3">Mes prochaines réservations</h3>
            <?php
            $query = "SELECT b.*, t.departure, t.destination, t.date, t.time, u.name as driver_name 
                     FROM bookings b 
                     JOIN trips t ON b.trip_id = t.id 
                     JOIN users u ON t.user_id = u.id 
                     WHERE b.user_id = ? AND t.date >= CURDATE() AND b.status = 'confirmed'
                     ORDER BY t.date ASC LIMIT 3";
            $stmt = prepare_query($conn, $query, "i", [$user_id]);
            $stmt->execute();
            $result = $stmt->fetchAll();

            if (count($result) > 0):
                foreach ($result as $booking):
            ?>
                <div class="trip-card">
                    <div class="trip-header">
                        <div class="trip-route">
                            <strong><?php echo sanitize($booking['departure']); ?></strong> → 
                            <strong><?php echo sanitize($booking['destination']); ?></strong>
                        </div>
                        <span class="badge bg-success">Confirmée</span>
                    </div>
                    <div class="trip-details">
                        <div class="trip-detail">
                            <label>Conducteur:</label>
                            <span class="trip-detail-value"><?php echo sanitize($booking['driver_name']); ?></span>
                        </div>
                        <div class="trip-detail">
                            <label>Date:</label>
                            <span class="trip-detail-value"><?php echo date('d/m/Y H:i', strtotime($booking['date'] . ' ' . $booking['time'])); ?></span>
                        </div>
                        <div class="trip-detail">
                            <label>Places:</label>
                            <span class="trip-detail-value"><?php echo $booking['seats_booked']; ?></span>
                        </div>
                    </div>
                    <div class="trip-actions">
                        <a href="my_bookings.php?view=<?php echo $booking['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye"></i> Détails
                        </a>
                    </div>
                </div>
            <?php
                endforeach;
            else:
            ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Vous n'avez pas de réservations.
                    <a href="search.php" class="alert-link">Rechercher un trajet</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
