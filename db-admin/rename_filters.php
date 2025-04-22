<?php

require_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/backend/db_connection.php');

$conn = get_db_connection();

$data = json_decode(file_get_contents('php://input'), true);
$updates = $data['updates'] ?? [];

foreach ($updates as $item) {
    $id = $item['id'];
    $value = $item['new_value'];

    $value = trim($item['new_value']);
    if ($value === '') continue; // Skip empty entries

    if ($item['type'] === 'filter') {
        $stmt = $conn->prepare("UPDATE filters SET name = ? WHERE id = ?");
    } elseif ($item['type'] === 'option') {
        $stmt = $conn->prepare("UPDATE filter_options SET value = ? WHERE id = ?");
    } else {
        continue; // unknown type
    }

    $stmt->bind_param("si", $value, $id);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['message' => 'Updates applied successfully']);
?>
