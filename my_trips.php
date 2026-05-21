<?php
$page_title = "Mes trajets";
require_once 'includes/config.php';
require_login();

$user_id = $_SESSION['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_trip'])) {
    $trip_id = intval($_POST['trip_id']);

    $query = "UPDATE trips SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'active'";
    $stmt = prepare_query($conn, $query, "ii", [$trip_id, $user_id]);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['message'] = "Le trajet a été annulé avec succès.";
        $_SESSION['message_type'] = "success";
        header("Location: my_trips.php");
        exit();
    } else {
        $error = "Impossible d'annuler ce trajet.";
    }
}

require_once 'includes/header.php';

$query = "SELECT t.*, COUNT(b.id) as confirmed_bookings
          FROM trips t
          LEFT JOIN bookings b ON t.id = b.trip_id AND b.status = 'confirmed'
          WHERE t.user_id = ?
          GROUP BY t.id
          ORDER BY t.date DESC, t.time DESC";
$stmt = prepare_query($conn, $query, "i", [$user_id]);
$stmt->execute();
$result = $stmt->fetchAll();
?>

<div class="container">
    <h1 class="mb-4"><i class="fas fa-list"></i> Mes trajets</h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (count($result) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Départ</th>
                        <th>Destination</th>
                        <th>Date</th>
                        <th>Places</th>
                        <th>Prix</th>
                        <th>Réservations</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result as $trip): ?>
                        <tr>
                            <td><?php echo sanitize($trip['departure']); ?></td>
                            <td><?php echo sanitize($trip['destination']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($trip['date'] . ' ' . $trip['time'])); ?></td>
                            <td><?php echo $trip['seats']; ?></td>
                            <td><?php echo number_format($trip['price'], 2, ',', ' '); ?> DT</td>
                            <td><?php echo $trip['confirmed_bookings']; ?></td>
                            <td>
                                <?php if ($trip['status'] === 'active'): ?>
                                    <span class="badge bg-success">Actif</span>
                                <?php elseif ($trip['status'] === 'completed'): ?>
                                    <span class="badge bg-secondary">Terminé</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Annulé</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($trip['status'] === 'active' && strtotime($trip['date'] . ' ' . $trip['time']) >= time()): ?>
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                                        <button type="submit" name="cancel_trip" class="btn btn-sm btn-danger" onclick="return confirm('Confirmer l\'annulation du trajet ?');">
                                            <i class="fas fa-trash-alt"></i> Annuler
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                                        <i class="fas fa-ban"></i> Aucun
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Vous n'avez encore proposé aucun trajet.
            <a href="add_trip.php" class="alert-link">Ajouter un trajet</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
