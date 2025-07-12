<?php
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = array_map('trim', explode('=', $line, 2));
        putenv("$name=$value");
    }
}

define('VERIFY_TOKEN', getenv('VERIFY_TOKEN'));
define('PAGE_ACCESS_TOKEN', getenv('PAGE_ACCESS_TOKEN'));
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY'));
define('N8N_WEBHOOK_URL', getenv('N8N_WEBHOOK_URL'));
