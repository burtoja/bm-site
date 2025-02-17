<?php
// An endpoint to return a JSON-encoded list of manufacturers for a given category & type

include ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/slugify.php');

header('Content-Type: application/json');

// Grab parameters
$category = isset($_GET['category']) ? strtolower($_GET['category']) : '';
$category = slugify($category);
$type = isset($_GET['type'])     ? strtolower($_GET['type'])     : '';
$type = slugify($type); 

// By default, read the master "all manufacturers" file
$filename = $_SERVER["DOCUMENT_ROOT"] . '/product_info_lists/list_' . $category . '_manufacturers.txt';

// Build the typed filename if available
if ($type !== '') {
    // If a specific type was requested, try to load the typed file
    $typedFile = $_SERVER["DOCUMENT_ROOT"] . "/product_info_lists/list_{$category}_manufacturers_{$type}.txt";
    
    // Check if typed file exists
    if (file_exists($typedFile)) {
        $filename = $typedFile;  // typed file found, override the default
    }
}

// DEBUGGING
error_log("Attempting to load manufacturers from the following file: " . $filename);

// Check if file exists
if (!file_exists($filename)) {
    // No file found
    error_log("***ERROR -- Unsuccessful in finding the following file: " . $filename);
    echo json_encode([]);
    exit;
}

// Read lines
$lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);


// If file() fails for any reason, $lines might be false
if ($lines === false) {
    error_log("ERROR -- Reading file fialed:" . $filename);
    echo json_encode([]);
    exit;
}
error_log("APPARENT SUCCESS LOADING FILE");

//Parse lines for overrides

// Return lines as JSON
echo json_encode($lines);

