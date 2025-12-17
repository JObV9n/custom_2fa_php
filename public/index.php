<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Custom2FA\TwoFactorAuthenticationService;

$service = new TwoFactorAuthenticationService();

// AJAX endpoint for getting current code
if (isset($_GET['ajax']) && isset($_GET['secret'])) {
    header('Content-Type: application/json');
    $secret = $_GET['secret'];
    $currentCode = $service->generateCode($secret);
    $remainingSeconds = 30 - (time() % 30);
    echo json_encode([
        'code' => $currentCode,
        'remaining' => $remainingSeconds
    ]);
    exit;
}

$secret = $_GET['secret'] ?? null;
$message = '';
$currentCode = '';
$remainingSeconds = 30;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $secret = $_POST['secret'] ?? '';
    $userCode = $_POST['verify_code'] ?? '';

    if ($service->verifyCode($secret, $userCode)) {
        $message = '<div style="color:green;font-weight:bold;">✓ Success! Code is valid.</div>';
    } else {
        $message = '<div style="color:red;font-weight:bold;">✗ Invalid or expired code.</div>';
    }
}

if (!$secret) {
    $secret = $service->generateSecretKey();
    header("Location: ?secret=" . urlencode($secret));
    exit;
}

$timeSlice = floor(time() / 30);
$currentCode = $service->generateCode($secret);
$remainingSeconds = 30 - (time() % 30);

$qrUrl = $service->getQRCodeUrl('test@example.com', $secret);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TOTP 2FA Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 40px auto; background: #f0f0f0; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; }
        input[type="text"] { width: 100%; padding: 15px; font-size: 20px; text-align: center; margin: 10px 0; }
        button { width: 100%; padding: 15px; font-size: 18px; background: #007bff; color: white; border: none; border-radius: 8px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .secret { background: #f8f9fa; padding: 10px; border-radius: 6px; word-break: break-all; font-family: monospace; font-size: 14px; }
        .qr { text-align: center; margin: 20px 0; }
        #qrcode { margin: 0 auto; display: block; }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>
<div class="card">
    <h1>TOTP 2FA Tester</h1>

    <p><strong>Secret Key:</strong></p>
    <div class="secret"><?= htmlspecialchars($secret) ?></div>

    <div class="qr">
        <p>Scan with any Authenticator app:</p>
        <div id="qrcode"></div>
    </div>

    <!-- <div class="code" id="code"><?= $currentCode ?></div> -->
    <!-- <div class="timer">Time left: <span id="timer"><?= $remainingSeconds ?></span>s</div> -->

    <form method="POST">
        <input type="hidden" name="secret" value="<?= htmlspecialchars($secret) ?>">
        <input type="text" name="verify_code" placeholder="Enter 6-digit code" maxlength="6" required pattern="\d{6}">
        <button type="submit">Verify Code</button>
    </form>

    <?= $message ?>

    <p style="text-align:center; margin-top:30px;">
        <a href="?">Generate New Secret</a>
    </p>
</div>

<script>
const secret = '<?= htmlspecialchars($secret) ?>';
const otpauthUrl = '<?= "otpauth://totp/MyTOTPTest:test@example.com?secret={$secret}&issuer=MyTOTPTest&digits=6&period=30" ?>';

// Generate QR Code
new QRCode(document.getElementById("qrcode"), {
    text: otpauthUrl,
    width: 256,
    height: 256,
    colorDark: "#000000",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H
});

function updateCode() {
    fetch(`?ajax=1&secret=${encodeURIComponent(secret)}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('code').textContent = data.code;
        })
        .catch(error => console.error('Error:', error));
}

// Update every second
setInterval(updateCode, 1000);

let lastTimestamp = null;
setInterval(() => {
    fetch('/hotreload.php')
        .then(response => response.json())
        .then(data => {
            if (lastTimestamp === null) {
                lastTimestamp = data.timestamp;
            } else if (data.timestamp > lastTimestamp) {
                console.log('Files changed, reloading...');
                location.reload();
            }
        })
        .catch(error => console.error('Hot reload error:', error));
}, 1000);
</script>
</body>
</html>
