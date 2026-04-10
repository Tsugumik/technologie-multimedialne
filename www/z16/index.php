<?php
$lab_name = 'z16';
$lab_title = 'Zadanie 16 - CMS';
$db_name = 'z16';
require_once '../shared/config.php';
require_once 'helpers.php';

session_start();

// Portal selection (default to 1)
$id_cms = $_GET['id_cms'] ?? ($_SESSION['current_id_cms'] ?? 1);
$_SESSION['current_id_cms'] = $id_cms;

$stmt = $pdo->prepare("SELECT * FROM cms WHERE id_cms = ?");
$stmt->execute([$id_cms]);
$cms = $stmt->fetch();

if (!$cms) {
    die("Portal nie istnieje.");
}

$isAdmin = isset($_SESSION[$lab_name]['admin_id']) && $_SESSION[$lab_name]['id_cms'] == $id_cms;

// Handle Content Save (AJAX/POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin && isset($_POST['action']) && $_POST['action'] === 'save_content') {
    $field = $_POST['field'];
    $content = $_POST['content'];
    
    $allowed_fields = ['about_company', 'contact', 'google_map_link', 'offer'];
    if (in_array($field, $allowed_fields)) {
        $stmt = $pdo->prepare("UPDATE cms SET $field = ? WHERE id_cms = ?");
        $stmt->execute([$content, $id_cms]);
        echo "Saved";
        exit;
    }
}

// Handle Logo Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin && isset($_FILES['logo'])) {
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir);
    
    $filename = time() . '_' . basename($_FILES['logo']['name']);
    $target = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['logo']['tmp_name'], $target)) {
        $stmt = $pdo->prepare("UPDATE cms SET logo_file = ? WHERE id_cms = ?");
        $stmt->execute([$target, $id_cms]);
        header("Location: index.php");
        exit;
    }
}

// Handle Chatbot
$bot_response = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bot_question'])) {
    $bot_response = getChatbotResponse($pdo, $id_cms, $_POST['bot_question'], $cms);
}

