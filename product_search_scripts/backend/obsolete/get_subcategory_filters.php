<?php
// get_subcategory_filters.php
// Returns filters associated with the given subcategory ID (leaf or nested)

require_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/backend/db_connection.php');
header('Content-Type: application/json');

if (!isset($_GET['subcategory_id']) || !is_numeric($_GET['subcategory_id'])) {
    echo json_encode(['error' => 'Missing or invalid subcategory_id']);
    exit;
}

$subcategoryId = intval($_GET['subcategory_id']);
$conn = get_db_connection();

// Get all filters for the given subcategory
$filterSql = "
    SELECT f.id AS filter_id, f.name AS filter_name
    FROM filters f
    JOIN subcategory_filters sf ON f.id = sf.filter_id
    WHERE sf.subcategory_id = ?
    ORDER BY f.name
";

$filterStmt = $conn->prepare($filterSql);
$filterStmt->bind_param("i", $subcategoryId);
$filterStmt->execute();
$filterResult = $filterStmt->get_result();

$filters = [];

while ($row = $filterResult->fetch_assoc()) {
    error_log(print_r($row, true));  // Add this line
    $filters[] = [
        'filter_id' => (int)$row['filter_id'],
        'filter_name' => $row['filter_name']
    ];
}

// For each filter, get its options
$result = [];

foreach ($filters as $filter) {
    $filterId = $filter['filter_id'];

    $optionSql = "
        SELECT id AS option_id, value
        FROM filter_options
        WHERE filter_id = ?
        ORDER BY sort_order ASC
    ";

    $optStmt = $conn->prepare($optionSql);
    $optStmt->bind_param("i", $filterId);
    $optStmt->execute();
    $optionResult = $optStmt->get_result();

    $options = [];
    while ($optRow = $optionResult->fetch_assoc()) {
        $options[] = $optRow;
    }

    $result[] = [
        'filter_id' => $filterId,
        'filter_name' => $filter['filter_name'],
        'options' => $options
    ];

    $optStmt->close();
}

$filterStmt->close();
$conn->close();

echo json_encode(['filters' => $result]);
