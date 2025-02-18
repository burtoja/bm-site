<?php

include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/search/collapsible_filter_box.php');

/**
 * Function to display the toggle button for the top of page search filter
 *
 * @param $unique_id
 * @return false|string
 */
function render_toggle_search_button($unique_id) {
    ob_start();
    ?>
    <div style="margin-bottom: 1em;">
        <button type="button" id="toggle-filters-<?php echo $unique_id; ?>"
                style="padding: 0.5em 1em; font-size: 1em; cursor: pointer;">
            Show/Hide Search Filters
        </button>

        <div id="filters-container-<?php echo $unique_id; ?>" style="display: none; border: 1px solid #ccc; padding: 1em; margin-top: 1em;">
            <?php
            // Dynamically insert the toggle script
            echo get_toggle_search_script($unique_id);
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Function to deliver the scripts associated with the reveal/hide
 * button for the top of page search filter
 *
 * @param $unique_id
 * @return false|string
 */
function get_toggle_search_script($unique_id) {
    ob_start();
    ?>
    <script>
        (function() {
            var btn = document.getElementById('toggle-filters-<?php echo $unique_id; ?>');
            var box = document.getElementById('filters-container-<?php echo $unique_id; ?>');

            if (btn && box) {
                btn.addEventListener('click', function() {
                    box.style.display = (box.style.display === 'none') ? 'block' : 'none';
                });
            }
        })();
    </script>
    <?php
    return ob_get_clean();
}
?>

