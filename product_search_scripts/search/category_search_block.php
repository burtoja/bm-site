<?php
// category_search_block.php

// Include common search functions
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/common_search_functions.php');

/* Main Shortcode Function */
function advanced_product_search_form_shortcode($atts) {
    return render_search_form($atts);
}
