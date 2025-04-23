<?php

/**
 * This file includes the various functions which will return the blocks of code
 * which make up the various filter blocks in the category searches
 */

/**
 * Creates the new/used condition filter
 * @param $categoryId
 * @return bool
 */
function render_condition_filter($categoryId) {
    // Read the 'condition' parameter from the URL
    if (isset($_GET['condition'])) {
        $condition_param = strtolower($_GET['condition']);
    } else {
        $condition_param = '';
    }

    // Determine if new/used needs to be selected
    $new_is_checked = '';
    $used_is_checked = '';
    if ($condition_param === 'new') {
        $new_is_checked = ' checked';
    } else if ($condition_param === 'used') {
        $used_is_checked = ' checked';
    }


    $snippet = '';
    $snippet .= '<div class="filter-item">';
    $snippet .= '<div class="toggle filter-toggle" onclick="toggleVisibility(this)">[+] Condition</div>';
    $snippet .= '<div class="filter-options" style="display:none;">';
    $snippet .= '<ul>';
    $snippet .= '<li><label><input type="radio" name="condition_' . $categoryId . '" value="new"' . $new_is_checked . '> New</label></li>';
    $snippet .= '<li><label><input type="radio" name="condition_' . $categoryId . '" value="used"' . $used_is_checked  . '> Used</label></li>';
    $snippet .= '</ul>';
    $snippet .= '</div>';
    $snippet .= '</div>';
    return $snippet;
}

/**
 * Creates the price range  filter
 * NOTE:  This function relies upon a JS script which must only run once
 * which is why it is not included here.
 *
 * @param $categoryId
 * @return bool
 */
function render_price_range_filter($categoryId)
{
    ob_start();

    echo '<div class="filter-item">';
    echo '<div class="toggle filter-toggle" onclick="toggleVisibility(this)">[+] Price Range</div>';
    echo '<div class="filter-options" style="display:none;">';
    echo '<div class="radio-group">';

    // Unique radio name per category
    $priceRadioName = 'price_range_' . $categoryId;

    echo '<label><input type="radio" name="' . $priceRadioName . '" value="any" checked> Any Price</label><br>';
    echo '<label><input type="radio" name="' . $priceRadioName . '" value="under_100"> Under $100</label><br>';

    echo '<label><input type="radio" name="' . $priceRadioName . '" value="custom" onclick="toggleCustomPrice(this)"> Custom Price Range</label><br>';

    // Custom min/max inputs (hidden unless 'custom' is selected)
    echo '<div class="custom-price-fields" style="display:none; margin-top: 5px;">';
    echo '<label class="price-input-label">$<input type="number" step="0.01" min="0" name="min_price_' . $categoryId . '" placeholder="Min" class="price-input"></label><br>';
    echo '<label class="price-input-label">$<input type="number" step="0.01" min="0" name="max_price_' . $categoryId . '" placeholder="Max" class="price-input"></label><br>';
    echo '</div>';

    echo '</div>';
    echo '</div>';
    echo '</div>';

    return ob_get_clean();
}

/**
 * Creates the sort order  filter
 *
 * @param $categoryId
 * @return bool
 */
function render_sort_order_filter($categoryId) {

    ob_start();
    echo '<div class="filter-item">';
            echo '<div class="toggle filter-toggle" onclick="toggleVisibility(this)">[+] Sort Order</div>';
            echo '<div class="filter-options" style="display:none;">';
            echo '<div class="radio-group">';

            $sortRadioName = 'sort_order_' . $categoryId;

            echo '<label><input type="radio" name="' . $sortRadioName . '" value="high_to_low" checked> High to Low</label><br>';
            echo '<label><input type="radio" name="' . $sortRadioName . '" value="low_to_high"> Low to High</label>';

            echo '</div>';
            echo '</div>';
            echo '</div>';
    return ob_get_clean();
}

/**
* Creates the filters specific to a given category
 *
 * @param $categoryId
 * @param $conn
 * @return bool
 */
function render_category_filters_from_db($categoryId, $conn) {
    ob_start();
    $filter_sql = "
                SELECT f.id, f.name 
                FROM filters f
                JOIN category_filters cf ON f.id = cf.filter_id
                WHERE cf.category_id = $categoryId
                ORDER BY f.name ASC
            ";
    $filters = $conn->query($filter_sql);

    if ($filters && $filters->num_rows > 0) {
        while ($filter = $filters->fetch_assoc()) {
            $filterId = (int) $filter['id'];
            $filterName = htmlspecialchars($filter['name']);

            echo '<div class="filter-item">';
            echo '<div class="toggle filter-toggle" onclick="toggleVisibility(this)">[+] ' . $filterName . '</div>';
            echo '<div class="filter-options" style="display:none;">';

            // Get options for this filter
            $option_sql = "
                        SELECT id, value 
                        FROM filter_options 
                        WHERE filter_id = $filterId 
                        ORDER BY sort_order ASC
                    ";
            $options = $conn->query($option_sql);

            if ($options && $options->num_rows > 0) {
                echo '<ul>';
                while ($opt = $options->fetch_assoc()) {
                    $optionId = (int) $opt['id'];
                    $optionLabel = htmlspecialchars($opt['value']);
                    echo '<li><label><input type="checkbox" name="filter_' . $filterId . '[]" value="' . $optionId . '"> ' . $optionLabel . '</label></li>';
                }
                echo '</ul>';
            } else {
                echo '<em>No options</em>';
            }

            echo '</div>'; // .filter-options
            echo '</div>'; // .filter-item
        }
    } else {
        echo '<em>No filters</em>';
    }

    return ob_get_clean();
}

/**
 * Renders the sticky submit and reset button for the main
 * search form
 * @return false|string
 */
function render_sticky_search_reset_buttons() {
    ob_start();
    echo '<div id="search-button-wrapper">';
    echo '<button type="submit" class="search-btn">Search Products</button>';
    echo '<button type="button" class="reset-btn" onclick="resetFilters()">Reset Filters</button>';
    echo '</div>';
    return ob_get_clean();
}

/**
 * Renders all filters and options from a subcategory (data must be pre-loaded).
 *
 * @param array $filtersWithOptions - Array of filters and their options.
 * @return string - HTML filter blocks.
 */
function render_filters_by_subcategory($filtersWithOptions) {
    $html = '';

    foreach ($filtersWithOptions as $filter) {
        $filterId = $filter['filter_id'];
        $filterName = htmlspecialchars($filter['filter_name']);
        $paramName = 'filter_' . $filterId;

        $html .= '<div class="filter-item">';
        $html .= '<div class="toggle filter-toggle" onclick="toggleVisibility(this)">[+] ' . $filterName . '</div>';
        $html .= '<div class="filter-options" style="display:none;">';
        $html .= '<ul>';

        foreach ($filter['options'] as $option) {
            $optionId = $option['option_id'];
            $optionValue = htmlspecialchars($option['value']);
            $html .= '<li><label><input type="checkbox" name="' . $paramName . '[]" value="' . $optionId . '"> ' . $optionValue . '</label></li>';
        }

        $html .= '</ul>';
        $html .= '</div>';
        $html .= '</div>';
    }

    return $html;
}

