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
                top: 50px;
                padding: 10px;
                text-align: left;
                z-index: 1000;
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
        
            #sticky-search-top {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
            }
            
            .search-btn, .reset-btn {
                padding: 10px 20px;
                font-size: 16px;
                font-weight: bold;
                border-radius: 6px;
                cursor: pointer;
                border: none;
            }
        
            #search-button-wrapper button:hover {
                background-color: #005f8d;
            }
            
            .search-btn {
                background-color: #0073aa;
                color: white;
            }
            
            .search-btn:hover {
                background-color: #005f8d;
            }
            
            .reset-btn {
                background-color: #f2f2f2;
                color: #333;
            }
            
            .reset-btn:hover {
                background-color: #e0e0e0;
            }
            


        </style>
    ';

    return ob_get_clean();
}
