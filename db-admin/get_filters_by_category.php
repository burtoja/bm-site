<?php
require_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/backend/db_connection.php');

$conn = get_db_connection();

$categories = [];
$cat_result = $conn->query("SELECT id, name FROM categories");

while ($cat = $cat_result->fetch_assoc()) {
    $cat_id = $cat['id'];
    $filters = [];

    $filter_stmt = $conn->prepare("
        SELECT f.id, f.name
        FROM filters f
        JOIN category_filters cf ON cf.filter_id = f.id
        WHERE cf.category_id = ?
    ");
    $filter_stmt->bind_param("i", $cat_id);
    $filter_stmt->execute();
    $filter_result = $filter_stmt->get_result();

    while ($filter = $filter_result->fetch_assoc()) {
        $filter_id = $filter['id'];
        $options_stmt = $conn->prepare("SELECT id, value FROM filter_options WHERE filter_id = ?");
        $options_stmt->bind_param("i", $filter_id);
        $options_stmt->execute();
        $options_result = $options_stmt->get_result();

        $filter['options'] = [];
        while ($opt = $options_result->fetch_assoc()) {
            $filter['options'][] = $opt;
        }

        $filters[] = $filter;
    }

    $cat['filters'] = $filters;
    $categories[] = $cat;
}

echo json_encode($categories);
?>
