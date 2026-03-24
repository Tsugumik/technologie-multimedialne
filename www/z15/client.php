<?php
if (!isset($lab_name) || $_SESSION[$lab_name]['role'] !== 'client') {
    die("Brak dostępu.");
}

require_once '../shared/config.php';

$client_id = $_SESSION[$lab_name]['user_id'];
$error = '';
$success = '';

// Handle new ticket
if (isset($_POST['action']) && $_POST['action'] === 'new_ticket') {
    $topic_id = $_POST['topic_id'] ?? 0;
    $message = $_POST['message'] ?? '';

    if ($topic_id > 0 && !empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO tickets (client_id, topic_id) VALUES (?, ?)");
        $stmt->execute([$client_id, $topic_id]);
        $ticket_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$ticket_id, $client_id, $message]);
        $success = "Zgłoszenie zostało wysłane.";
    } else {
        $error = "Wypełnij wszystkie pola.";
    }
}

// Handle reply to existing ticket
if (isset($_POST['action']) && $_POST['action'] === 'reply') {
    $ticket_id = $_POST['ticket_id'] ?? 0;
    $message = $_POST['message'] ?? '';

    if ($ticket_id > 0 && !empty($message)) {
        // Check if ticket belongs to client
        $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ? AND client_id = ?");
        $stmt->execute([$ticket_id, $client_id]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$ticket_id, $client_id, $message]);
            
            // Reopen ticket if it was answered
            $stmt = $pdo->prepare("UPDATE tickets SET status = 'open' WHERE id = ?");
            $stmt->execute([$ticket_id]);
            
            $success = "Odpowiedź została wysłana.";
        }
    }
}

