<?php
header('Content-Type: application/json');

// Use your custom DB connection
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/backend/db_connection.php');
$conn = get_db_connection();

// Include the recursive subcategory tree builder
//include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/backend/build_subcategory_tree.php');

// Step 1: Get all top-level categories
$categoriesStmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name ASC");
$categoriesStmt->execute();
$categoriesResult = $categoriesStmt->get_result();

$result = [];

while ($cat = $categoriesResult->fetch_assoc()) {
    $catId = (int)$cat['id'];

    // Step 2: Get all subcategories (flat list) under this category
    $subcatStmt = $conn->prepare("
        SELECT id, name, parent_id
        FROM subcategories
        WHERE category_id = ?
        ORDER BY name ASC
    ");
    $subcatStmt->bind_param("i", $catId);
    $subcatStmt->execute();
    $subcatRes = $subcatStmt->get_result();

    $allSubcategories = [];
    while ($row = $subcatRes->fetch_assoc()) {
        // Normalize parent_id to int (handle nulls)
        $row['parent_id'] = is_null($row['parent_id']) ? 0 : (int)$row['parent_id'];
        $allSubcategories[] = $row;
    }

    // Step 3: Build nested tree from subcategories starting at root (parent_id = 0)
    $nestedSubcategories = build_subcategory_tree($allSubcategories, 0, $conn);

    // Step 4: Add to result
    $result[] = [
        'name' => $cat['name'],
        'open' => false,
        'subcategories' => $nestedSubcategories
    ];
}

echo json_encode($result);
