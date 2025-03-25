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
function get_condition_filter($categoryId) {
    $snippet = '';
    $snippet .= '<div class="filter-item">';
    $snippet .= '<div class="toggle filter-toggle" onclick="toggleVisibility(this)">[+] Condition</div>';
    $snippet .= '<div class="filter-options" style="display:none;">';
    $snippet .= '<ul>';
    $snippet .= '<li><label><input type="checkbox" name="condition_' . $categoryId . '[]" value="new"> New</label></li>';
    $snippet .= '<li><label><input type="checkbox" name="condition_' . $categoryId . '[]" value="used"> Used</label></li>';
    $snippet .= '</ul>';
    $snippet .= '</div>';
    $snippet .= '</div>';
    return $snippet;
}
