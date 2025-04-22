<?php
// get_subcategory_filters.php
// Returns all filters and their options for a given subcategory ID

require_once 'db_connection.php';
header('Content-Type: application/json');

if (!isset($_GET['subcategory_id'])) {
    echo json_encode(['error' => 'Missing subcategory_id']);
    exit;
}

$subcategoryId = intval($_GET['subcategory_id']);

try {
    $pdo = getPDO();

    // Fetch filters for this subcategory
    $stmt = $pdo->prepare("
        SELECT f.id AS filter_id, f.name AS filter_name
        FROM filters f
        JOIN subcategory_filters sf ON f.id = sf.filter_id
        WHERE sf.subcategory_id = ?
        ORDER BY f.name
    ");
    $stmt->execute([$subcategoryId]);
    $filters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];

    foreach ($filters as $filter) {
        $filterId = $filter['filter_id'];

        // Fetch options for this filter
        $optStmt = $pdo->prepare("
            SELECT id AS option_id, value
            FROM filter_options
            WHERE filter_id = ?
            ORDER BY sort_order ASC
        ");
        $optStmt->execute([$filterId]);
        $options = $optStmt->fetchAll(PDO::FETCH_ASSOC);

        $result[] = [
            'filter_id' => $filterId,
            'filter_name' => $filter['filter_name'],
            'options' => $options
        ];
    }

    echo json_encode(['filters' => $result]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>

