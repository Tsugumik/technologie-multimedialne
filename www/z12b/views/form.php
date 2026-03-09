<form method="POST" action="index.php?page=form" class="card p-4 mx-auto" style="max-width: 500px;">
    <div class="mb-3">
        <label>X1 (Zasilanie):</label>
        <input type="number" step="0.1" name="x1" class="form-control" required min="-50" max="100">
    </div>
    <div class="mb-3">
        <label>X2 (Powrót):</label>
        <input type="number" step="0.1" name="x2" class="form-control" required min="-50" max="100">
    </div>
    <div class="mb-3">
        <label>X3 (Biuro 1):</label>
        <input type="number" step="0.1" name="x3" class="form-control" required min="-50" max="100">
    </div>
    <div class="mb-3">
        <label>X4 (Biuro 2):</label>
        <input type="number" step="0.1" name="x4" class="form-control" required min="-50" max="100">
    </div>
    <div class="mb-3">
        <label>X5 (Biuro 3):</label>
        <input type="number" step="0.1" name="x5" class="form-control" required min="-50" max="100">
    </div>
    
    <hr>
    <h5>Stany instalacji:</h5>
    <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" name="pozar" id="pozar">
        <label class="form-check-label" for="pozar">Alarm Pożarowy</label>
    </div>
    <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" name="zalanie" id="zalanie">
        <label class="form-check-label" for="zalanie">Zalanie</label>
    </div>
    <div class="mb-3 mt-3">
        <label>Wentylacja:</label>
        <select name="wentylacja" class="form-select">
            <option value="0">Wyłączona</option>
            <option value="1">Bieg 1 (Wolno)</option>
            <option value="2">Bieg 2 (Szybko)</option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary w-100">Wyślij dane do bazy</button>
</form>