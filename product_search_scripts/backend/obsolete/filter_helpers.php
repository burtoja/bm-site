<?php
/**
 * This function will take an object which contains all of the selected filter values
 * and query the DB to make the appropriate substitutions to now have usable filter
 * and category names.  The return is an array which can be used to build api endpoint.
 *
 * @param $inputData
 * @param $conn
 * @return array
 */
function translate_filter_ids_to_names($inputData, $conn) {
    $translated = [];

    foreach ($inputData as $category => $filters) {
        $translated[$category] = [];

        foreach ($filters as $key => $value) {
            // Handle custom price or special radio fields
            if ($key === 'custom_price') {
                $translated[$category]['Custom Price Range'] = $value;
                continue;
            }

            // Example: filter_2401 → 2401
            if (preg_match('/^filter_(\d+)/', $key, $matches)) {
                $filterId = (int)$matches[1];

                // Lookup filter name
                $filterNameQuery = $conn->prepare("SELECT name FROM filters WHERE id = ?");
                $filterNameQuery->bind_param("i", $filterId);
                $filterNameQuery->execute();
                $result = $filterNameQuery->get_result();
                $filterRow = $result->fetch_assoc();
                $filterLabel = $filterRow ? $filterRow['name'] : $key;

                // Lookup option names
                $optionNames = [];
                if (is_array($value)) {
                    $in = implode(',', array_map('intval', $value));
                    $optQuery = $conn->query("SELECT id, value FROM filter_options WHERE id IN ($in)");
                    while ($opt = $optQuery->fetch_assoc()) {
                        $optionNames[] = $opt['value'];
                    }
                }

                $translated[$category][$filterLabel] = $optionNames;
            }

            // Handle other fields like price range or sort order
            elseif (strpos($key, 'price_range') !== false) {
                $translated[$category]['Price Range'] = ucfirst($value);
            }
            elseif (strpos($key, 'sort_order') !== false) {
                $translated[$category]['Sort Order'] = $value === 'high_to_low' ? 'High to Low' : 'Low to High';
            }
            elseif (strpos($key, 'condition') !== false) {
                $translated[$category]['Condition'] = $value === 'new' ? 'New' : 'Used';
            }
        }
    }

    return $translated;
}
