<?php
/**
 * Retrieves the misc filter fields for a given category
 *
 * @return array
 * @param  $categoryName
 */

function get_extra_filter_fields_for_category($categoryName) {
    // DB connection
    include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/db_connection.php');
    $conn = get_db_connection();

    // First, get category_id from category name
    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->bind_param("s", $categoryName);
    $stmt->execute();
    $stmt->bind_result($categoryId);
    $stmt->fetch();
    $stmt->close();

    if (!$categoryId) {
        return [];
    }

    // Now get filter names linked to this category
    $stmt = $conn->prepare("
        SELECT f.name
        FROM filters f
        JOIN category_filters cf ON cf.filter_id = f.id
        WHERE cf.category_id = ?
    ");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();

    $filterNames = [];
    while ($row = $result->fetch_assoc()) {
        $filterNames[] = strtolower(str_replace(' ', '_', $row['name'])); // Normalize to match param keys
    }

    $stmt->close();
    $conn->close();

    return $filterNames;
}

