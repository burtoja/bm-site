<?php
// get_child_subcategories.php
// Returns all direct child subcategories of a given subcategory_id

require_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/backend/db_connection.php');
header('Content-Type: application/json');

if (!isset($_GET['parent_id']) || !is_numeric($_GET['parent_id'])) {
    echo json_encode(['error' => 'Missing or invalid parent_id']);
    exit;
}

$conn = get_db_connection();
$parentId = (int) $_GET['parent_id'];

$sql = "SELECT id, name FROM subcategories WHERE parent_subcategory_id = ? ORDER BY name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $parentId);
$stmt->execute();
$result = $stmt->get_result();

$subcategories = [];
while ($row = $result->fetch_assoc()) {
    $subcategories[] = $row;
}

echo json_encode($subcategories);
