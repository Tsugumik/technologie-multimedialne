<?php
if (!isset($pdo)) exit;

$logsAll = $pdo->query("SELECT l.*, p.login FROM logowanie l LEFT JOIN pracownik p ON l.idp = p.idp ORDER BY l.idl DESC LIMIT 100")->fetchAll();
$logsFailed = $pdo->query("SELECT l.*, p.login FROM logowanie l LEFT JOIN pracownik p ON l.idp = p.idp WHERE l.state != 0 ORDER BY l.idl DESC LIMIT 100")->fetchAll();
$allTasks = $pdo->query("
    SELECT z.nazwa_zadania, pm.login AS manager, p.nazwa_podzadania, p.stan, wk.login AS wykonawca 
    FROM zadanie z
    JOIN pracownik pm ON z.idp = pm.idp
    LEFT JOIN podzadanie p ON z.idz = p.idz
    LEFT JOIN pracownik wk ON p.idp = wk.idp
    ORDER BY z.idz, p.idpz
")->fetchAll();

$employeeStatsRaw = $pdo->query("
    SELECT p.idp, p.login, COALESCE(AVG(pz.stan), 0) as avg_stan 
    FROM pracownik p 
    LEFT JOIN podzadanie pz ON p.idp = pz.idp 
    WHERE p.login != 'admin'
    GROUP BY p.idp, p.login
")->fetchAll();

$totalAvgSum = 0;
$totalCount = 0;
foreach ($employeeStatsRaw as $stat) {
    if ($stat['avg_stan'] > 0 || $pdo->query("SELECT COUNT(*) FROM podzadanie WHERE idp = " . $stat['idp'])->fetchColumn() > 0) {
        $totalAvgSum += $stat['avg_stan'];
        $totalCount++;
    }
}
$teamAverage = $totalCount > 0 ? $totalAvgSum / $totalCount : 0;

function getSpeedIcon($userAvg, $teamAvg) {
    if ($teamAvg == 0) return '🚶'; // default
    $ratio = $userAvg / $teamAvg;
    if ($ratio < 0.5) return '🐌'; // ślimak
    if ($ratio < 0.9) return '🐢'; // żółw
    if ($ratio <= 1.1) return '🚶'; // człowiek
    return '🐆'; // puma
}
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white">Statystyki pracowników (Średnia zespołu: <?= round($teamAverage, 2) ?>%)</div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead><tr><th>Pracownik</th><th>Średni stopień realizacji podzadań</th><th>Szybkość</th></tr></thead>
                    <tbody>
                        <?php foreach ($employeeStatsRaw as $stat): ?>
                        <tr>
                            <td><?= htmlspecialchars($stat['login']) ?></td>
                            <td><?= round($stat['avg_stan'], 2) ?>%</td>
                            <td class="fs-4"><?= getSpeedIcon($stat['avg_stan'], $teamAverage) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white">Wszystkie zadania i podzadania</div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead><tr><th>Zadanie</th><th>Manager</th><th>Podzadanie</th><th>Wykonawca</th><th>Stan</th></tr></thead>
                    <tbody>
                        <?php foreach ($allTasks as $task): ?>
                        <tr>
                            <td><?= htmlspecialchars($task['nazwa_zadania']) ?></td>
                            <td><?= htmlspecialchars($task['manager']) ?></td>
                            <td><?= htmlspecialchars($task['nazwa_podzadania'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($task['wykonawca'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($task['stan'] ?? '-') ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-secondary text-white">Ostatnie logowania (wszystkie)</div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-sm">
                    <thead><tr><th>Czas</th><th>Konto</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($logsAll as $log): ?>
                        <tr>
                            <td><?= $log['datetime'] ?></td>
                            <td><?= htmlspecialchars($log['login'] ?? 'Nieznane (id: ' . $log['idp'] . ')') ?></td>
                            <td><?= $log['state'] === 0 ? '<span class="text-success">Sukces</span>' : '<span class="text-danger">Błąd ('.$log['state'].')</span>' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-danger text-white">Nieudane próby logowania</div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-sm">
                    <thead><tr><th>Czas</th><th>Konto</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($logsFailed as $log): ?>
                        <tr>
                            <td><?= $log['datetime'] ?></td>
                            <td><?= htmlspecialchars($log['login'] ?? 'Nieznane (id: ' . $log['idp'] . ')') ?></td>
                            <td><span class="text-danger">Błąd (<?= $log['state'] ?>)</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
