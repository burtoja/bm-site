<?php
/**
 * Displays a filter box with eBay aspects for the given category.
 * 
 * @param string $ebayCategoryId    the eBay category ID 
 * @param string $authToken         eBay OAuth token 
 */
function displayEbayFilterBox($ebayCategoryId, $authToken) {
    // 1) Build the API endpoint
    $endpoint = "https://api.ebay.com/buy/browse/v1/item_summary/search?category_ids={$ebayCategoryId}&limit=1&aspect_filter";

    // 2) Set up cURL with your OAuth token
    $curl = curl_init();
    $headers = [
        "Authorization: Bearer {$authToken}",
        "Content-Type: application/json",
        "X-EBAY-C-MARKETPLACE-ID: EBAY_US" 
    ];

    curl_setopt_array($curl, [
        CURLOPT_URL            => $endpoint,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        echo "<p>Error fetching aspects: " . curl_error($curl) . "</p>";
        curl_close($curl);
        return;
    }
    curl_close($curl);

    // 3) Parse the JSON response
    //TODO: Need to vrify this structure
    $responseData = json_decode($response, true);

    // In real code, verify $responseData isn't null and includes aspects.
    // Let's pretend $responseData['aspects'] or $responseData['aspectValues'] has the data.
    // We'll build a simple array of aspects for demonstration:
    // e.g.  "aspects" => [
    //          [
    //            "localizedAspectName" => "Brand",
    //            "localizedAspectValues" => ["Sony","LG","Samsung"]
    //          ],
    //          [
    //            "localizedAspectName" => "Color",
    //            "localizedAspectValues" => ["Black","White","Red"]
    //          ]
    //        ]
    
    if (!isset($responseData['aspects']) || empty($responseData['aspects'])) {
        echo "<p>No aspect data found for category <strong>{$ebayCategoryId}</strong>.</p>";
        return;
    }

    $aspects = $responseData['aspects'];

    // 4) Build the HTML form for filters
    echo '<div class="ebay-filter-box" style="border:1px solid #ccc;padding:15px;margin:15px 0;">';
    echo "<h4>Refine Results</h4>";

    echo '<form method="GET" action="">';

    foreach ($aspects as $aspect) {
        $aspectName = $aspect['localizedAspectName'];
        $values = $aspect['localizedAspectValues'] ?? [];

        echo "<fieldset style='margin-bottom:10px;'>";
        echo "<legend style='font-weight:bold;'>{$aspectName}</legend>";

        foreach ($values as $val) {
            $inputName = "aspect_".str_replace(' ', '_', $aspectName)."[]";
            $safeVal   = htmlspecialchars($val, ENT_QUOTES);

            echo "<label style='display:block;'>";
            echo "<input type='checkbox' name='{$inputName}' value='{$safeVal}'> {$safeVal}";
            echo "</label>";
        }
        echo "</fieldset>";
    }

    echo '<button type="submit">Apply Filters</button>';

    echo '</form>';
    echo '</div>';
}
