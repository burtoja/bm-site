<?php
/**
 * This file contains the function to connect to the DB
 */

function get_db_connection()
{

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
    return $conn;
}

?>