$current_view = $_GET['view'] ?? 'about';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($cms['url'] ?? 'Portal') ?> - CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <style>
        :root {
            --sidebar-width: 280px;
        }
        body {
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            color: white;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        .sidebar {
            width: var(--sidebar-width);
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            position: fixed;
            height: 100vh;
            padding: 2rem 1rem;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 3rem;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 2rem;
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.7);
            padding: 0.8rem 1rem;
            border-radius: 10px;
            margin-bottom: 0.5rem;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: #00d2ff;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .logo-img {
            max-width: 100%;
            max-height: 60px;
        }
        .ck-editor__editable {
            min-height: 300px;
            color: black !important;
        }
        .animated-svg-text {
            font-size: 24px;
            font-weight: bold;
            fill: none;
            stroke: #00d2ff;
            stroke-width: 1px;
            stroke-dasharray: 500;
            stroke-dashoffset: 500;
            animation: draw 5s infinite;
        }
        @keyframes draw {
            to { stroke-dashoffset: 0; fill: #00d2ff; }
        }
        .chat-history {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 1rem;
        }
        .chat-item {
            margin-bottom: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            background: rgba(255,255,255,0.05);
        }
        .chat-user { font-weight: bold; color: #00d2ff; }
        .chat-bot { font-weight: bold; color: #ff00ea; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo-container">
            <?php if ($cms['logo_file']): ?>
                <img src="<?= htmlspecialchars($cms['logo_file']) ?>" alt="Logo" class="logo-img">
            <?php else: ?>
                <svg width="200" height="60" viewBox="0 0 200 60">
                    <text x="10" y="40" class="animated-svg-text">MEGA FIRMA</text>
                </svg>
            <?php endif; ?>
            
            <?php if ($isAdmin): ?>
                <form method="POST" enctype="multipart/form-data" class="mt-2">
                    <input type="file" name="logo" class="form-control form-control-sm bg-dark text-white border-secondary mb-1" onchange="this.form.submit()">
                    <small class="text-white-50">Zmień logo</small>
                </form>
            <?php endif; ?>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link <?= $current_view === 'about' ? 'active' : '' ?>" href="?view=about">O firmie</a>
            <a class="nav-link <?= $current_view === 'contact' ? 'active' : '' ?>" href="?view=contact">Kontakt</a>
            <a class="nav-link <?= $current_view === 'map' ? 'active' : '' ?>" href="?view=map">Jak do nas dotrzeć</a>
            <a class="nav-link <?= $current_view === 'offer' ? 'active' : '' ?>" href="?view=offer">Oferta</a>
            <a class="nav-link <?= $current_view === 'chatbot' ? 'active' : '' ?>" href="?view=chatbot">Chatbot</a>
            <a class="nav-link <?= $current_view === 'history' ? 'active' : '' ?>" href="?view=history">Historia Chatbota</a>
            
            <?php if ($isAdmin): ?>
                <a class="nav-link <?= $current_view === 'logins' ? 'active' : '' ?>" href="?view=logins">Historia Logowań</a>
            <?php endif; ?>
        </nav>

        <div class="mt-4 px-2">
            <div id="google_translate_element"></div>
            <script type="text/javascript">
                function googleTranslateElementInit() {
                    new google.translate.TranslateElement({pageLanguage: 'pl', layout: google.translate.TranslateElement.InlineLayout.SIMPLE}, 'google_translate_element');
                }
            </script>
            <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
        </div>
        
        <div class="mt-auto pt-4 border-top border-light border-opacity-10">
            <?php if ($isAdmin): ?>
                <div class="px-3 mb-2">
                    <small class="text-info">Admin: <?= htmlspecialchars($_SESSION[$lab_name]['username']) ?></small>
                </div>
                <a href="logout.php" class="btn btn-outline-danger btn-sm w-100 rounded-pill">Logout Admin</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline-info btn-sm w-100 rounded-pill">Admin logowanie</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="main-content">
        <div class="glass-card">
            <?php if ($current_view === 'about'): ?>
                <h2>O firmie</h2>
                <div id="content-area"><?= $cms['about_company'] ?></div>
                <?php if ($isAdmin): ?>
                    <button class="btn btn-primary btn-sm mt-3" onclick="enableEdit('about_company')">Edytuj</button>
                <?php endif; ?>

            <?php elseif ($current_view === 'contact'): ?>
                <h2>Kontakt</h2>
                <div id="content-area"><?= $cms['contact'] ?></div>
                <?php if ($isAdmin): ?>
                    <button class="btn btn-primary btn-sm mt-3" onclick="enableEdit('contact')">Edytuj</button>
                <?php endif; ?>

            <?php elseif ($current_view === 'map'): ?>
                <h2>Jak do nas dotrzeć</h2>
                <div class="ratio ratio-16x9 mb-3">
                    <iframe src="<?= htmlspecialchars($cms['google_map_link']) ?>" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
                <?php if ($isAdmin): ?>
                    <div class="mt-3">
                        <label>Link do mapy (Embed URL):</label>
                        <input type="text" id="map_link_input" class="form-control bg-dark text-white border-secondary mb-2" value="<?= htmlspecialchars($cms['google_map_link']) ?>">
                        <button class="btn btn-primary btn-sm" onclick="saveMap()">Zapisz Mapę</button>
                    </div>
                <?php endif; ?>

            <?php elseif ($current_view === 'offer'): ?>
                <h2>Oferta</h2>
                <div id="content-area"><?= $cms['offer'] ?></div>
                <?php if ($isAdmin): ?>
                    <button class="btn btn-primary btn-sm mt-3" onclick="enableEdit('offer')">Edytuj</button>
                <?php endif; ?>

            <?php elseif ($current_view === 'chatbot'): ?>
                <h2>Chatbot</h2>
                <div class="row">
                    <div class="col-md-8">
                        <div class="chat-history border border-light border-opacity-10 p-3 rounded mb-3">
                            <?php if ($bot_response): ?>
                                <div class="chat-item">
                                    <span class="chat-user">Ty:</span> <?= htmlspecialchars($_POST['bot_question']) ?>
                                </div>
                                <div class="chat-item">
                                    <span class="chat-bot">Bot:</span> <?= $bot_response ?>
                                </div>
                            <?php else: ?>
                                <p class="text-white-50">Zadaj pytanie chatbotowi...</p>
                            <?php endif; ?>
                        </div>
                        <form method="POST">
                            <div class="input-group">
                                <input type="text" name="bot_question" class="form-control bg-dark text-white border-secondary" placeholder="Wpisz tu swoje pytanie..." required>
                                <button class="btn btn-primary" type="submit">Zapytaj</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4 text-center">
                        <svg width="150" height="150" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="45" fill="rgba(0, 210, 255, 0.2)" stroke="#00d2ff" stroke-width="2" />
                            <circle cx="35" cy="40" r="5" fill="#00d2ff">
                                <animate attributeName="cy" values="40;38;40" dur="2s" repeatCount="indefinite" />
                            </circle>
                            <circle cx="65" cy="40" r="5" fill="#00d2ff">
                                <animate attributeName="cy" values="40;38;40" dur="2s" repeatCount="indefinite" />
                            </circle>
                            <path d="M 30 70 Q 50 85 70 70" fill="none" stroke="#00d2ff" stroke-width="3" stroke-linecap="round">
                                <animate attributeName="d" values="M 30 70 Q 50 85 70 70; M 30 70 Q 50 75 70 70; M 30 70 Q 50 85 70 70" dur="2s" repeatCount="indefinite" />
                            </path>
                        </svg>
                        <p class="mt-2 small text-white-50">Twój asystent</p>
                    </div>
                </div>

            <?php elseif ($current_view === 'history'): ?>
                <h2>Historia Chatbota</h2>
                <div class="table-responsive">
                    <table class="table table-dark table-hover table-striped border-light border-opacity-10">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>IP</th>
                                <th>Pytanie</th>
                                <th>Odpowiedź</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM chatbot WHERE id_cms = ? ORDER BY id DESC");
                            $stmt->execute([$id_cms]);
                            while ($row = $stmt->fetch()):
                            ?>
                            <tr>
                                <td class="small"><?= $row['datetime'] ?></td>
                                <td class="small"><?= $row['question_ip'] ?></td>
                                <td><?= htmlspecialchars($row['question']) ?></td>
                                <td><?= htmlspecialchars($row['answer']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($current_view === 'logins' && $isAdmin): ?>
                <h2>Historia Logowań</h2>
                <div class="table-responsive">
                    <table class="table table-dark table-hover table-striped border-light border-opacity-10">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Użytkownik</th>
                                <th>IP</th>
                                <th>Sukces</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM login_history WHERE id_cms = ? ORDER BY id DESC");
                            $stmt->execute([$id_cms]);
                            while ($row = $stmt->fetch()):
                            ?>
                            <tr>
                                <td><?= $row['datetime'] ?></td>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><?= $row['ip_address'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $row['success'] ? 'success' : 'danger' ?>">
                                        <?= $row['success'] ? 'Tak' : 'Nie' ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let editor;

        function enableEdit(field) {
            if (editor) return;
            
            ClassicEditor
                .create(document.querySelector('#content-area'))
                .then(newEditor => {
                    editor = newEditor;
                    const saveBtn = document.createElement('button');
                    saveBtn.className = 'btn btn-success btn-sm mt-3 me-2';
                    saveBtn.innerText = 'Zapisz';
                    saveBtn.onclick = () => saveContent(field);
                    
                    const cancelBtn = document.createElement('button');
                    cancelBtn.className = 'btn btn-secondary btn-sm mt-3';
                    cancelBtn.innerText = 'Anuluj';
                    cancelBtn.onclick = () => location.reload();
                    
                    document.querySelector('#content-area').parentNode.appendChild(saveBtn);
                    document.querySelector('#content-area').parentNode.appendChild(cancelBtn);
                    
                    // Hide Edit button
                    event.target.style.display = 'none';
                })
                .catch(error => {
                    console.error(error);
                });
        }

        function saveContent(field) {
            const data = editor.getData();
            const formData = new FormData();
            formData.append('action', 'save_content');
            formData.append('field', field);
            formData.append('content', data);

            fetch('index.php?id_cms=<?= $id_cms ?>', {
                method: 'POST',
                body: formData
            }).then(response => {
                location.reload();
            });
        }

        function saveMap() {
            const link = document.getElementById('map_link_input').value;
            const formData = new FormData();
            formData.append('action', 'save_content');
            formData.append('field', 'google_map_link');
            formData.append('content', link);

            fetch('index.php?id_cms=<?= $id_cms ?>', {
                method: 'POST',
                body: formData
            }).then(response => {
                location.reload();
            });
        }
    </script>
</body>
</html>
