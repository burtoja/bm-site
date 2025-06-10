<?php
header('Content-Type: application/json');

include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/backend/db_connection.php');
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/backend/build_subcategory_tree.php');

$conn = get_db_connection();

$categoriesStmt = $conn->prepare("SELECT id, name, has_subcategories FROM categories ORDER BY name ASC");
$categoriesStmt->execute();
$categoriesResult = $categoriesStmt->get_result();

$result = [];

while ($cat = $categoriesResult->fetch_assoc()) {
    $catId = (int)$cat['id'];
    $hasSubcats = (int)$cat['has_subcategories'];

    $filtersDirect = [];
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
    } else {
        // No subcategories — load filters directly from category_filters
        $filtStmt = $conn->prepare("
            SELECT f.id, f.name
            FROM category_filters cf
            JOIN filters f ON cf.filter_id = f.id
            WHERE cf.category_id = ?
            ORDER BY f.name ASC
        ");
        $filtStmt->bind_param("i", $catId);
        $filtStmt->execute();
        $filtRes = $filtStmt->get_result();

        while ($filt = $filtRes->fetch_assoc()) {
            $filtId = (int)$filt['id'];

            $optStmt = $conn->prepare("
                SELECT id, value
                FROM filter_options
                WHERE filter_id = ?
                ORDER BY sort_order ASC, value ASC
            ");
            $optStmt->bind_param("i", $filtId);
            $optStmt->execute();
            $optRes = $optStmt->get_result();

            $options = [];
            while ($opt = $optRes->fetch_assoc()) {
                $options[] = [
                    'id' => 'opt_' . $opt['id'],
                    'value' => $opt['value']
                ];
            }

            $filtersDirect[] = [
                'name' => $filt['name'],
                'open' => false,
                'options' => $options
            ];
        }
    }

    $result[] = [
        'id' => $cat['id'],
        'name' => $cat['name'],
        'open' => false,
        'filters' => $filtersDirect,
        'subcategories' => $nestedSubcategories
    ];
}

echo json_encode($result);
