<?php

/**
 * This file includes the various functions which will return the blocks of code
 * which make up the various filter blocks in the category searches
 */

/**
 * Creates the new/used condition filter
 * @return bool
 */
function get_condition_filter() {
    ob_start();
    echo '<div class="filter-item">';
    echo '<div class="toggle filter-toggle" onclick="toggleVisibility(this)">[+] Condition</div>';
    echo '<div class="filter-options" style="display:none;">';
    echo '<ul>';
    echo '<li><label><input type="checkbox" name="condition_' . $categoryId . '[]" value="new"> New</label></li>';
    echo '<li><label><input type="checkbox" name="condition_' . $categoryId . '[]" value="used"> Used</label></li>';
    echo '</ul>';
    echo '</div>'; // .filter-options
    echo '</div>'; // .filter-item
    return ob_clean();
}
