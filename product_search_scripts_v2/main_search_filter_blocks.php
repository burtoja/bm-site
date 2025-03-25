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

/**
 * Creates the price range  filter
 * NOTE:  This function relies upon a JS script which must only run once
 * which is why it is not included here.
 *
 * @param $categoryId
 * @return bool
 */
function get_price_range_filter($categoryId)
{
// Add "Price Range" as a toggleable filter
    ob_start();
    echo '<div class="filter-item">';
    echo '<div class="toggle filter-toggle" onclick="toggleVisibility(this)">[+] Price Range</div>';
    echo '<div class="filter-options" style="display:none;">';

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

    echo '</div>'; // .filter-options
    echo '</div>'; // .filter-item

    return ob_get_clean();
}

