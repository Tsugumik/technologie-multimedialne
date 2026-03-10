<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platforma Laboratoriów PHP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="flex-center py-5">
    <div class="container">
        <div class="glass-card text-center mx-auto" style="max-width: 800px;">
            <h1 class="mb-4 fw-bold">Platforma Laboratoriów</h1>
            <p class="mb-5 text-muted-custom fs-5">Wybierz laboratorium, do którego chcesz uzyskać dostęp. Pamiętaj, że każde laboratorium wymaga osobnego logowania i działa na odizolowanej bazie danych.</p>
            
            <div class="row g-4 justify-content-center">
                <div class="col-md-4">
                    <a href="/lab_test" class="btn lab-btn w-100 py-4 fs-4 fw-semibold d-flex flex-column align-items-center text-decoration-none">
                        <span style="font-size: 2.5rem; margin-bottom: 0.5rem;">🔬</span>
                        Lab Test
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="/z12" class="btn lab-btn w-100 py-4 fs-4 fw-semibold d-flex flex-column align-items-center text-decoration-none">
                        <span style="font-size: 2.5rem; margin-bottom: 0.5rem;">🚨</span>
                        Projekt Z12a
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="/z12b" class="btn lab-btn w-100 py-4 fs-4 fw-semibold d-flex flex-column align-items-center text-decoration-none">
                        <span style="font-size: 2.5rem; margin-bottom: 0.5rem;">🌐</span>
                        Projekt Z12b
                    </a>
                </div>
                <div class="col-md-4 mt-4">
                    <a href="/z13" class="btn lab-btn w-100 py-4 fs-4 fw-semibold d-flex flex-column align-items-center text-decoration-none">
                        <span style="font-size: 2.5rem; margin-bottom: 0.5rem;">📅</span>
                        Projekt Z13
                    </a>
                </div>
            </div>
            
            <div class="mt-5 text-muted-custom small">
                Zarządzanie bazami danych pod adresem: <a href="https://phpadmin.blazejdrozd.pl" class="text-info text-decoration-none" target="_blank">PhpMyAdmin</a>
            </div>
        </div>
    </div>
</body>
</html>
