<?php
function build_subcategory_tree(array $subcategories, $parentId, mysqli $conn): array {
    // collect direct children of $parentId
    $children = [];
    foreach ($subcategories as $s) {
        $pid = array_key_exists('parent_subcategory_id', $s) ? $s['parent_subcategory_id'] : null;

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
            'sort_order' => $s['sort_order'] ?? null, // keep for sorting
            'open' => false,
            'filters' => $filters,
            'subcategories' => [] // fill after sorting
        ];
    }

    // sort: non-NULL sort_order first, then by sort_order asc, then name asc
    usort($children, function ($a, $b) {
        $ao = isset($a['sort_order']) && $a['sort_order'] !== null ? (int)$a['sort_order'] : PHP_INT_MAX;
        $bo = isset($b['sort_order']) && $b['sort_order'] !== null ? (int)$b['sort_order'] : PHP_INT_MAX;
        if ($ao !== $bo) return $ao <=> $bo;
        return strcasecmp($a['name'], $b['name']);
    });

    // now recurse for each child (after we know sibling order)
    foreach ($children as &$child) {
        $child['subcategories'] = build_subcategory_tree($subcategories, (int)$child['id'], $conn);
    }

    return $children;
}

