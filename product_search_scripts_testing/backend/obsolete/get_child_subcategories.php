<?php
// get_child_subcategories.php
// Returns direct children of a given parent subcategory for a specific category

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_testing/backend/db_connection.php');
header('Content-Type: application/json');

if (!isset($_GET['category_id'], $_GET['parent_id']) || !is_numeric($_GET['category_id']) || !is_numeric($_GET['parent_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing or invalid parameters']);
    exit;
}

$categoryId = (int) $_GET['category_id'];
$parentId = (int) $_GET['parent_id'];

$conn = get_db_connection();

$sql = "
    SELECT id, name, category_id, has_children
    FROM subcategories
    WHERE category_id = ? AND parent_subcategory_id = ?
    ORDER BY name ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'DB prepare failed']);
    exit;
}

$stmt->bind_param("ii", $categoryId, $parentId);
$stmt->execute();
$result = $stmt->get_result();

$subcategories = [];

while ($row = $result->fetch_assoc()) {
    $subcategories[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'subcategories' => $subcategories
]);
