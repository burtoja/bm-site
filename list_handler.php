<?php
/**
 * Function for parsing manufacturer and type files 
 * used within the shortcode function 
 **/
 
	function get_names_and_search_terms($filePath) {
		$lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$line_information = [];
		foreach ($lines as $line) {
			$line = trim($line);
			$displayName = $line;
			$override = null;
			// Find positions of < and >
			$posOpen = strpos($line, '<');
			$posClose = strpos($line, '>');
			if ($posOpen !== false && $posClose !== false && $posClose > $posOpen) {
				// Extract what's inside < >
				$override = substr($line, $posOpen + 1, $posClose - ($posOpen + 1));
				// Remove the <stuff> from the display name
				$displayName = trim(substr_replace($line, '', $posOpen, $posClose - $posOpen + 1));
			} else {
				$override = $displayName;
			}
			$line_information[] = [
				'displayName'  => $displayName,
				'override'     => $override
			];
		}
		return $line_information;
	}


?>