// Handle rating
if (isset($_POST['action']) && $_POST['action'] === 'rate') {
    $message_id = $_POST['message_id'] ?? 0;
    $rating = $_POST['rating'] ?? 0;

    if ($message_id > 0 && $rating >= 1 && $rating <= 5) {
        // Check if message belongs to a ticket of this client AND it's from an employee
        $stmt = $pdo->prepare("
            SELECT tm.* FROM ticket_messages tm 
            JOIN tickets t ON tm.ticket_id = t.id 
            WHERE tm.id = ? AND t.client_id = ? AND tm.sender_id != ? AND tm.rating IS NULL
        ");
        $stmt->execute([$message_id, $client_id, $client_id]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE ticket_messages SET rating = ? WHERE id = ?");
            $stmt->execute([$rating, $message_id]);
            $success = "Dziękujemy za ocenę.";
        }
    }
}

// Fetch topics
$topics = $pdo->query("SELECT * FROM topics")->fetchAll();

// Fetch client's tickets
$stmt = $pdo->prepare("
    SELECT t.*, top.name as topic_name 
    FROM tickets t 
    JOIN topics top ON t.topic_id = top.id 
    WHERE t.client_id = ? 
    ORDER BY t.created_at DESC
");
$stmt->execute([$client_id]);
$tickets = $stmt->fetchAll();

require_once '../shared/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card bg-dark text-white border-light border-opacity-10 mb-4 shadow">
            <div class="card-header border-light border-opacity-10">
                <h5 class="mb-0">Nowe zgłoszenie</h5>
            </div>
            <div class="card-body">
                <?php if ($success && !isset($_POST['ticket_id'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="action" value="new_ticket">
                    <div class="mb-3">
                        <label class="form-label">Zagadnienie</label>
                        <select name="topic_id" class="form-select bg-dark text-white border-light border-opacity-25" required>
                            <option value="">Wybierz...</option>
                            <?php foreach ($topics as $topic): ?>
                                <option value="<?= $topic['id'] ?>"><?= htmlspecialchars($topic['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pytanie</label>
                        <textarea name="message" class="form-control bg-dark text-white border-light border-opacity-25" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-info fw-bold w-100">Wyślij zgłoszenie</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card bg-dark text-white border-light border-opacity-10 shadow">
            <div class="card-header border-light border-opacity-10">
                <h5 class="mb-0">Twoje zgłoszenia (Komunikator)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($tickets)): ?>
                    <p class="text-white-50">Nie masz jeszcze żadnych zgłoszeń.</p>
                <?php else: ?>
                    <div class="accordion accordion-flush" id="ticketsAccordion">
                        <?php foreach ($tickets as $ticket): ?>
                            <div class="accordion-item bg-transparent border-light border-opacity-10">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed bg-dark text-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#ticket-<?= $ticket['id'] ?>">
                                        <div class="d-flex justify-content-between w-100 me-3">
                                            <span>
                                                <strong>#<?= $ticket['id'] ?>: <?= htmlspecialchars($ticket['topic_name']) ?></strong>
                                                <small class="text-white-50 ms-2"><?= $ticket['created_at'] ?></small>
                                            </span>
                                            <span class="badge <?= $ticket['status'] === 'answered' ? 'bg-success' : ($ticket['status'] === 'open' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                                <?= $ticket['status'] === 'answered' ? 'Odpowiedziano' : ($ticket['status'] === 'open' ? 'Oczekiwanie' : 'Zamknięte') ?>
                                            </span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="ticket-<?= $ticket['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#ticketsAccordion">
                                    <div class="accordion-body bg-dark bg-opacity-50">
                                        <?php
                                        $stmt = $pdo->prepare("
                                            SELECT tm.*, u.username, u.role 
                                            FROM ticket_messages tm 
                                            JOIN users u ON tm.sender_id = u.id 
                                            WHERE tm.ticket_id = ? 
                                            ORDER BY tm.created_at ASC
                                        ");
                                        $stmt->execute([$ticket['id']]);
                                        $messages = $stmt->fetchAll();
                                        ?>
                                        <div class="chat-history mb-4">
                                            <?php foreach ($messages as $msg): ?>
                                                <div class="mb-3 <?= $msg['sender_id'] == $client_id ? 'text-end' : '' ?>">
                                                    <div class="d-inline-block p-3 rounded-3 <?= $msg['sender_id'] == $client_id ? 'bg-info text-dark' : 'bg-secondary text-white' ?>" style="max-width: 80%;">
                                                        <div class="fw-bold small mb-1"><?= htmlspecialchars($msg['username']) ?> (<?= $msg['role'] == 'client' ? 'Ty' : 'Pracownik' ?>)</div>
                                                        <div><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                                                        <div class="small mt-1 opacity-75"><?= $msg['created_at'] ?></div>
                                                        
                                                        <?php if ($msg['sender_id'] != $client_id && $msg['role'] != 'client'): ?>
                                                            <div class="mt-2 border-top border-light border-opacity-25 pt-2">
                                                                <?php if ($msg['rating']): ?>
                                                                    <div class="text-warning">
                                                                        Ocena: <?php for($i=0; $i<$msg['rating']; $i++) echo '★'; ?>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <form method="POST" class="d-flex align-items-center">
                                                                        <input type="hidden" name="action" value="rate">
                                                                        <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                                                        <select name="rating" class="form-select form-select-sm bg-dark text-white border-light border-opacity-25 me-2" style="width: auto;">
                                                                            <option value="5">5 gwiazdek</option>
                                                                            <option value="4">4 gwiazdki</option>
                                                                            <option value="3">3 gwiazdki</option>
                                                                            <option value="2">2 gwiazdki</option>
                                                                            <option value="1">1 gwiazdka</option>
                                                                        </select>
                                                                        <button type="submit" class="btn btn-warning btn-sm">Oceń</button>
                                                                    </form>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <hr class="border-light border-opacity-10">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="reply">
                                            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                            <div class="input-group">
                                                <input type="text" name="message" class="form-control bg-dark text-white border-light border-opacity-25" placeholder="Napisz kolejną wiadomość..." required>
                                                <button type="submit" class="btn btn-info">Wyślij</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../shared/footer.php'; ?>
