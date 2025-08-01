<?php
require_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_testing/backend/db_connection.php');
header('Content-Type: application/json');

if (!isset($_GET['category_id']) || !is_numeric($_GET['category_id'])) {
    echo json_encode(['error' => 'Missing or invalid category_id']);
    exit;
}

$categoryId = (int) $_GET['category_id'];
$conn = get_db_connection();

// OLD:  $sql = "SELECT id, name FROM subcategories WHERE category_id = ? ORDER BY sort_order ASC, name ASC";
$sql = "SELECT id, name, category_id, has_children FROM subcategories WHERE category_id = ? AND parent_subcategory_id IS NULL ORDER BY name";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $categoryId);
$stmt->execute();

$result = $stmt->get_result();
$subcategories = [];

while ($row = $result->fetch_assoc()) {
    $subcategories[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode(['subcategories' => $subcategories]);
?>

