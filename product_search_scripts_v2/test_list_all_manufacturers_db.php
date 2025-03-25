<?php

function boilersa_categories_shortcode($atts) {
// Replace these with your actual credentials
$host = 'localhost:3306';        // or the IP address of your DB server
$username = 'boilersa_app_user';
$password = 'CzF06TTM^lCPWc$*';
$database = 'boilersa_category_search_filters';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Run query
$sql = "SELECT DISTINCT category_name FROM boilersa_category_search_filters ORDER BY category_name ASC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h2>Categories:</h2><ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['category_name']) . "</li>";
    }
    echo "</ul>";
} else {
    echo "No categories found.";
}

$conn->close();

}
add_shortcode('boilersa_categories', 'boilersa_categories_shortcode');

?>