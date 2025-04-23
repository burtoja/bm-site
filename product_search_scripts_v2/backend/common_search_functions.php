<?php
require_once $_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/backend/db_connection.php';

/**
 * Gets the search parameters from the URL
 *
 * @return  array of parameters
 **/
function get_search_parameters() {
    $params = $_GET;
    $params['k'] = isset($_GET['k']) ? $_GET['k'] : '';
    $params['search_keyword_phrase'] = isset($_GET['filters']) ? $_GET['filters'] : '';
    $params['condition'] = isset($_GET['condition']) ? strtoupper($_GET['condition']) : '';
    $params['manufacturer'] = isset($_GET['manufacturer']) ? $_GET['manufacturer'] : '';
    $params['type'] = isset($_GET['type']) ? $_GET['type'] : '';
    $params['min_price'] = isset($_GET['min_price']) ? trim($_GET['min_price']) : '';
    $params['max_price'] = isset($_GET['max_price']) ? trim($_GET['max_price']) : '';
    $params['sort_select'] = isset($_GET['sort_select']) ? $_GET['sort_select'] : 'price_desc';
    $params['pg'] = isset($_GET['pg']) ? (int)$_GET['pg'] : 1;
    $params['subcategory_id'] = isset($_GET['subcategory_id']) ? (int)$_GET['subcategory_id'] : null;
    return $params;
}

/**
 * Given an array of filter_option_ids, returns a map of
 * filter_name => [option_value1, option_value2, ...]
 *
 * @param array $optionIds
 * @return array
 */
function get_aspect_filter_map_from_option_ids($optionIds) {
    if (empty($optionIds)) {
        return [];
    }

    $conn = get_db_connection();

    $placeholders = implode(',', array_fill(0, count($optionIds), '?'));
    $types = str_repeat('i', count($optionIds));

    $stmt = $conn->prepare("
        SELECT f.name AS filter_name, fo.value AS option_value
        FROM filter_options fo
        JOIN filters f ON fo.filter_id = f.id
        WHERE fo.id IN ($placeholders)
    ");
    $stmt->bind_param($types, ...$optionIds);
    $stmt->execute();
    $result = $stmt->get_result();

    $aspectMap = [];
    while ($row = $result->fetch_assoc()) {
        $fname = $row['filter_name'];
        $oval = $row['option_value'];
        $aspectMap[$fname][] = $oval;
    }

    $stmt->close();
    $conn->close();
    return $aspectMap;
}


