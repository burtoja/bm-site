<?php
require_once $_SERVER["DOCUMENT_ROOT"] . '/ebay_oauth/getBasicToken.php';

$token = getBasicOauthToken();

if ($token && !str_starts_with($token, "ERR")) {
    echo "✅ Token retrieved successfully:<br>" . htmlspecialchars($token);
} else {
    echo "❌ Failed to get token: " . htmlspecialchars($token);
}
?>
