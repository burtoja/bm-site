<?php
/**
 * This function will turn any string into a usable filename
 * e.g. "Rotary Lobe (Pump)" -> "rotary_lobe_pump"
 **/

function slugify($string) {
  $string = strtolower($string);
  // Replace non-alphanumeric or spaces with underscore
  $string = preg_replace('/[^a-z0-9]+/', '_', $string); 
  // Trim underscores from ends
  return trim($string, '_');
}

/**
 * Deslugifies strings and capitalizes the first character of every word
 */
function deslugify($slug) {
    // Replace underscores with spaces
    $string = str_replace('_', ' ', $slug);
    // Capitalize the first letter of each word
    return ucwords($string);
}


?>