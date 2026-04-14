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
            $domains = [];
            foreach ($list as $domain) {
                $clean = strtolower(trim($domain));
                if (!empty($clean)) {
                    $domains[$clean] = true;
                }
            }
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

    // Robust pattern for URLs and naked domains/subdomains
    // 1. Matches http(s)://...
    // 2. Matches www....
    // 3. Matches domains like example.com, sub.example.pl etc.
    $pattern = '/(?<=\s|^)((?:https?:\/\/|www\.)[^\s\)]+|(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,6}(?:\/[^\s\)]*)?)/i';
    
    return preg_replace_callback($pattern, function($matches) use ($domains) {
        $url = $matches[0];
        
        // Remove trailing punctuation that might have been caught
        $url = rtrim($url, '.,!?;:');
        
        $test_url = $url;
        if (!preg_match('/^https?:\/\//i', $test_url)) {
            $test_url = 'http://' . $test_url;
        }
        
        $host = parse_url($test_url, PHP_URL_HOST);
        if (!$host) return $matches[0];

        $host = strtolower($host);
        
        // Remove 'www.' prefix for checking if the base domain is malicious
        $check_host = $host;
        if (substr($check_host, 0, 4) === 'www.') {
            $check_host = substr($check_host, 4);
        }

        // Check exact host and all its parent domains
        $parts = explode('.', $check_host);
        while (count($parts) >= 2) {
            $curr = implode('.', $parts);
            if (isset($domains[$curr])) {
                return date('Y-m-d H:i') . ' Usunięto niebezpieczny link';
            }
            array_shift($parts);
        }
        
        return $matches[0];
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
