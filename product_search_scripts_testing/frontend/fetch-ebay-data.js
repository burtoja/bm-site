/**
 * Fetch eBay search results using a constructed API endpoint URL and a dynamic OAuth token.
 *
 * This function:
 *  - Calls  PHP backend to get a valid OAuth token
 *  - Sends a GET request to the eBay Browse API with that token
 *  - Handles and logs any errors
 *  - Returns parsed JSON data from eBay or null on failure
 *
 * @param {string} apiUrl - The full eBay API endpoint (already constructed)
 * @returns {Promise<Object|null>} - The response data from eBay, or null on failure
 */
async function fetchEbayData(apiUrl) {
    try {
        // Fetch OAuth token from server
        const tokenRes = await fetch('/product_search_scripts_v2/backend/get-ebay-token.php');
        if (!tokenRes.ok) {
            console.error(`Token request failed with status ${tokenRes.status}`);
            return null;
        }

        const tokenData = await tokenRes.json();
        if (!tokenData.access_token) {
            console.error(`Token response did not contain 'access_token':`, tokenData);
            return null;
        }

        const authToken = tokenData.access_token;

        // Call eBay API with token
        const ebayRes = await fetch(apiUrl, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Content-Type': 'application/json',
                'X-EBAY-C-ENDUSERCTX': 'contextualLocation=country=US'
            }
        });

        if (!ebayRes.ok) {
            const errorText = await ebayRes.text();
            console.error(`eBay API error (${ebayRes.status}):`, errorText);
            return null;
        }

        const data = await ebayRes.json();
        console.log('eBay response received:', data);
        return data;

    } catch (err) {
        console.error('Unexpected error fetching eBay data:', err);
        return null;
    }
}
