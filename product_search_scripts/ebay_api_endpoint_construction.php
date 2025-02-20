<?php
include_once ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/common_search_functions.php');

/**
 * Gets the eBay OAuth token
 *
 * @return  token
 **/
//function get_ebay_oauth_token() {
//    include ($_SERVER["DOCUMENT_ROOT"] . '/ebay_oauth/getBasicToken.php');
//    return getBasicOauthToken();
//}

/**
 * Constructs the initial endpoint to fetch available brands in a category
 *
 * @return string API endpoint (url)
 **/
function construct_brand_list_endpoint($category_id) {
    return "https://api.ebay.com/buy/browse/v1/item_summary/search?q=&category_ids={$category_id}&fieldgroups=ASPECT_REFINEMENTS";
}

/**
 * Extracts brand list from the API response (accepts JSON string)
 *
 * @param string $json_response The API response data in JSON format
 * @return array List of available brands
 **/
function extract_brands_from_response($json_response) {
    $brands = [];
    $response = json_decode($json_response, true);
    if (isset($response['refinement']['aspectDistributions'])) {
        foreach ($response['refinement']['aspectDistributions'] as $aspect) {
            if ($aspect['localizedAspectName'] === 'Brand') {
                foreach ($aspect['aspectValueDistributions'] as $brandData) {
                    $brands[] = $brandData['localizedAspectValue'];
                }
            }
        }
    }
    return $brands;
}

/**
 * Constructs the search endpoint based on whether the brand exists in the category
 *
 * @return string API endpoint (url)
 **/
function construct_search_endpoint($search_keyword_phrase, $category_id, $manufacturer, $condition, $brand_list) {
    $search_keyword_phrase = urlencode($search_keyword_phrase);
    $api_endpoint = "https://api.ebay.com/buy/browse/v1/item_summary/search?q={$search_keyword_phrase}";

    // Add category ID
    $api_endpoint .= "&category_ids={$category_id}";

    // Add filters for condition
    if (!empty($condition)) {
        $api_endpoint .= "&filter=" . urlencode("conditions:{" . $condition . "}");
    }

    // Determine if the manufacturer exists in the available brands
    if (in_array($manufacturer, $brand_list)) {
        $api_endpoint .= "&aspect_filter=" . urlencode("categoryId:{$category_id},Brand:{" . $manufacturer . "}");
    } else {
        // Fallback to adding the manufacturer to the search query
        $api_endpoint = "https://api.ebay.com/buy/browse/v1/item_summary/search?q={$search_keyword_phrase}+{$manufacturer}&category_ids={$category_id}";
        if (!empty($condition)) {
            $api_endpoint .= "&filter=" . urlencode("conditions:{" . $condition . "}");
        }
    }

    // Add sorting and pagination
    $api_endpoint .= "&limit=50&offset=0&sort=-price";

    return $api_endpoint;
}
?>

