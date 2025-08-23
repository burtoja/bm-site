<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_testing/backend/db_connection.php');
$conn = get_db_connection();

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
  SELECT s.id, s.name, s.sort_order, s.parent_subcategory_id, s.has_children
  FROM subcategories s
  JOIN subcategory_category_links scl ON scl.subcategory_id = s.id
  WHERE scl.category_id = ?
  ORDER BY
    (s.parent_subcategory_id IS NULL) DESC,     -- top-level first
    s.parent_subcategory_id,                    -- group siblings together
    (s.sort_order IS NULL) ASC,                 -- non-NULL sort_order first
    COALESCE(s.sort_order, 999999) ASC,         -- then by explicit sort_order
    s.name ASC                                  -- tie-break alphabetically
";

$filters = [];
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    exit;
}
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
$escaped_ids = array_map('intval', $filter_ids);
$in_clause = implode(',', $escaped_ids);

$sql_options = "
    SELECT id, filter_id, value
    FROM filter_options
    WHERE filter_id IN ($in_clause)
";
//    ORDER BY sort_order ASC, value ASC

$result = $conn->query($sql_options);
if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Option query failed: ' . $conn->error]);
    exit;
}

$options = [];
while ($row = $result->fetch_assoc()) {
    $options[] = $row;
}

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




echo json_encode(['filters' => $filters], JSON_PRETTY_PRINT);
exit;
