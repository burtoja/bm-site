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

?>