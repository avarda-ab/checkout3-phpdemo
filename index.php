<?php
session_start();
require "vendor/autoload.php";
require "utils.php";

// Load variables from .env file
// Environment variables have to be passed to the application in order to authenticate
// initialize payment and show the Checkout3 frontend app
$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();
$dotenv->required('CLIENT_ID')->notEmpty();
$dotenv->required('CLIENT_SECRET')->notEmpty();
$dotenv->required('CHECKOUT3_BACKEND_API_URL')->notEmpty();
$dotenv->required('CHECKOUT3_JS_BUNDLE')->notEmpty();
$dotenv->required('PUBLIC_URL')->notEmpty();

$api_url = getenv('CHECKOUT3_BACKEND_API_URL');
$checkout3_js_bundle = getenv('CHECKOUT3_JS_BUNDLE');
$public_url = getenv('PUBLIC_URL');
$client_id =  getenv('CLIENT_ID');
$client_secret = getenv('CLIENT_SECRET');

if (empty($_GET['purchaseId'])) {
    // Get "Merchant access token"
    // CLIENT_ID and CLIENT_SECRET is used for generating a "Merchant access token"
    // This token is used as authentication for all further communication with the Checkout3 BE API
    // This token should never be displayed to the user or sent to the frontend of the application
    // Send CLIENT_ID and CLIENT_SECRET as query parameters
    // GET request '/api/merchant/accessToken?ClientID=<client_id_here>&ClientSecret=<client_secret_here>'
    // Additional info can be found in the documentation here:
    // <https://docs.avarda.com/checkout-3/how-to-get-started/#obtain-merchant-access-token>
    $request_payload = array("ClientID" => $client_id, "ClientSecret" => $client_secret);
    // Create encoded HTTP query according to the RFC3986
    // Send CLIENT_ID and CLIENT_SECRET (may contain special characters) correctly
    // Alternatively the same can be achieved encoding the variables manually with urlencode() + utf8_encode()
    $query_parameters = http_build_query($request_payload, null, '&', PHP_QUERY_RFC3986);
    $request_url = "$api_url/api/merchant/accessToken?$query_parameters";
    $get_merchant_access_token_result = send_get_request($request_url);
    if ($get_merchant_access_token_result === false) { /* Handle error */
    };
    $data = json_decode($get_merchant_access_token_result);
    $merchant_access_token = $data->token;
    $_SESSION['merchant_access_token'] = $merchant_access_token;

    // Initialize payment in the Checkout3
    // Send language, items list and other additional information...
    // Exhaustive list of all possibilities available here:
    // <https://docs.avarda.com/checkout-3/how-to-get-started/#initialize-payment>
    // Merchant has to send "Merchant access token" as an authorization in the POST request header:
    //      Authorization: Bearer <merchant_access_token_here>
    // Successful initialization returns unique "Purchase JWT token" and "PurchaseId"
    // "Purchase JWT token" is used to display Checkout3 form on the frontend for the current session
    // This token can be stored, in case merchant does not want to keep track, it can be regenerated:
    //      GET request to '/api/merchant/reClaimPurchaseToken?PurchaseId=<purchase_id_here>'
    //      Authorization: Bearer <merchant_access_token_here>
    // More info about re-claiming "Purchase JWT token" here:
    // <https://docs.avarda.com/checkout-3/how-to-get-started>
    // "PurchaseId" is ID for merchant API calls:
    //      - getting payment status,
    //      - refunds,
    //      - cancels,
    //      - returns, etc.
    $request_url = "$api_url/api/merchant/initializePayment";
    $request_header = "Content-type: application/json\r\nAuthorization: Bearer $merchant_access_token\r\n";
    $request_payload = array(
        "language" => "English", "items" => array(array(
            "description" => "Some item",
            "notes" => "",
            "amount" => 50,
            "taxCode" => "20",
            "taxAmount" => 42
        )),
    );
    $initialize_payment_result = send_post_request($request_url, $request_header, $request_payload);
    if ($initialize_payment_result === false) { /* Handle error */
    };
    $data = json_decode($initialize_payment_result);
    $purchase_JWT_token = $data->jwt;
    $_SESSION['purchase_JWT_token'] = $purchase_JWT_token;
    $purchase_id = $data->purchaseId;
    $_SESSION['purchase_id'] = $purchase_id;

    // Encode PurchaseId and add it to the URL
    // GET query parameter "purchaseId" is used to handle new/old purchase
    // This can be done either in merchant DB, browser storage or as it's done here using URL
    // For demo purposes, this is not required in a real integration
    $encoded_purchase_JWT_token = urlencode($purchase_JWT_token);
    $encoded_purchase_id = urlencode($purchase_id);
    $_SESSION['encoded_purchase_JWT_token'] = $encoded_purchase_JWT_token;
    $_SESSION['purchaseId'] = $encoded_purchase_id;
    if (empty($_GET['purchaseId'])) {
        header("Location: $public_url/?purchaseId=$encoded_purchase_id");
        die();
    };
};

