<?php
include_once ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/db_connection.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/main_search_filter_blocks.php');

function boilersa_categories_shortcode($atts) {
    $conn = get_db_connection();

    // Get all categories
    $sql = "SELECT id, name FROM categories ORDER BY name ASC";
    $result = $conn->query($sql);

    ob_start();
    echo '<div class="category-list">';

    if ($result->num_rows > 0) {
        while ($cat = $result->fetch_assoc()) {
            $categoryId = (int) $cat['id'];
            $categoryName = htmlspecialchars($cat['name']);

            echo '<div class="category-item">';
            echo '<div class="toggle category-toggle" onclick="toggleVisibility(this)">[+] ' . $categoryName . '</div>';
            echo '<div class="category-filters" style="display:none;">';

            // Condition Filter (New/Used)
            echo render_condition_filter($categoryId);
            // Add "Price Range" as a toggleable filter
            echo render_price_range_filter($categoryId);
            // Add "Sort Order" as a toggleable filter
            echo render_sort_order_filter($categoryId);
            // Get filters linked to this category
            echo render_category_filters_from_db($categoryId, $conn);
        }
    } else {
        echo '<p>No categories found.</p>';
    }

    echo '</div>';

    $conn->close();

    // Add JS & CSS
    ?>
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
    </style>

    <script>
        function toggleVisibility(el) {
            const content = el.nextElementSibling;
            if (!content) return;
            if (content.style.display === "none") {
                content.style.display = "block";
                el.innerHTML = el.innerHTML.replace("[+]", "[-]");
            } else {
                content.style.display = "none";
                el.innerHTML = el.innerHTML.replace("[-]", "[+]");
            }
        }


    </script>
    <script>
        function toggleCustomPrice(radioBtn) {
            const container = radioBtn.closest('.filter-options');
            const customFields = container.querySelector('.custom-price-fields');
            const allRadios = container.querySelectorAll('input[type="radio"]');

            allRadios.forEach(radio => {
                if (radio.value !== 'custom') {
                    radio.addEventListener('click', () => {
                        customFields.style.display = 'none';
                    });
                }
            });

            if (radioBtn.value === 'custom') {
                customFields.style.display = 'block';
            }
        }

        // On page load, add listeners for all "custom" price radios
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll('input[type="radio"][value="custom"]').forEach(radio => {
                radio.addEventListener('click', function () {
                    toggleCustomPrice(this);
                });
            });

            // Add fallback listeners for non-custom to hide custom fields
            document.querySelectorAll('input[type="radio"]').forEach(radio => {
                if (radio.value !== 'custom') {
                    radio.addEventListener('click', function () {
                        const container = this.closest('.filter-options');
                        const customFields = container.querySelector('.custom-price-fields');
                        if (customFields) {
                            customFields.style.display = 'none';
                        }
                    });
                }
            });
        });
    </script>

    <?php

    return ob_get_clean();
}
add_shortcode('boilersa_categories', 'boilersa_categories_shortcode');
?>
