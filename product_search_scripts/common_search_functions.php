<?php
// common_search_functions.php

// Function to handle retrieving common parameters from $_GET
function get_search_parameters() {
    return [
        'condition' => isset($_GET['condition']) ? $_GET['condition'] : '',
        'manufacturer' => isset($_GET['manufacturer']) ? trim($_GET['manufacturer']) : '',
        'keyword' => isset($_GET['k']) ? $_GET['k'] : '',
        'category' => isset($_GET['category']) ? $_GET['category'] : ''
    ];
}

