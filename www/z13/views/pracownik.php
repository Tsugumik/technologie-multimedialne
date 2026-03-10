<?php
if (!isset($pdo) || !isset($user_id)) exit;

function getColorClass($stan) {
    if ($stan == 0) return 'text-danger';
    if ($stan == 100) return 'text-success';
    return 'text-dark';
}

// Pobieranie podzadań, których pracownik jest wykonawcą
$stmtMySubtasks = $pdo->prepare("
    SELECT z.idz, p.idpz, z.nazwa_zadania, pm.login AS manager, p.nazwa_podzadania, p.stan 
    FROM podzadanie p 
    JOIN zadanie z ON p.idz = z.idz 
    JOIN pracownik pm ON z.idp = pm.idp 
    WHERE p.idp = ?
");
$stmtMySubtasks->execute([$user_id]);
$mySubtasks = $stmtMySubtasks->fetchAll();

// Pobieranie zadań, których pracownik jest managerem
$stmtMyTasks = $pdo->prepare("SELECT idz, nazwa_zadania FROM zadanie WHERE idp = ?");
$stmtMyTasks->execute([$user_id]);
$myTasks = $stmtMyTasks->fetchAll();
// Pobieranie monity
$stmtMonity = $pdo->prepare("SELECT m.*, p.login as od_kogo FROM monit m JOIN pracownik p ON m.idp_od = p.idp WHERE m.idp_do = ? ORDER BY m.idm DESC");
$stmtMonity->execute([$user_id]);
$monity = $stmtMonity->fetchAll();
?>

<?php if (!empty($monity)): ?>
<div class="alert alert-warning mb-4">
    <h5 class="alert-heading">🔔 Otrzymane monity</h5>
    <ul class="mb-0">
        <?php foreach ($monity as $m): ?>
            <li><strong><?= htmlspecialchars($m['od_kogo']) ?> (<?= $m['data'] ?>):</strong> <?= htmlspecialchars($m['tresc']) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Twoje podzadania -->
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-info text-white fw-bold">
                Twoje podzadania
            </div>
            <div class="card-body">
                <h5 class="card-title text-muted mb-3">Zadanie (Project Manager) -> Podzadanie</h5>
                <ul class="list-group list-group-flush">
                    <?php if (empty($mySubtasks)): ?>
                        <li class="list-group-item text-muted">Brak przypisanych podzadań.</li>
                    <?php else: ?>
                        <?php foreach ($mySubtasks as $sub): ?>
                            <li class="list-group-item">
                                <span class="fw-bold <?= getColorClass($sub['stan']) ?>">
                                    <?= htmlspecialchars($sub['nazwa_zadania']) ?> (<?= htmlspecialchars($sub['manager']) ?>) 
                                    -> <?= htmlspecialchars($sub['nazwa_podzadania']) ?>
                                </span>
                                <div class="d-flex align-items-center gap-2 mt-2">
                                    <input type="range" class="form-range flex-grow-1 range-stan-wykonawca" min="0" max="100" value="<?= $sub['stan'] ?>" data-idpz="<?= $sub['idpz'] ?>" data-idz="<?= $sub['idz'] ?>">
                                    <span style="min-width: 50px;" class="text-end fw-bold" id="stan-label-wyk-<?= $sub['idpz'] ?>"><?= $sub['stan'] ?>%</span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Twoje zadania -->
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-primary text-white fw-bold d-flex justify-content-between align-items-center">
                Twoje zadania
                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#newTaskModal">Nowe zadanie</button>
            </div>
            <div class="card-body">
                <h5 class="card-title text-muted mb-3">Zadanie (Ty) -> Podzadanie (Wykonawca)</h5>
                
                <?php if (empty($myTasks)): ?>
                    <p class="text-muted">Nie zarządzasz żadnymi zadaniami.</p>
                <?php else: ?>
                    <div class="accordion" id="tasksAccordion">
                        <?php foreach ($myTasks as $task): 
                            $stmtSub = $pdo->prepare("SELECT p.idpz, p.idp, p.nazwa_podzadania, p.stan, wk.login as wykonawca FROM podzadanie p JOIN pracownik wk ON p.idp = wk.idp WHERE p.idz = ?");
                            $stmtSub->execute([$task['idz']]);
                            $subtasks = $stmtSub->fetchAll();
                            
                            $sum = 0;
                            $count = count($subtasks);
                            foreach ($subtasks as $s) $sum += $s['stan'];
                            $avg = $count > 0 ? round($sum / $count) : 0;
                        ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading<?= $task['idz'] ?>">
                                    <button class="accordion-button collapsed fw-bold <?= getColorClass($avg) ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $task['idz'] ?>">
                                        <?= htmlspecialchars($task['nazwa_zadania']) ?> (Ty) - <?= $avg ?>%
                                    </button>
                                </h2>
                                <div id="collapse<?= $task['idz'] ?>" class="accordion-collapse collapse" data-bs-parent="#tasksAccordion">
                                    <div class="accordion-body">
                                        <form method="POST" class="mb-3 d-flex gap-2">
                                            <input type="hidden" name="action" value="delete_task">
                                            <input type="hidden" name="idz" value="<?= $task['idz'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger w-100" onclick="return confirm('Usunąć zadanie i wszystkie podzadania?')">Usuń całe zadanie</button>
                                        </form>

                                        <ul class="list-group mb-3">
                                            <?php foreach ($subtasks as $sub): ?>
                                                <li class="list-group-item">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="<?= getColorClass($sub['stan']) ?>">
                                                            <?= htmlspecialchars($sub['nazwa_podzadania']) ?> (<?= htmlspecialchars($sub['wykonawca']) ?>)
                                                        </span>
                                                        <div class="d-flex gap-2">
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="action" value="send_monit">
                                                                <input type="hidden" name="idp_do" value="<?= $sub['idp'] ?>">
                                                                <input type="hidden" name="nazwa_podzadania" value="<?= htmlspecialchars($sub['nazwa_podzadania']) ?>">
                                                                <button type="submit" class="btn btn-sm btn-warning py-0 px-2" title="Wyślij monit" onclick="return confirm('Wysłać monit do wykonawcy?')">🔔</button>
                                                            </form>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="action" value="delete_subtask">
                                                                <input type="hidden" name="idpz" value="<?= $sub['idpz'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Usuń">&times;</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="d-flex align-items-center gap-2">
                                                        <input type="range" class="form-range flex-grow-1 range-stan" min="0" max="100" value="<?= $sub['stan'] ?>" data-idpz="<?= $sub['idpz'] ?>" data-idz="<?= $task['idz'] ?>">
                                                        <span style="min-width: 50px;" class="text-end stan-label" id="stan-label-<?= $sub['idpz'] ?>"><?= $sub['stan'] ?>%</span>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>

                                        <form method="POST" class="mt-3 border-top pt-3">
                                            <input type="hidden" name="action" value="add_subtask">
                                            <input type="hidden" name="idz" value="<?= $task['idz'] ?>">
                                            <h6 class="text-muted">Nowe podzadanie do istniejącego zadania</h6>
                                            <div class="input-group input-group-sm mb-2">
                                                <input type="text" name="nazwa_podzadania" class="form-control" placeholder="Nazwa podzadania" required>
                                            </div>
                                            <div class="input-group input-group-sm mb-2">
                                                <select name="idp" class="form-select" required>
                                                    <option value="">Wybierz wykonawcę...</option>
                                                    <?php foreach ($pracownicy as $p): ?>
                                                        <option value="<?= $p['idp'] ?>"><?= htmlspecialchars($p['login']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn btn-success">Dodaj</button>
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

<!-- Modal Nowe Zadanie -->
<div class="modal fade" id="newTaskModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content glass-card">
      <div class="modal-header border-0">
        <h5 class="modal-title">Utwórz nowe zadanie</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
          <div class="modal-body">
              <input type="hidden" name="action" value="create_task">
              <div class="mb-3">
                  <label class="form-label">Nazwa zadania</label>
                  <input type="text" name="nazwa_zadania" class="form-control" required placeholder="np. Modernizacja infrastruktury">
              </div>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
            <button type="submit" class="btn btn-primary-custom">Zapisz zadanie</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rangeInputs = document.querySelectorAll('input[type="range"]');
    
    function updateColor(element, stan) {
        element.classList.remove('text-danger', 'text-success', 'text-dark');
        if (stan == 0) {
            element.classList.add('text-danger');
        } else if (stan == 100) {
            element.classList.add('text-success');
        } else {
            element.classList.add('text-dark');
        }
    }

    rangeInputs.forEach(input => {
        input.addEventListener('input', function() {
            const idpz = this.getAttribute('data-idpz');
            const stan = this.value;
            
            // Aktualizacja tylko etykiety przy przesuwaniu
            const isWyk = this.classList.contains('range-stan-wykonawca');
            const labelId = isWyk ? `stan-label-wyk-${idpz}` : `stan-label-${idpz}`;
            const label = document.getElementById(labelId);
            if (label) {
                label.textContent = `${stan}%`;
            }
        });

        input.addEventListener('change', function() {
            const idpz = this.getAttribute('data-idpz');
            const idz = this.getAttribute('data-idz');
            const stan = this.value;
            const isWyk = this.classList.contains('range-stan-wykonawca');
            
            // Wysyłanie AJAX po puszczeniu suwaka
            fetch('api_update_stan.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `idpz=${idpz}&stan=${stan}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Aktualizacja koloru podzadania
                    const listItem = this.closest('.list-group-item');
                    if (listItem) {
                        const titleSpan = listItem.querySelector('span[class*="text-"]');
                        if (titleSpan) {
                            updateColor(titleSpan, stan);
                        }
                    }

                    // Aktualizacja tytułu akordeonu tylko dla managerów
                    if (!isWyk) {
                        const accordionBtn = document.querySelector(`#heading${idz} .accordion-button`);
                        if (accordionBtn) {
                            const currentText = accordionBtn.textContent.trim();
                            const taskName = currentText.split('(Ty)')[0].trim();
                            accordionBtn.textContent = `${taskName} (Ty) - ${data.avg_stan}%`;
                            updateColor(accordionBtn, data.avg_stan);
                        }
                    }
                } else {
                    alert('Błąd podczas zapisywania: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas zapisywania');
            });
        });
    });
});
</script>
