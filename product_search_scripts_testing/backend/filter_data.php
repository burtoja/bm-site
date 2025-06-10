<?php
header('Content-Type: application/json');

include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_testing/backend/db_connection.php');
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_testing/backend/build_subcategory_tree.php');

$conn = get_db_connection();

// Fetch categories
$categoriesStmt = $conn->prepare("SELECT id, name, has_subcategories FROM categories ORDER BY name ASC");
$categoriesStmt->execute();
$categoriesResult = $categoriesStmt->get_result();

$result = [];

while ($cat = $categoriesResult->fetch_assoc()) {
    $catId = (int)$cat['id'];
    $hasSubcats = (int)$cat['has_subcategories'];

    $nestedSubcategories = [];

    if ($hasSubcats === 1) {
        // Get all subcategories under this category
        $subcatStmt = $conn->prepare("
            SELECT id, name, parent_subcategory_id, has_children
            FROM subcategories
            WHERE category_id = ?
            ORDER BY name ASC
        ");
        $subcatStmt->bind_param("i", $catId);
        $subcatStmt->execute();
        $subcatRes = $subcatStmt->get_result();

        $allSubcategories = [];
        while ($row = $subcatRes->fetch_assoc()) {
            $row['parent_subcategory_id'] = is_null($row['parent_subcategory_id']) ? 0 : (int)$row['parent_subcategory_id'];
            $allSubcategories[] = $row;
        }

        $nestedSubcategories = build_subcategory_tree($allSubcategories, 0, $conn);
    }

    $result[] = [
        'id' => $catId,
        'name' => $cat['name'],
        'open' => false,
        'loaded' => false,
        'filters' => [], // placeholder; will be lazy-loaded
        'subcategories' => $nestedSubcategories
    ];
}

echo json_encode($result);
