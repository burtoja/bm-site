<?php

function build_subcategory_tree($subcategories, $parentId, $conn) {
$tree = [];

foreach ($subcategories as $subcat) {
if ((int)$subcat['parent_subcategory_id'] === $parentId) {
$subcatId = (int)$subcat['id'];
$filters = [];

// Only load filters if subcategory has no children
if ((int)$subcat['has_children'] === 0) {
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

$optStmt = $conn->prepare("
SELECT id, value
FROM filter_options
WHERE filter_id = ?
ORDER BY sort_order ASC, value ASC
");
$optStmt->bind_param("i", $filtId);
$optStmt->execute();
$optRes = $optStmt->get_result();

$options = [];
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
}

$children = build_subcategory_tree($subcategories, $subcatId, $conn);

$tree[] = [
'id' => $subcat['id'],
'name' => $subcat['name'],
'open' => false,
'filters' => $filters,
'subcategories' => $children
];
}
}

return $tree;
}
