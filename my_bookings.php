<?php
$page_title = "Mes réservations";
require_once 'includes/config.php';
require_login();

$user_id = $_SESSION['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    $query = "UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'confirmed'";
    $stmt = prepare_query($conn, $query, "ii", [$booking_id, $user_id]);
    if ($stmt->execute() && $stmt->rowCount() > 0) {
        $_SESSION['message'] = "Réservation annulée avec succès.";
        $_SESSION['message_type'] = "success";
        header("Location: my_bookings.php");
        exit();
    } else {
        $error = "Impossible d'annuler cette réservation.";
    }
}

require_once 'includes/header.php';

$query = "SELECT b.*, t.departure, t.destination, t.date, t.time, t.price, u.name as driver_name, u.phone as driver_phone, t.status as trip_status
          FROM bookings b
          JOIN trips t ON b.trip_id = t.id
          JOIN users u ON t.user_id = u.id
          WHERE b.user_id = ?
          ORDER BY t.date DESC, t.time DESC";
$stmt = prepare_query($conn, $query, "i", [$user_id]);
$stmt->execute();
$result = $stmt->fetchAll();
?>

<div class="container">
    <h1 class="mb-4"><i class="fas fa-ticket-alt"></i> Mes réservations</h1>

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
                        <th>Trajet</th>
                        <th>Conducteur</th>
                        <th>Téléphone</th>
                        <th>Date</th>
                        <th>Places</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result as $booking): ?>
                        <tr>
                            <td><?php echo sanitize($booking['departure']); ?> → <?php echo sanitize($booking['destination']); ?></td>
                            <td><?php echo sanitize($booking['driver_name']); ?></td>
                            <td>
                                <?php if (!empty($booking['driver_phone'])): ?>
                                    <a href="tel:<?php echo sanitize($booking['driver_phone']); ?>"><?php echo sanitize($booking['driver_phone']); ?></a>
                                <?php else: ?>
                                    <span class="text-muted">Non renseigné</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($booking['date'] . ' ' . $booking['time'])); ?></td>
                            <td><?php echo $booking['seats_booked']; ?></td>
                            <td><?php echo number_format($booking['price'], 2, ',', ' '); ?> DT</td>
                            <td>
                                <?php if ($booking['status'] === 'confirmed'): ?>
                                    <span class="badge bg-success">Confirmée</span>
                                <?php elseif ($booking['status'] === 'pending'): ?>
                                    <span class="badge bg-warning text-dark">En attente</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Annulée</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($booking['status'] === 'confirmed' && strtotime($booking['date'] . ' ' . $booking['time']) >= time()): ?>
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" name="cancel_booking" class="btn btn-sm btn-danger" onclick="return confirm('Voulez-vous vraiment annuler cette réservation ?');">
                                            <i class="fas fa-ban"></i> Annuler
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                                        <i class="fas fa-eye"></i> Aucun
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
            <i class="fas fa-info-circle"></i> Vous n'avez aucune réservation.
            <a href="search.php" class="alert-link">Rechercher un trajet</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
