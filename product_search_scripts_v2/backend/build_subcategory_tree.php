<?php
// Function to build the subcategory tree for the filter data

function build_subcategory_tree($subcategories, $parentId, $conn) {
$tree = [];

foreach ($subcategories as $subcat) {
if ((int)$subcat['parent_id'] === $parentId) {
// Get filters for this subcategory
$filters = [];

$subcatId = (int)$subcat['id'];

$filtStmt = $conn->prepare("
SELECT f.id, f.name
FROM subcategory_filters sf
JOIN filters f ON sf.filter_id = f.id
WHERE sf.subcategory_id = ?
ORDER BY f.name ASC
");
$filtStmt->bind_param("i", $subcatId);
$filtStmt->execute();
$filtRes = $filtStmt->get_result();

while ($filt = $filtRes->fetch_assoc()) {
$filtId = (int)$filt['id'];

// Get filter options
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

// Recursively build children
$children = build_subcategory_tree($subcategories, $subcatId, $conn);

$tree[] = [
'name' => $subcat['name'],
'open' => false,
'filters' => $filters,
'subcategories' => $children
];
}
}

return $tree;
}

