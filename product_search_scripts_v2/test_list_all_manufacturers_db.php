<?php
// Replace these with your actual credentials
$host = 'localhost:3306';        // or the IP address of your DB server
$username = 'boilersa_wp_g1klw';
$password = 'b67a9!^rMA~CzqTu';
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

?>