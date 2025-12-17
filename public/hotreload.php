<?php
header('Content-Type: application/json');

$filesToWatch = [
    __DIR__ . '/index.php',
    __DIR__ . '/../src/TwoFactorAuthenticationService.php',
];

$latestMtime = 0;

foreach ($filesToWatch as $file) {
    if (file_exists($file)) {
        $mtime = filemtime($file);
        if ($mtime > $latestMtime) {
            $latestMtime = $mtime;
        }
    }
}

echo json_encode([
    'timestamp' => $latestMtime,
    'time' => date('Y-m-d H:i:s', $latestMtime)
]);
