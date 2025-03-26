<?php

/**
 * This file will hold functions for including any needed styling blocks
 * to the category listing pages
 */

/**
 * Renders the style block to style the category and filter list
 * @return false|string
 */
function render_main_category_listing_style_block() {
    ob_start();
    echo'
        <style>
            .toggle {
                cursor: pointer;
                margin: 6px 0;
                font-weight: bold;
            }
            .toggle:hover {
                text-decoration: underline;
            }
    
            .category-item {
                margin-bottom: 10px;
            }
    
            .category-filters {
                margin-left: 20px; /* indent filters under category */
                color: cadetblue;
            }
    
            .filter-item {
                margin-bottom: 8px;
                color: darkturquoise;
            }
    
            .filter-options {
                margin-left: 20px; /* indent options under filter */
            }
    
            .filter-options ul {
                margin: 0;
                padding-left: 0;
                list-style-type: none;
            }
    
            .filter-options li {
                margin-bottom: 4px;
            }
            .custom-price-fields input {
                margin: 4px 0;
            }
            
            #search-button-wrapper {
                position: sticky;
                bottom: 20px;
                background: white;
                padding: 10px;
                text-align: center;
                z-index: 100;
                border-top: 1px solid #ccc;
            }
        
            #search-button-wrapper button {
                padding: 10px 20px;
                font-size: 16px;
                font-weight: bold;
                cursor: pointer;
                border-radius: 6px;
                background-color: #0073aa;
                color: white;
                border: none;
            }
        
            #search-button-wrapper button:hover {
                background-color: #005f8d;
            }

        </style>
    ';

    return ob_get_clean();
}
