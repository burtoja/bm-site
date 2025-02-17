<?php
/**
 * This function will take the URL which is provided after a product search
 * and parse out all of the parameter-value pairs which are found after the
 * sort_select parameter.  We have specifically created this such that the
 * special filter parameters will fall at the end to faciliate this function
 * working.
 * 
 * Sample URL:
 * https://boilersandmachinery.com/product-listings/?k=boilers&condition=used&
 * manufacturer=Cleaver+Brooks+Boiler&type=Hot+Water&sort_select=price_desc&
 * fuel=Oil+Fired+Boiler&medium=Low+Temperature&
 * configuration=Horizontal+Firetube
 * 
 * returns and array of parameter value pairs
 * 
 **/

function get_special_filter_pairs_from_url() {
    $queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
    // put parameter value pairs into an array
    // ["k=boilers","condition=used","sort_select=price_desc","fuel=Oil","medium=Low..."]
    $pairs = explode('&', $queryString); 
    
    $foundSortSelect = false;
    $specialFilterParametersAndValues = []; // we store "fuel=Oil", "medium=Low..." etc. here
    
    foreach ($pairs as $pair) {
        if (!$foundSortSelect) {
            if (strpos($pair, 'sort_select=') === 0) {
                $foundSortSelect = true;
                continue; 
            }
        } else {
            $specialFilterParametersAndValues[] = $pair;
        }
    }
return $specialFilterParametersAndValues;

}

?>