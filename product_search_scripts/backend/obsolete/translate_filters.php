<?php

header('Content-Type: application/json');

include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/backend/db_connection.php');
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/backend/filter_helpers.php');

$conn = get_db_connection();

$input = json_decode(file_get_contents('php://input'), true);
$filters = $input['filters'] ?? [];

$translated = translate_filter_ids_to_names($filters, $conn);
echo json_encode($translated);

