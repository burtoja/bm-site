<?php

header('Content-Type: application/json');

// Include your token logic
include ($_SERVER["DOCUMENT_ROOT"] . '/ebay_oauth/getBasicToken.php');

$token = getBasicOauthToken();

if (str_starts_with($token, "ERR")) {
    http_response_code(500);
    echo json_encode(["error" => $token]);
} else {
    echo json_encode(["access_token" => $token]);
}

