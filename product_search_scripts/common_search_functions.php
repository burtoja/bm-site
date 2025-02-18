<?php
// common_search_functions.php

/**
 * Gets the search parameters from the URL
 *
 * @return  array of parameters
 **/
function get_search_parameters() {
    $params = $_GET;
    $params['search_keyword_phrase'] = isset($_GET['k']) ? $_GET['k'] : '';
    $params['condition'] = isset($_GET['condition']) ? strtoupper($_GET['condition']) : '';
    $params['manufacturer'] = isset($_GET['manufacturer']) ? $_GET['manufacturer'] : '';
    $params['type'] = isset($_GET['type']) ? $_GET['type'] : '';
    $params['min_price'] = isset($_GET['min_price']) ? trim($_GET['min_price']) : '';
    $params['max_price'] = isset($_GET['max_price']) ? trim($_GET['max_price']) : '';
    $params['sort_select'] = isset($_GET['sort_select']) ? $_GET['sort_select'] : 'price_desc';
    $params['pg'] = isset($_GET['pg']) ? (int)$_GET['pg'] : 1;
    return $params;
}

