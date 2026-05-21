<?php
$page_title = "Accueil";
require_once 'includes/config.php';

// Traitement du formulaire 'Decline' — annuler un trajet (propriétaire uniquement)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decline_trip_id'])) {
    $trip_id = (int)$_POST['decline_trip_id'];
    if (is_logged_in()) {
        $current_user = $_SESSION['user_id'];
        $stmt = prepare_query($conn, "SELECT user_id FROM trips WHERE id = ?", "i", [$trip_id]);
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row && $row['user_id'] == $current_user) {
            $stmt = prepare_query($conn, "UPDATE trips SET status = 'cancelled' WHERE id = ?", "i", [$trip_id]);
            $stmt->execute();
            $_SESSION['message'] = 'Le trajet a été annulé.';
            $_SESSION['message_type'] = 'success';
            header('Location: index.php');
            exit();
        } else {
            $_SESSION['message'] = 'Action non autorisée.';
            $_SESSION['message_type'] = 'danger';
        }
    } else {
        $_SESSION['message'] = 'Veuillez vous connecter.';
        $_SESSION['message_type'] = 'warning';
    }
}

require_once 'includes/header.php';
?>

<div class="container">
    <!-- Jumbotron -->
    <div class="jumbotron-custom">
        <h1><i class="fas fa-globe"></i> Bienvenue sur CovoitPlus</h1>
        <p class="lead">Trouvez des trajets, économisez, et rencontrez de nouvelles personnes</p>
        <?php if (!is_logged_in()): ?>
            <div class="mt-4">
                <a href="register.php" class="btn btn-light btn-lg me-2">
                    <i class="fas fa-user-plus"></i> S'inscrire
                </a>
                <a href="login.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </a>
            </div>
        <?php else: ?>
            <div class="mt-4">
                <a href="search.php" class="btn btn-light btn-lg me-2">
                    <i class="fas fa-search"></i> Rechercher un trajet
                </a>
                <a href="add_trip.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-plus"></i> Proposer un trajet
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Fonctionnalités principales -->
    <div class="row mb-5">
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-0 text-center">
                <div class="card-body">
                    <i class="fas fa-search fa-3x text-success mb-3"></i>
                    <h5 class="card-title">Rechercher</h5>
                    <p class="card-text">Trouvez facilement des trajets qui correspondent à vos besoins.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-0 text-center">
                <div class="card-body">
                    <i class="fas fa-plus fa-3x text-info mb-3"></i>
                    <h5 class="card-title">Proposer</h5>
                    <p class="card-text">Publiez vos trajets et partagez les frais de route.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-0 text-center">
                <div class="card-body">
                    <i class="fas fa-handshake fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Partager</h5>
                    <p class="card-text">Connectez-vous avec d'autres voyageurs et économisez.</p>
                </div>
            </div>
        </div>
    </div>

    <?php if (is_logged_in()): ?>
        <!-- Trajets récents -->
        <div class="row mt-5">
            <div class="col-md-12">
                <h2 class="mb-4">Trajets récents</h2>
                <?php
                $query = "SELECT t.*, u.name as driver_name, COUNT(b.id) as bookings_count 
                         FROM trips t 
                         JOIN users u ON t.user_id = u.id 
                         LEFT JOIN bookings b ON t.id = b.trip_id 
                         WHERE t.status = 'active' AND t.date >= CURDATE()
                         GROUP BY t.id
                         ORDER BY t.date ASC 
                         LIMIT 6";
                $stmt = $conn->query($query);
                $result = $stmt ? $stmt->fetchAll() : [];
                
                if (count($result) > 0):
                    foreach ($result as $trip):
                ?>
                    <div class="trip-card">
                        <div class="trip-header">
                            <div class="trip-route">
                                <i class="fas fa-map-marker-alt text-success"></i>
                                <?php echo sanitize($trip['departure']); ?> 
                                <i class="fas fa-arrow-right"></i> 
                                <?php echo sanitize($trip['destination']); ?>
                            </div>
                            <div class="trip-price"><?php echo number_format($trip['price'], 2, ',', ' '); ?> €</div>
                        </div>
                        <div class="trip-details">
                            <div class="trip-detail">
                                <label>Conducteur:</label>
                                <span class="trip-detail-value"><?php echo sanitize($trip['driver_name']); ?></span>
                            </div>
                            <div class="trip-detail">
                                <label>Date:</label>
                                <span class="trip-detail-value">
                                    <?php echo date('d/m/Y', strtotime($trip['date'])); ?> à 
                                    <?php echo date('H:i', strtotime($trip['time'])); ?>
                                </span>
                            </div>
                            <div class="trip-detail">
                                <label>Places:</label>
                                <span class="trip-detail-value"><?php echo $trip['seats']; ?> disponible(s)</span>
                            </div>
                            <div class="trip-detail">
                                <label>Réservations:</label>
                                <span class="trip-detail-value"><?php echo $trip['bookings_count']; ?></span>
                            </div>
                        </div>
                        <div class="trip-actions">
                            <button type="button" 
                                class="btn btn-primary btn-sm btn-view"
                                data-trip-id="<?php echo $trip['id']; ?>"
                                data-departure="<?php echo htmlspecialchars($trip['departure'], ENT_QUOTES); ?>"
                                data-destination="<?php echo htmlspecialchars($trip['destination'], ENT_QUOTES); ?>"
                                data-date="<?php echo $trip['date']; ?>"
                                data-time="<?php echo $trip['time']; ?>"
                                data-seats="<?php echo $trip['seats']; ?>"
                                data-price="<?php echo $trip['price']; ?>"
                                data-driver="<?php echo htmlspecialchars($trip['driver_name'], ENT_QUOTES); ?>"
                                data-description="<?php echo htmlspecialchars($trip['description'] ?? '', ENT_QUOTES); ?>"
                            >
                                <i class="fas fa-eye"></i> Voir plus
                            </button>

                            <?php if (is_logged_in() && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $trip['user_id']): ?>
                                <form method="post" class="d-inline ms-2" onsubmit="return confirm('Annuler ce trajet ?');">
                                    <input type="hidden" name="decline_trip_id" value="<?php echo $trip['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-ban"></i> Decline
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php 
                    endforeach;
                else:
                ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Aucun trajet disponible pour le moment.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de détails du trajet -->