// Update items in the current session
// Merchant has to send "Merchant access token" as an authorization in the PUT request header:
//      Authorization: Bearer <merchant_access_token_here>
// Merchant has to provide a "PurchaseId" that was obtained after POST call to /api/merchant/initializePayment
// in order to send a new list of items
// More information can be found here: 
// <https://docs.avarda.com/checkout-3/more-features/update-items/>
if (!empty($_GET['updateItems'])) {
    $update_amount = rand(10, 500);
    $tax_amount = $update_amount * 0.2;
    $request_url = "$api_url/api/merchant/updateItems";
    $request_header = "Content-type: application/json\r\nAuthorization: Bearer " . $_SESSION['merchant_access_token'] . "\r\n";
    $request_payload = array(
        "purchaseId" => $_SESSION['purchase_id'], "items" => array(array(
            "description" => "Some item",
            "notes" => "",
            "amount" => (int) $update_amount,
            "taxCode" => "20",
            "taxAmount" => (int) $tax_amount
        )),
    );
    $update_items_result = send_put_request($request_url, $request_header, $request_payload);
    if ($update_items_result === false) { /* Handle error */
    } else {
        // GET query parameter "updateItems" is used to start Update items flow
        // After resolving update items flow put the URL into the original format
        // For demo purposes, this is not required in a real integration
        header("Location: $public_url/?purchaseId=" . $_SESSION['purchaseId']);
        die();
    };
};


if (!empty($_GET['redirected'])) {
    // Re-claim "Purchase JWT token" 
    // "Purchase JWT token" is used to display Checkout3 form on the frontend for the current session
    // This token can be stored, in case merchant does not want to keep track, it can be regenerated:
    //      GET request to '/api/merchant/reClaimPurchaseToken?PurchaseId=<purchase_id_here>'
    //      Authorization: Bearer <merchant_access_token_here>
    // More info about re-claiming "Purchase JWT token" here:
    // <https://docs.avarda.com/checkout-3/how-to-get-started>
    // Using GET query parameter "redirected" for demo purposes
    // External payment gate redirects user to "redirectUrl" provided by merchant in Checkout3 FE initialization
    // This is not required in case "Purchase JWT token" is stored by merchant
    $request_url = "$api_url/api/merchant/reClaimPurchaseToken?purchaseId=" . $_SESSION['purchase_id'];
    $request_header = "Authorization: Bearer " . $_SESSION['merchant_access_token'];
    $reclaim_token_result = send_get_request($request_url, $request_header);
    if ($reclaim_token_result === false) { /* Handle error */
    } else {
        $data = json_decode($reclaim_token_result);
        $purchase_JWT_token = $data->jwt;
        $encoded_purchase_JWT_token = urlencode($purchase_JWT_token);
        // Update saved and encoded "Purchase JWT token"
        // For demo purposes, this is not required in a real integration
        $_SESSION['purchase_JWT_token'] = $purchase_JWT_token;
        $_SESSION['encoded_purchase_JWT_token'] = $encoded_purchase_JWT_token;
        // After resolving redirect from merchant and updating data put the URL into the original format
        // For demo purposes, this is not required in a real integration
        header("Location: $public_url/?purchaseId=" . $_SESSION['purchaseId']);
        die();
    }
};

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>AVARDA - Checkout3 - PHP Integration Demo</title>
    <meta name="description" content="DemoShop">
    <meta name="author" content="Avarda">
</head>

<body>
    <h1>AVARDA - Checkout3 - PHP Integration Demo</h1>
    <button><a href="/?updateItems=1&purchaseId=<?php echo (string) $_SESSION['purchaseId'] ?>">Update Items</a></button>
    <!-- Checkout3 form will be displayed in a <div> with a custom unique ID, this ID is passed on in the Checkout3 frontend app initialization -->
    <div id="checkout-form"></div>
    <!-- During the initialization of the Checkout3 frontend app, additional flags can be passed to change appearance or behaviour of the app -->
    <!-- "Purchase JWT token" is passed and required -->
    <!-- Redirect url is necessary for payment methods that will redirect user to their domain while processing payment (e.g. card payment) -->
    <!-- Additional information available here: -->
    <!-- <https://docs.avarda.com/checkout-3/embed-checkout/#showing-the-form> -->
    <script>
        (function(e,t,n,a,s,c,o,i,r){e[a]=e[a]||function(){(e[a].q=e[a].q||[
        ]).push(arguments)};e[a].i=s;i=t.createElement(n);i.async=1
        ;i.src=o+"?v="+c+"&ts="+1*new Date;r=t.getElementsByTagName(n)[0]
        ;r.parentNode.insertBefore(i,r)})(window,document,"script",
        "avardaCheckoutInit","avardaCheckout","1.0.0",
        "<?php echo (string)$checkout3_js_bundle ?>"
        );
        // Handle external payment methods
        // Additional information available here: <https://docs.avarda.com/checkout-3/more-features/external-payments/>
        var handleByMerchantCallback = function(avardaCheckoutInstance) {
            console.log("Handle external payment here");

            // Un-mount Checkout3 frontend app from the page when external payment is handled
            avardaCheckoutInstance.unmount();
            // Display success message instead of Checkout3 frontend application
            document.getElementById("checkout-form").innerHTML = "<br><h2>External payment handled by merchant!</h2><br>";
        }

        window.avardaCheckoutInit({
            "accessToken": "<?php echo (string) $_SESSION['purchase_JWT_token'] ?>",
            "rootElementId": "checkout-form",
            "redirectUrl": "<?php echo (string) ("$public_url/?redirected=1&purchaseId=" . $_SESSION['purchaseId']) ?>",
            "styles": {},
            "disableFocus": true,
            "handleByMerchantCallback": handleByMerchantCallback,
        });
    </script>
    <hr>
    <!-- Refresh the page restarting the process of authentication and payment initialization, new "Merchant access token" will be created for the session -->
    <button><a href="/">Reset Session Access Token</a></button>
    <button><a href="/getPaymentStatus.php" target="_blank">Get payment status</a></button>
    <hr>
    <button onclick="handleByMerchantCallback(avardaCheckout)">Finish External Payment Manually</button>
</body>

</html>