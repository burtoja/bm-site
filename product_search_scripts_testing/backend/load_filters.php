<?php
header('Content-Type: application/json');

// Connect to DB
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_testing/backend/db_connection.php');
$conn = get_db_connection(); // Returns a mysqli object

// Determine which ID is passed
$category_id = $_GET['category_id'] ?? null;
$subcategory_id = $_GET['subcategory_id'] ?? null;
$subsubcategory_id = $_GET['subsubcategory_id'] ?? null;

if (!$category_id && !$subcategory_id && !$subsubcategory_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing category, subcategory, or subsubcategory ID']);
    exit;
}

// Determine scope
if ($subsubcategory_id) {
    $scope = 'subsubcategory';
    $scope_id = (int)$subsubcategory_id;
    $id_column = 'subsubcategory_id';
} elseif ($subcategory_id) {
    $scope = 'subcategory';
    $scope_id = (int)$subcategory_id;
    $id_column = 'subcategory_id';
} else {
    $scope = 'category';
    $scope_id = (int)$category_id;
    $id_column = 'category_id';
}

// Step 1: Get filters
$sql = "
    SELECT f.id AS filter_id, f.name AS filter_name
    FROM filters f
    JOIN {$scope}_filters cf ON cf.filter_id = f.id
    WHERE cf.{$id_column} = ?
    ORDER BY f.sort_order ASC, f.name ASC
";

$filters = [];
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $scope_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $filters[] = $row;
}
$stmt->close();

$filter_ids = array_column($filters, 'filter_id');
if (empty($filter_ids)) {
    echo json_encode(['filters' => []]);
    exit;
}

// Step 2: Get options
$placeholders = implode(',', array_fill(0, count($filter_ids), '?'));
$types = str_repeat('i', count($filter_ids));

$sql_options = "
    SELECT id, filter_id, value
    FROM filter_options
    WHERE filter_id IN ($placeholders)
    ORDER BY sort_order ASC, value ASC
";

$stmt = $conn->prepare($sql_options);
$stmt->bind_param($types, ...$filter_ids);
$stmt->execute();
$result = $stmt->get_result();

$options = [];
while ($row = $result->fetch_assoc()) {
    $options[] = $row;
}
$stmt->close();

// Group options under filters
$grouped_options = [];
foreach ($options as $opt) {
    $fid = $opt['filter_id'];
    $grouped_options[$fid][] = [
        'id' => (int)$opt['id'],
        'value' => $opt['value']
    ];
}

// Final JSON output
foreach ($filters as &$f) {
    $fid = (int)$f['filter_id'];
    $f = [
        'id' => $fid,
        'name' => $f['filter_name'],
        'options' => $grouped_options[$fid] ?? [],
        'open' => false
    ];
}

echo json_encode(['filters' => $filters]);
