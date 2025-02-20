<?php
// search_button_snippet.php

function build_search_button_snippet($unique_id, $product_category, $specialFilterKeys) {
    ob_start();
    ?>
    <script>
    document.getElementById('find-products-button-<?php echo $unique_id; ?>').addEventListener('click', function() {
        // do URLSearchParams, etc.
        // you can reference product_category_<?php echo $unique_id; ?>
        // and specialFilterKeys_<?php echo $unique_id; ?>
    });
    </script>
    <?php
    // Possibly a small HTML snippet for the button or keep it in the main file
    return ob_get_clean();
}
