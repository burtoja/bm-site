<?php
/**
 * Boilers & Machinery
 * load_filters.php  —  returns filters (+ options) for a given scope
 *
 *
 * INPUT PARAMS (GET):
 *   - subcategory_id (preferred)    int
 *   - subsubcategory_id (alias)     int  (treated same as subcategory_id)
 *   - category_id                   int
 *
 * OUTPUT (JSON):
 *   { "filters": [ {id, name, options: [{id, value, filter_id}] } ] }
 *
 * EXPECTED TABLES (core):
 *   - filters(id, name, ...)
 *   - filter_options(id, filter_id, value, sort_order?)
 *   - category_filters(category_id, filter_id)
 *   - subcategory_filters(subcategory_id, filter_id)
 *
 * OPTIONAL TABLES (for scoping options by scope):
 *   - category_filter_options(category_id, option_id)
 *   - subcategory_filter_options(subcategory_id, option_id)
 *
 * Date:   2025-09-05
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// ---- DB ----
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_testing/backend/db_connection.php');
$conn = get_db_connection();
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

// ---- Helpers ----
function bm_table_exists(mysqli $conn, string $table): bool {
    $t = $conn->real_escape_string($table);
    $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '$t'";
    if ($res = $conn->query($sql)) {
        $ok = $res->num_rows > 0;
        $res->free();
        return $ok;
    }
    return false;
}
function bm_int($v) {
    if ($v === null || $v === '') return null;
    return intval($v);
}

// ---- Read params ----
$subcategory_id    = bm_int($_GET['subcategory_id']    ?? null);
$subsubcategory_id = bm_int($_GET['subsubcategory_id'] ?? null); // alias
$category_id       = bm_int($_GET['category_id']       ?? null);

// Normalize: treat subsubcategory_id as subcategory_id (same table in schema)
if ($subcategory_id === null && $subsubcategory_id !== null) {
    $subcategory_id = $subsubcategory_id;
}

// Determine scope
$scope = null;
$scope_id = null;
if ($subcategory_id !== null) { $scope = 'subcategory'; $scope_id = $subcategory_id; }
elseif ($category_id !== null) { $scope = 'category'; $scope_id = $category_id; }
else {
    http_response_code(400);
    echo json_encode(['error' => 'Missing subcategory_id (or subsubcategory_id) or category_id']);
    exit;
}

// ---- Step 1: get filters for the scope ----
if ($scope === 'subcategory') {
    $sql_filters = "
    SELECT f.id AS filter_id, f.name AS filter_name
    FROM filters f
    JOIN subcategory_filters sf ON sf.filter_id = f.id
    WHERE sf.subcategory_id = ?
    ORDER BY f.name ASC
  ";
} else { // category
    $sql_filters = "
    SELECT f.id AS filter_id, f.name AS filter_name
    FROM filters f
    JOIN category_filters cf ON cf.filter_id = f.id
    WHERE cf.category_id = ?
    ORDER BY f.name ASC
  ";
}

$filters = [];
if ($stmt = $conn->prepare($sql_filters)) {
    $stmt->bind_param('i', $scope_id);
    $stmt->execute();
    $rs = $stmt->get_result();
    while ($row = $rs->fetch_assoc()) {
        $filters[] = $row;
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    exit;
}

if (empty($filters)) {
    echo json_encode(['filters' => []], JSON_PRETTY_PRINT); // nothing linked at this scope
    exit;
}

$filter_ids = array_map(function($r){ return intval($r['filter_id']); }, $filters);
$in_clause  = implode(',', $filter_ids);

// ---- Step 2: get options for those filters ----
// Try scoped-by-scope tables first; fall back to global options
$has_sfo = bm_table_exists($conn, 'subcategory_filter_options');
$has_cfo = bm_table_exists($conn, 'category_filter_options');

$result = null;
$order  = " ORDER BY COALESCE(fo.sort_order, 999999) ASC, fo.value ASC ";

if ($scope === 'subcategory' && $has_sfo) {
    // Only options assigned to this subcategory
    $sql = "
    SELECT fo.id, fo.filter_id, fo.value
    FROM filter_options fo
    JOIN subcategory_filter_options sfo ON sfo.option_id = fo.id
    WHERE sfo.subcategory_id = ?
      AND fo.filter_id IN ($in_clause)
    $order
  ";
    if ($stmt2 = $conn->prepare($sql)) {
        $stmt2->bind_param('i', $scope_id);
        $stmt2->execute();
        $result = $stmt2->get_result();
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Prepare options failed: ' . $conn->error]);
        exit;
    }
} elseif ($scope === 'category' && $has_cfo) {
    // Only options assigned to this category
    $sql = "
    SELECT fo.id, fo.filter_id, fo.value
    FROM filter_options fo
    JOIN category_filter_options cfo ON cfo.option_id = fo.id
    WHERE cfo.category_id = ?
      AND fo.filter_id IN ($in_clause)
    $order
  ";
    if ($stmt2 = $conn->prepare($sql)) {
        $stmt2->bind_param('i', $scope_id);
        $stmt2->execute();
        $result = $stmt2->get_result();
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Prepare options failed: ' . $conn->error]);
        exit;
    }
} else {
    // Fallback: global options per filter_id
    $sql = "SELECT id, filter_id, value FROM filter_options WHERE filter_id IN ($in_clause) $order";
    $result = $conn->query($sql);
    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => 'Option query failed: ' . $conn->error]);
        exit;
    }
}

// Group options by filter_id
$grouped = [];
while ($row = $result->fetch_assoc()) {
    $fid = intval($row['filter_id']);
    if (!isset($grouped[$fid])) $grouped[$fid] = [];
    $grouped[$fid][] = [
        'id'        => intval($row['id']),
        'filter_id' => $fid,
        'value'     => $row['value']
    ];
}
if (isset($stmt2)) { $stmt2->close(); }

// Build output structure
$out = [];
foreach ($filters as $f) {
    $fid = intval($f['filter_id']);
    $out[] = [
        'id'      => $fid,
        'name'    => $f['filter_name'],
        'options' => $grouped[$fid] ?? [],
        'open'    => false
    ];
}

echo json_encode(['filters' => $out], JSON_PRETTY_PRINT);
exit;
