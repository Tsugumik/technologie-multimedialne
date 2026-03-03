<?php
$model = new Measurement($pdo);
$data = $model->getAll();
?>
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th><th>X1</th><th>X2</th><th>X3</th><th>X4</th><th>X5</th>
                <th>Pożar</th><th>Zalanie</th><th>Wentylacja</th><th>Data / Godzina</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['x1']) ?></td>
                <td><?= htmlspecialchars($row['x2']) ?></td>
                <td><?= htmlspecialchars($row['x3']) ?></td>
                <td><?= htmlspecialchars($row['x4']) ?></td>
                <td><?= htmlspecialchars($row['x5']) ?></td>
                <td><?= $row['pozar'] ? '<span class="badge bg-danger">TAK</span>' : 'NIE' ?></td>
                <td><?= $row['zalanie'] ? '<span class="badge bg-primary">TAK</span>' : 'NIE' ?></td>
                <td>Bieg <?= htmlspecialchars($row['wentylacja']) ?></td>
                <td><?= htmlspecialchars($row['datetime']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>