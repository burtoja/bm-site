<?php
function build_subcategory_tree(array $subcategories, $parentId, mysqli $conn): array {
    $children = [];

    // Because the $subcategories array should already be loaded from SQL with ORDER BY,
    // siblings are guaranteed to be in the correct order:
    //   (sort_order IS NULL) ASC, COALESCE(sort_order, 999999) ASC, name ASC

    foreach ($subcategories as $s) {
        $pid = $s['parent_subcategory_id'] ?? null;

        $isMatch = ($pid === null && $parentId === null) || ((int)$pid === (int)$parentId);
        if (!$isMatch) continue;

        $subcatId = (int)$s['id'];
        $filters = [];

        // Only load filters if subcategory has no children
        if ((int)$s['has_children'] === 0) {
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

            while ($f = $filtRes->fetch_assoc()) {
                $filtId = (int)$f['id'];

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
                while ($o = $optRes->fetch_assoc()) {
                    $options[] = [
                        'id' => 'opt_' . $o['id'],
                        'value' => $o['value']
                    ];
                }

                $filters[] = [
                    'name' => $f['name'],
                    'open' => false,
                    'options' => $options
                ];
            }
        }

        $children[] = [
            'id' => $s['id'],
            'name' => $s['name'],
            'open' => false,
            'filters' => $filters,
            'subcategories' => build_subcategory_tree($subcategories, $subcatId, $conn)
        ];
    }

    return $children;
}
