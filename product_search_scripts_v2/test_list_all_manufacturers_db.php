<?php
function boilersa_list_categories_shortcode() {
    global $wpdb;

    $table = $wpdb->prefix . 'category_search_filters';

    // Get unique category names
    $categories = $wpdb->get_col("SELECT DISTINCT category_name FROM $table ORDER BY category_name ASC");

    if (empty($categories)) {
        return '<p>No categories found.</p>';
    }

    $output = '<ul>';
    foreach ($categories as $category_name) {
        $output .= '<li>' . esc_html($category_name) . '</li>';
    }
    $output .= '</ul>';

    return $output;
}
add_shortcode('boilersa_categories', 'boilersa_list_categories_shortcode');
?>