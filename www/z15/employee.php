<?php
$lab_name = 'z15';
$lab_title = 'Zadanie 15 - CRM';
$db_name = 'z15';
require_once '../shared/auth.php';
require_once '../shared/config.php';

if (!isset($_SESSION[$lab_name]) || !in_array($_SESSION[$lab_name]['role'], ['employee', 'admin'])) {
    die("Brak dostępu.");
}

$user_id = $_SESSION[$lab_name]['user_id'];
$user_role = $_SESSION[$lab_name]['role'];

// Fetch employee's specialization
$stmt = $pdo->prepare("SELECT specialization_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_spec = $stmt->fetchColumn();

// If admin and no specialization, maybe they want to see all?
if (isset($_POST['set_specialization'])) {
    $new_spec = !empty($_POST['specialization_id']) ? $_POST['specialization_id'] : null;
    $stmt = $pdo->prepare("UPDATE users SET specialization_id = ? WHERE id = ?");
    $stmt->execute([$new_spec, $user_id]);
    $current_spec = $new_spec;
}

// Handle reply
if (isset($_POST['action']) && $_POST['action'] === 'reply') {
    $ticket_id = $_POST['ticket_id'] ?? 0;
    $message = $_POST['message'] ?? '';

    if ($ticket_id > 0 && !empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$ticket_id, $user_id, $message]);
        
        $stmt = $pdo->prepare("UPDATE tickets SET status = 'answered', employee_id = ? WHERE id = ?");
        $stmt->execute([$user_id, $ticket_id]);
        
        $success = "Odpowiedź została wysłana.";
    }
}

// Fetch all topics for specialization selection
$topics = $pdo->query("SELECT * FROM topics")->fetchAll();

// Fetch tickets for current specialization
$tickets = [];
if ($current_spec) {
    $stmt = $pdo->prepare("
        SELECT t.*, u.username as client_name, top.name as topic_name 
        FROM tickets t 
        JOIN users u ON t.client_id = u.id 
        JOIN topics top ON t.topic_id = top.id 
        WHERE t.topic_id = ? 
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$current_spec]);
    $tickets = $stmt->fetchAll();
}

require_once '../shared/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card bg-dark text-white border-light border-opacity-10 mb-4 shadow">
            <div class="card-header border-light border-opacity-10">
                <h5 class="mb-0">Twoja specjalizacja</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="set_specialization" value="1">
                    <div class="mb-3">
                        <select name="specialization_id" class="form-select bg-dark text-white border-light border-opacity-25" onchange="this.form.submit()">
                            <option value="">Wybierz...</option>
                            <?php foreach ($topics as $topic): ?>
                                <option value="<?= $topic['id'] ?>" <?= $current_spec == $topic['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($topic['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
                <p class="small text-white-50">Wybierz kategorię, w której chcesz pomagać klientom.</p>
            </div>
            <?php if ($user_role === 'admin'): ?>
                <div class="card-footer border-light border-opacity-10">
                    <a href="admin.php" class="btn btn-outline-info btn-sm w-100">Wróć do panelu admina</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card bg-dark text-white border-light border-opacity-10 shadow">
            <div class="card-header border-light border-opacity-10">
                <h5 class="mb-0">Zgłoszenia do obsłużenia</h5>
            </div>
            <div class="card-body">
                <?php if (!$current_spec): ?>
                    <div class="alert alert-info">Wybierz specjalizację, aby zobaczyć zgłoszenia.</div>
                <?php elseif (empty($tickets)): ?>
                    <p class="text-white-50">Brak zgłoszeń w tej kategorii.</p>
                <?php else: ?>
                    <div class="accordion accordion-flush" id="employeeTickets">
                        <?php foreach ($tickets as $ticket): ?>
                            <div class="accordion-item bg-transparent border-light border-opacity-10">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed bg-dark text-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#ticket-<?= $ticket['id'] ?>">
                                        <div class="d-flex justify-content-between w-100 me-3">
                                            <span>
                                                <strong>#<?= $ticket['id'] ?> (<?= htmlspecialchars($ticket['client_name']) ?>): <?= htmlspecialchars($ticket['topic_name']) ?></strong>
                                                <small class="text-white-50 ms-2"><?= $ticket['created_at'] ?></small>
                                            </span>
                                            <span class="badge <?= $ticket['status'] === 'answered' ? 'bg-success' : ($ticket['status'] === 'open' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                                <?= $ticket['status'] === 'answered' ? 'Obsłużone' : ($ticket['status'] === 'open' ? 'Nowe/Otwarty' : 'Zamknięte') ?>
                                            </span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="ticket-<?= $ticket['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#employeeTickets">
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
                                                <div class="mb-3 <?= $msg['sender_id'] == $user_id ? 'text-end' : '' ?>">
                                                    <div class="d-inline-block p-3 rounded-3 <?= $msg['sender_id'] == $user_id ? 'bg-info text-dark' : 'bg-secondary text-white' ?>" style="max-width: 80%;">
                                                        <div class="fw-bold small mb-1"><?= htmlspecialchars($msg['username']) ?> (<?= $msg['role'] == 'client' ? 'Klient' : 'Ty' ?>)</div>
                                                        <div><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                                                        <div class="small mt-1 opacity-75"><?= $msg['created_at'] ?></div>
                                                        <?php if ($msg['rating']): ?>
                                                            <div class="mt-2 border-top border-light border-opacity-25 pt-2 text-warning">
                                                                Ocena klienta: <?php for($i=0; $i<$msg['rating']; $i++) echo '★'; ?>
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
                                            <div class="mb-3">
                                                <textarea name="message" class="form-control bg-dark text-white border-light border-opacity-25" rows="3" placeholder="Twoja odpowiedź..." required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-success fw-bold w-100">Wyślij odpowiedź</button>
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
