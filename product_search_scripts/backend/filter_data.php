<?php
header('Content-Type: application/json');

include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/backend/db_connection.php');

$conn = get_db_connection();

/**
 * Recursively builds subcategory tree structure.
 */
function build_subcategory_tree_minimal(array $allSubcategories, int $parentId): array {
    $tree = [];

    foreach ($allSubcategories as $subcat) {
        if ((int)$subcat['parent_subcategory_id'] === $parentId) {
            $tree[] = [
                'id' => (int)$subcat['id'],
                'name' => $subcat['name'],
                'open' => false,
                'loaded' => false,
                'filters' => [],
                'subcategories' => build_subcategory_tree_minimal($allSubcategories, (int)$subcat['id'])
            ];
        }
    }

    return $tree;
}

// Get all categories
$categoriesStmt = $conn->prepare("SELECT id, name, has_subcategories FROM categories ORDER BY name ASC");
$categoriesStmt->execute();
$categoriesResult = $categoriesStmt->get_result();

$result = [];

while ($cat = $categoriesResult->fetch_assoc()) {
    $catId = (int)$cat['id'];
    $hasSubcats = (int)$cat['has_subcategories'];

    $nestedSubcategories = [];

    if ($hasSubcats === 1) {
        // Load all subcategories for this category
        $subcatStmt = $conn->prepare("
            SELECT id, name, parent_subcategory_id
            FROM subcategories
            WHERE category_id = ?
        ");
        $subcatStmt->bind_param("i", $catId);
        $subcatStmt->execute();
        $subcatRes = $subcatStmt->get_result();

        $allSubcategories = [];
        while ($row = $subcatRes->fetch_assoc()) {
            $row['parent_subcategory_id'] = is_null($row['parent_subcategory_id']) ? 0 : (int)$row['parent_subcategory_id'];
            $allSubcategories[] = $row;
        }

        $nestedSubcategories = build_subcategory_tree_minimal($allSubcategories, 0);
    }

    $result[] = [
        'id' => $catId,
        'name' => $cat['name'],
        'open' => false,
        'loaded' => false,
        'filters' => [], // always lazy-loaded
        'subcategories' => $nestedSubcategories
    ];
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
