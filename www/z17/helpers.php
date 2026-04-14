<?php

function getProfanityList() {
    static $profanity = null;
    if ($profanity === null) {
        $filePath = __DIR__ . '/wulgaryzmy.php';
        if (file_exists($filePath)) {
            include $filePath;
            $profanity = isset($wulgaryzmy_pl) ? $wulgaryzmy_pl : [];
        } else {
            $profanity = [];
        }
    }
    return $profanity;
}

function getMaliciousDomains() {
    static $domains = null;
    if ($domains === null) {
        $filePath = __DIR__ . '/domains.txt';
        if (file_exists($filePath)) {
            $list = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            // Use array_flip for O(1) lookup
            $domains = array_flip($list);
        } else {
            $domains = [];
        }
    }
    return $domains;
}

function containsProfanity($text) {
    $profanity = getProfanityList();
    $text = mb_strtolower($text);
    foreach ($profanity as $word) {
        if (!empty($word) && mb_strpos($text, $word) !== false) {
            return true;
        }
    }
    return false;
}

function cleanLinks($text) {
    $domains = getMaliciousDomains();
    if (empty($domains)) return $text;

    $pattern = '/(https?:\/\/[^\s]+)/i';
    
    return preg_replace_callback($pattern, function($matches) use ($domains) {
        $url = $matches[0];
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) return $url;

        $host = strtolower($host);
        
        // Check exact host and all its parent domains
        $parts = explode('.', $host);
        while (count($parts) >= 2) {
            $check = implode('.', $parts);
            if (isset($domains[$check])) {
                return date('Y-m-d H:i') . ' Usunięto niebezpieczny link';
            }
            array_shift($parts);
        }
        
        return $url;
    }, $text);
}

function logLogin($pdo, $user_id, $username, $success) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, username, ip, success) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $username, $ip, $success ? 1 : 0]);
}

function handlePostSubmission($pdo, $topic_id, $author_id, $content, $file_path, $username) {
    $content = cleanLinks($content);
    
    if (containsProfanity($content)) {
        // Increment swear count
        $stmt = $pdo->prepare("UPDATE users SET swear_count = swear_count + 1 WHERE id = ?");
        $stmt->execute([$author_id]);
        
        // Get new count
        $stmt = $pdo->prepare("SELECT swear_count FROM users WHERE id = ?");
        $stmt->execute([$author_id]);
        $user = $stmt->fetch();
        
        $msg = "";
        $is_banned = 0;
        if ($user['swear_count'] == 1) {
            $msg = date('Y-m-d H:i') . " Usunięto post użytkownika $username, ze względu na użyty wulgaryzm. Jest to oficjalne ostrzeżenie dla użytkownika $username, przy kolejnym użytym wulgaryzmie użytkownik ten zostanie zabanowany.";
        } else {
            $msg = date('Y-m-d H:i') . " Usunięto post użytkownika $username, ze względu na użyty wulgaryzm. Użytkownik $username został zabanowany z powodu używania wulgaryzmów.";
            $is_banned = 1;
            $stmt = $pdo->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
            $stmt->execute([$author_id]);
        }
        
        // Add system message instead of user post
        $stmt = $pdo->prepare("INSERT INTO posts (topic_id, author_id, content, is_system_msg) VALUES (?, NULL, ?, 1)");
        $stmt->execute([$topic_id, $msg]);
        
        if ($is_banned) {
            session_destroy();
            return ['status' => 'banned', 'msg' => $msg];
        }
        return ['status' => 'warning', 'msg' => $msg];
    }
    
    // Normal post
    $stmt = $pdo->prepare("INSERT INTO posts (topic_id, author_id, content, file_path) VALUES (?, ?, ?, ?)");
    $stmt->execute([$topic_id, $author_id, $content, $file_path]);
    
    return ['status' => 'success'];
}
