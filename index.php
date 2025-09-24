<?php
// index.php - PHP HLS Proxy via Webshare

// configure your Webshare proxy
$proxyHost = "45.38.107.97:6014";       // proxy host:port
$proxyAuth = "zucvsikb:fnn1eucm3bt0";   // username:password

if (!isset($_GET['url']) || !filter_var($_GET['url'], FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo "Error: missing or invalid ?url parameter";
    exit;
}

$target = $_GET['url'];
$ch = curl_init($target);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// use proxy
curl_setopt($ch, CURLOPT_PROXY, $proxyHost);
curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyAuth);

// forward headers
$headers = [
    "User-Agent: Mozilla/5.0",
    "Accept: */*"
];
if (!empty($_SERVER['HTTP_RANGE'])) {
    $headers[] = "Range: " . $_SERVER['HTTP_RANGE'];
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

// check if playlist
if (stripos($target, ".m3u8") !== false) {
    header("Content-Type: application/vnd.apple.mpegurl");

    $base = dirname($target) . "/";
    $lines = explode("\n", $response);

    foreach ($lines as &$line) {
        $trim = trim($line);
        if ($trim !== "" && $trim[0] !== "#") {
            // make absolute
            if (!preg_match("#^https?://#", $trim)) {
                $trim = $base . $trim;
            }
            // rewrite to proxy
            $line = $_SERVER['SCRIPT_NAME'] . "?url=" . urlencode($trim);
        }
    }
    echo implode("\n", $lines);
} else {
    header("Content-Type: " . ($contentType ?: "application/octet-stream"));
    echo $response;
}
