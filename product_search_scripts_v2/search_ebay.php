<?php
header('Content-Type: application/json');
error_log("🔥 search_ebay.php loaded and running!");

echo json_encode(["status" => "OK", "message" => "Reached PHP successfully"]);
