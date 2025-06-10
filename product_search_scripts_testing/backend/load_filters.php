<?php
header('Content-Type: application/json');

// Use your existing DB connection script
require_once '../../your_db_connection_script.php'; // Adjust path as needed

$pdo = getDbConnection(); // Example: function returns PDO instance

// Determine which ID is passed
$category_id = $_GET['category_id'] ?? null;
$subcategory_id = $_GET['subcategory_id'] ?? null;
$subsubcategory_id = $_GET['subsubcategory_id'] ?? null;

if (!$category_id && !$subcategory_id && !$subsubcategory_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing category, subcategory, or subsubcategory ID']);
    exit;
}

// Determine the filter scope
$scope = '';
$scope_id = 0;

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

// Step 1: Fetch filters
$sql = "
    SELECT f.id AS filter_id, f.name AS filter_name
    FROM filters f
    JOIN {$scope}_filters cf ON cf.filter_id = f.id
    WHERE cf.{$id_column} = ?
    ORDER BY f.sort_order ASC, f.name ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$scope_id]);
$filters = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Step 2: Fetch options for each filter
$filter_ids = array_column($filters, 'filter_id');

if (empty($filter_ids)) {
    echo json_encode(['filters' => []]);
    exit;
}

$in_clause = implode(',', array_fill(0, count($filter_ids), '?'));

$sql_options = "
    SELECT id, filter_id, value
    FROM filter_options
    WHERE filter_id IN ($in_clause)
    ORDER BY sort_order ASC, value ASC
";
$stmt = $pdo->prepare($sql_options);
$stmt->execute($filter_ids);
$options = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group options under their filters
$grouped_options = [];
foreach ($options as $opt) {
    $grouped_options[$opt['filter_id']][] = [
        'id' => (int)$opt['id'],
        'value' => $opt['value']
    ];
}

// Assemble final response
foreach ($filters as &$f) {
    $f['id'] = (int)$f['filter_id'];
    $f['name'] = $f['filter_name'];
    $f['options'] = $grouped_options[$f['filter_id']] ?? [];
    $f['open'] = false;
    unset($f['filter_id'], $f['filter_name']);
}

echo json_encode(['filters' => $filters]);

