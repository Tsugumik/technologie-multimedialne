<?php
$lab_name = 'z15';
$lab_title = 'Zadanie 15 - CRM';
$db_name = 'z15';
require_once '../shared/auth.php';
require_once '../shared/config.php';

if (!isset($_SESSION[$lab_name]) || $_SESSION[$lab_name]['role'] !== 'admin') {
    die("Brak dostępu.");
}

// Stats: Total queries
$total_tickets = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
// Total answers (count of employee messages)
$total_answers = $pdo->query("SELECT COUNT(*) FROM ticket_messages tm JOIN users u ON tm.sender_id = u.id WHERE u.role IN ('employee', 'admin')")->fetchColumn();

// Stats per employee
$employee_stats = $pdo->query("
    SELECT u.username, 
           COUNT(tm.id) as answer_count,
           AVG(tm.rating) as avg_rating
    FROM users u
    LEFT JOIN ticket_messages tm ON u.id = tm.sender_id
    WHERE u.role IN ('employee', 'admin')
    GROUP BY u.id
")->fetchAll();

// Calculate average answers for speed icons
$avg_answers = count($employee_stats) > 0 ? $total_answers / count($employee_stats) : 0;

function getSpeedIcon($count, $avg) {
    if ($count == 0) return '🐌 (Ślimak)';
    $ratio = $count / ($avg ?: 1);
    if ($ratio < 0.5) return '🐌 (Ślimak)';
    if ($ratio < 0.9) return '🐢 (Żółw)';
    if ($ratio < 1.2) return '👤 (Człowiek)';
    return '🐆 (Puma)';
}

// Stats per topic (answers per topic)
$topic_stats = $pdo->query("
    SELECT top.name, COUNT(tm.id) as answer_count
    FROM topics top
    LEFT JOIN tickets t ON top.id = t.topic_id
    LEFT JOIN ticket_messages tm ON t.id = tm.ticket_id
    LEFT JOIN users u ON tm.sender_id = u.id AND u.role IN ('employee', 'admin')
    GROUP BY top.id
")->fetchAll();

// Login logs
$login_logs = $pdo->query("SELECT * FROM login_logs ORDER BY login_time DESC LIMIT 50")->fetchAll();

require_once '../shared/header.php';
?>

<div class="row g-4">
    <div class="col-md-3">
        <div class="card bg-dark text-white border-light border-opacity-10 text-center p-3 shadow">
            <h6 class="text-info">Wszystkie zgłoszenia</h6>
            <h2 class="fw-bold"><?= $total_tickets ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark text-white border-light border-opacity-10 text-center p-3 shadow">
            <h6 class="text-info">Udzielone odpowiedzi</h6>
            <h2 class="fw-bold"><?= $total_answers ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark text-white border-light border-opacity-10 text-center p-3 shadow">
            <h6 class="text-info">Średnia odp. / pracownika</h6>
            <h2 class="fw-bold"><?= number_format($avg_answers, 1) ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark text-white border-light border-opacity-10 text-center p-3 shadow">
            <h6 class="text-info">Aktywne kategorie</h6>
            <h2 class="fw-bold"><?= count($topic_stats) ?></h2>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card bg-dark text-white border-light border-opacity-10 shadow">
            <div class="card-header border-light border-opacity-10">
                <h5 class="mb-0">Statystyki pracowników</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-dark table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Pracownik</th>
                            <th>Liczba odpowiedzi</th>
                            <th>Tempo pracy</th>
                            <th>Średnia ocena</th>
                            <th>Jakość pracy</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employee_stats as $emp): ?>
                            <tr>
                                <td><?= htmlspecialchars($emp['username']) ?></td>
                                <td><?= $emp['answer_count'] ?></td>
                                <td><?= getSpeedIcon($emp['answer_count'], $avg_answers) ?></td>
                                <td><?= $emp['avg_rating'] ? number_format($emp['avg_rating'], 2) : 'Brak ocen' ?></td>
                                <td class="text-warning">
                                    <?php 
                                    if ($emp['avg_rating']) {
                                        for($i=1; $i<=5; $i++) {
                                            echo $i <= round($emp['avg_rating']) ? '★' : '☆';
                                        }
                                    } else {
                                        echo '---';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark text-white border-light border-opacity-10 shadow">
            <div class="card-header border-light border-opacity-10">
                <h5 class="mb-0">Zgłoszenia wg kategorii</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-dark table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Kategoria</th>
                            <th>Liczba odpowiedzi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topic_stats as $top): ?>
                            <tr>
                                <td><?= htmlspecialchars($top['name']) ?></td>
                                <td><?= $top['answer_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card bg-dark text-white border-light border-opacity-10 shadow">
            <div class="card-header border-light border-opacity-10">
                <h5 class="mb-0">Historia logowania</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0" style="font-size: 0.85em;">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Użytkownik</th>
                                <th>Rola (próba)</th>
                                <th>Status</th>
                                <th>IP</th>
                                <th>System</th>
                                <th>Przeglądarka</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($login_logs as $log): ?>
                                <tr class="<?= $log['is_success'] ? '' : 'table-danger text-dark' ?>">
                                    <td><?= $log['login_time'] ?></td>
                                    <td><?= htmlspecialchars($log['username']) ?></td>
                                    <td><?= htmlspecialchars($log['role']) ?></td>
                                    <td><?= $log['is_success'] ? 'Sukces' : 'Błąd' ?></td>
                                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                    <td><?= htmlspecialchars($log['os']) ?></td>
                                    <td><?= htmlspecialchars($log['browser']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    <a href="employee.php" class="btn btn-outline-info">Przejdź do panelu pracownika</a>
</div>

<?php require_once '../shared/footer.php'; ?>
