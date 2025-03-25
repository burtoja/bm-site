<?php
include_once ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/db_connection.php');

/**
 * Main shortcode for displaying the category filters
 * @param $atts
 * @return void
 */
function boilersa_categories_shortcode($atts) {
    $conn = get_db_connection();

    // Run query
    $sql = "SELECT * FROM categories";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<h2>Categories:</h2><ul>";
        while ($row = $result->fetch_assoc()) {
            echo "<li>" . htmlspecialchars($row['name']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "No categories found.";
    }

    $conn->close();

}
add_shortcode('boilersa_categories', 'boilersa_categories_shortcode');

?>