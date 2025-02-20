<?php 
//=============================================================================================
// PHP include file -- basic api token  (basic scope, for Shopping api) utilizing CURL.
// We name this file as "getBasicToken.php" in our example application code.
//=============================================================================================
//The eBay OAuth token service generates, or mints, access tokens via two different grant flows:
//   1. Client credentials grant flow: mints a new Application access token that you can use 
//	to access the resources owned by the application.
// 	The Application token expires in two hours and must be reminted.
//   2. Authorization code grant flow: mints a new User access token that you can use to access 
//	the resources owned by the user, rather than the application.
//	The user token expires in two hours and can be renewed with the 
//	refresh token that is returned by the request.
//=============================================================================================

// The following generates an Application token (Client credentials grant flow).

// Set the server folder and path to hidden credential files on the private side of your server.
// The include module will then function without further modification.
// This file may be on the public side of your server, accessible to your applications.


$oauth_root = $_SERVER["DOCUMENT_ROOT"] . "/ebay_oauth/"; // place secure data upstream behind your server's firewall.
$oauth_clientIdfile	=  $oauth_root . "ebay_apiuser.txt";     // file containing appID  = clientID
$oauth_secretIdfile	=  $oauth_root . "ebay_apisecret.txt";   // file containing certID = clientSecret
$oauth_basictokenfile	=  $oauth_root . "ebay_basic_token.txt"; // file containing token (that will update every 2 hours). 
				


function createBasicOauthToken(){
	global $oauth_clientIdfile, $oauth_secretIdfile, $oauth_basictokenfile ;
	
	$oauth_root = $_SERVER["DOCUMENT_ROOT"] . "/ebay_oauth/"; // place secure data upstream behind your server's firewall.
    $oauth_clientIdfile	=  $oauth_root . "ebay_apiuser.txt";     // file containing appID  = clientID
    $oauth_secretIdfile	=  $oauth_root . "ebay_apisecret.txt";   // file containing certID = clientSecret
    $oauth_basictokenfile	=  $oauth_root . "ebay_basic_token.txt"; // file containing token (that will update every 2 hours).

	$url  		= 'https://api.ebay.com/identity/v1/oauth2/token'; 
	$clientID 	= file_get_contents($oauth_clientIdfile);	// AppID
	$clientSecret 	= file_get_contents($oauth_secretIdfile);	// CertID
	$headers 	= [ 
				'Content-Type: application/x-www-form-urlencoded', 
				'Authorization: Basic '.base64_encode($clientID.':'.$clientSecret) 
			]; 

	$body 		= http_build_query([ 
				'grant_type' => 'client_credentials',		  // application credentials 
				'scope' => 'https://api.ebay.com/oauth/api_scope' // space-delimit more scopes for other APIs
			]);  
	$curl 		= curl_init(); // prepare the url shell
	curl_setopt_array( 
			$curl, 
			array( 
				CURLOPT_URL => $url, 
				CURLOPT_RETURNTRANSFER => true,  // true means return result as string without output/echo
				CURLOPT_CUSTOMREQUEST => 'POST', // post format because we are including body data
				CURLOPT_HTTPHEADER => $headers, 
				CURLOPT_POSTFIELDS => $body 
				)
			); 
	$response 	= curl_exec($curl); 	// output string as result of CURLOPT_RETURNTRANSFER
	$err   		= curl_error($curl); 	// capture any URL errors
	curl_close($curl); 

	if ($err) { return "ERR: " . $err; } // this should be trapped by your application by testing for "ERR".

	else { 
		$token = json_decode($response,true);  // true means use keys
		if ($token["access_token"]){
			// write the token to server to use for next two hours (7200 secs).
			file_put_contents($oauth_basictokenfile,$token["access_token"]);
			return $token["access_token"];
		}  
		else{
			return "ERR: could not access token" ; 	//something went wrong, so trap in your application
		}
	} 
}

function getBasicOauthToken(){
	global $oauth_basictokenfile, $oauth_clientIdfile, $oauth_secretIdfile, $oauth_basictokenfile;
	// this is the routine called by the application
	// look at time stamp to see if token has expired
	$now 	  = time();
	$duration = 7200 ; // life of the token, 2 hours
	$margin	  =   30 ; // remaining seconds before we request a new token (depends on how long it will take the application to make all related calls. 
	if (file_exists($oauth_basictokenfile)){
	  	$tstamp	= getdate(filemtime($oauth_basictokenfile));	// this is the last write or update time, not the creation date.
		if ($tstamp[0] + $duration - $now > $margin){		// some time still remains on token.
			return file_get_contents($oauth_basictokenfile);
		}
		else{ 
			return createBasicOauthToken(); 		// if time has run out, then generate a new token.
		}
	}
	else{ 
		return createBasicOauthToken();  // if first time use, then create a new token.
	}
}
?>