<div class="modal fade" id="tripModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails du trajet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Itinéraire:</strong> <span id="modal-route"></span></p>
                <p><strong>Conducteur:</strong> <span id="modal-driver"></span></p>
                <p><strong>Date / Heure:</strong> <span id="modal-datetime"></span></p>
                <p><strong>Places disponibles:</strong> <span id="modal-seats"></span></p>
                <p><strong>Prix:</strong> <span id="modal-price"></span> €</p>
                <hr>
                <p id="modal-description"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <a href="#" id="modal-book-link" class="btn btn-primary">Réserver</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('click', function(e){
        var btn = e.target.closest && e.target.closest('.btn-view');
        if (!btn) return;
        var departure = btn.getAttribute('data-departure');
        var destination = btn.getAttribute('data-destination');
        var driver = btn.getAttribute('data-driver');
        var date = btn.getAttribute('data-date');
        var time = btn.getAttribute('data-time');
        var seats = btn.getAttribute('data-seats');
        var price = btn.getAttribute('data-price');
        var description = btn.getAttribute('data-description');
        document.getElementById('modal-route').textContent = departure + ' → ' + destination;
        document.getElementById('modal-driver').textContent = driver;
        document.getElementById('modal-datetime').textContent = date + ' à ' + time;
        document.getElementById('modal-seats').textContent = seats;
        document.getElementById('modal-price').textContent = parseFloat(price).toFixed(2).replace('.', ',');
        document.getElementById('modal-description').textContent = description || 'Aucune description.';
        // Lien de réservation redirige vers la page search avec filtres
        var link = 'search.php?departure=' + encodeURIComponent(departure) + '&destination=' + encodeURIComponent(destination);
        document.getElementById('modal-book-link').setAttribute('href', link);
        var modalEl = document.getElementById('tripModal');
        var modal = new bootstrap.Modal(modalEl);
        modal.show();
});
</script>

<?php require_once 'includes/footer.php'; ?>
