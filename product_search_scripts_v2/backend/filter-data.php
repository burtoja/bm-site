<?php
header('Content-Type: application/json');

// ✅ Use your existing DB connection
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/backend/db_connection.php');
$conn = get_db_connection();

include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/backend/build_subcategory_tree.php');

// Step 1: Get categories
$categoriesStmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name ASC");
$categoriesStmt->execute();
$categoriesResult = $categoriesStmt->get_result();

$result = [];

while ($cat = $categoriesResult->fetch_assoc()) {
    $catId = (int)$cat['id'];

    // Step 2: Get subcategories
    $subcategories = [];
    $subStmt = $conn->prepare("SELECT id, name FROM subcategories WHERE category_id = ? ORDER BY name ASC");
    $subStmt->bind_param("i", $catId);
    $subStmt->execute();
    $subRes = $subStmt->get_result();

    while ($sub = $subRes->fetch_assoc()) {
        $subId = (int)$sub['id'];

        // Step 3: Get filters for subcategory
        $filters = [];
        $filtStmt = $conn->prepare("
            SELECT f.id, f.name
            FROM subcategory_filters sf
            JOIN filters f ON sf.filter_id = f.id
            WHERE sf.subcategory_id = ?
            ORDER BY f.name ASC
        ");
        $filtStmt->bind_param("i", $subId);
        $filtStmt->execute();
        $filtRes = $filtStmt->get_result();

        while ($filt = $filtRes->fetch_assoc()) {
            $filtId = (int)$filt['id'];

            // Step 4: Get filter options
            $options = [];
            $optStmt = $conn->prepare("
                SELECT id, value
                FROM filter_options
                WHERE filter_id = ?
                ORDER BY sort_order ASC, value ASC
            ");
            $optStmt->bind_param("i", $filtId);
            $optStmt->execute();
            $optRes = $optStmt->get_result();

            while ($opt = $optRes->fetch_assoc()) {
                $options[] = [
                    'id' => 'opt_' . $opt['id'],
                    'value' => $opt['value']
                ];
            }

            $filters[] = [
                'name' => $filt['name'],
                'open' => false,
                'options' => $options
            ];
        }

        $subcategories[] = [
            'name' => $sub['name'],
            'open' => false,
            'filters' => $filters
        ];
    }

    $result[] = [
        'name' => $cat['name'],
        'open' => false,
        'subcategories' => $subcategories
    ];
}

echo json_encode($result);
