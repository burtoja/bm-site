<?php
header('Content-Type: application/json');
require_once($_SERVER['DOCUMENT_ROOT'] . '/product_search_scripts_v2/backend/db_connection.php');
global $db;

// Step 1: Get all categories
$categories = $db->get_results("
  SELECT id, name FROM categories ORDER BY name ASC
");

// Build tree
$result = [];

foreach ($categories as $cat) {
    // Step 2: Get subcategories
    $subcategories = $db->get_results($db->prepare("
    SELECT id, name FROM subcategories
    WHERE category_id = %d ORDER BY name ASC
  ", $cat->id));

    $subcatArray = [];

    foreach ($subcategories as $subcat) {
        // Step 3: Get filters for this subcategory
        $filters = $db->get_results($db->prepare("
      SELECT f.id, f.name
      FROM subcategory_filters sf
      JOIN filters f ON sf.filter_id = f.id
      WHERE sf.subcategory_id = %d
      ORDER BY f.name ASC
    ", $subcat->id));

        $filterArray = [];

        foreach ($filters as $filter) {
            // Step 4: Get filter options
            $options = $db->get_results($db->prepare("
        SELECT id, value
        FROM filter_options
        WHERE filter_id = %d
        ORDER BY sort_order ASC, value ASC
      ", $filter->id));

            $optionArray = [];
            foreach ($options as $opt) {
                $optionArray[] = [
                    'id' => "opt_" . $opt->id,
                    'value' => $opt->value
                ];
            }

            $filterArray[] = [
                'name' => $filter->name,
                'open' => false,
                'options' => $optionArray
            ];
        }

        $subcatArray[] = [
            'name' => $subcat->name,
            'open' => false,
            'filters' => $filterArray
        ];
    }

    $result[] = [
        'name' => $cat->name,
        'open' => false,
        'subcategories' => $subcatArray
    ];
}

// Output the final JSON tree
echo json_encode($result);

