<?php
header('Content-Type: application/json');

include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_testing/backend/db_connection.php');

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
$categoriesStmt = $conn->prepare("SELECT id, name, has_subcategories FROM categories WHERE is_active = 1 ORDER BY name ASC");
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
            SELECT s.* 
            FROM subcategories s
            JOIN subcategory_category_links scl ON scl.subcategory_id = s.id
            WHERE scl.category_id = ?
            ORDER BY 
                (s.parent_subcategory_id IS NULL) DESC,     -- top-level first
                s.parent_subcategory_id,                    -- group siblings together
                (s.sort_order IS NULL) ASC,                 -- non-NULL sort_order first
                COALESCE(s.sort_order, 999999) ASC,         -- then by explicit sort_order
                s.name ASC                                  -- tie-break alphabetically
            ;
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
