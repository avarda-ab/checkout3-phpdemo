<?php
session_start();
require "vendor/autoload.php";
require "utils.php";

// Load variables from .env file
// Environment variables have to be passed to the application in order to authenticate, initilize payment and show the checkout app on the frontend
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

$redirect_url = "$public_url/?accessToken=";

if (empty($_GET['accessToken'])) {
    // Get merchant access token
    // This token is used as authentication for all further comunication with the checkout api
    // This token should never be displayed to the user or sent to the frontend of the application
    // CLIENT_ID and CLIENT_SECRET is used for generating a merchant access token
    // POST request is sent to '/api/merchant/accessToken'
    // Additional info can be found in the documentation here: <docs.avarda.com/checkout-3/how-to-get-started/#obtain-merchant-access-token>
    $request_url = "$api_url/api/merchant/accessToken";
    $request_header = "Content-type: application/json\r\n";
    $request_payload = array('clientId' => $client_id, 'clientSecret' => $client_secret);
    $get_access_token_result = send_post_request($request_url, $request_header, $request_payload);
    if ($get_access_token_result === false) { /* Handle error */ };
    $data = json_decode($get_access_token_result);
    $merchant_token = $data->token;
    $_SESSION['merchant_token'] = $merchant_token;

    // Initialize payment in the Checkout
    // Send language, items list and other additional information
    // Exhaustive list of all possibilities available here: <https://docs.avarda.com/checkout-3/how-to-get-started/#initialize-payment>
    // Merchant has to send merchant access token as an authorization in the POST request header:
    //      Authorization: Bearer <merchant_access_token_here>
    // Successfull initialization returns unique JWT session access token and purchase ID
    // Session access token is used to display checkout form on the frontend for the current session
    $request_url = "$api_url/api/merchant/initializePayment";
    $request_header = "Content-type: application/json\r\nAuthorization: Bearer $merchant_token\r\n";
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
    if ($initialize_payment_result === false) { /* Handle error */ };
    $data = json_decode($initialize_payment_result);
    $session_access_token = $data->jwt;
    $_SESSION['session_access_token'] = $session_access_token;
    $purchase_id = $data->purchaseId;
    $_SESSION['purchase_id'] = $purchase_id;

    // Encode session access token so it can be displayed in the URL
    $encoded_access_token = urlencode($session_access_token);
    $_SESSION['encoded_access_token'] = $encoded_access_token;
    if (empty($_GET['accessToken'])) {
        header("Location: $redirect_url $encoded_access_token");
        die();
    };
};

// Update items in the current session
// Merchant has to send merchant access token as an authorization in the POST request header:
//      Authorization: Bearer <merchant_access_token_here>
// Merchant has to provide a purchaseId that was obtained after POST call to /api/merchant/initializePayment
// in order to send a new list of items
// More information can be found here: <https://docs.avarda.com/checkout-3/more-features/update-items/>
if (!empty($_GET['updateItems'])) {
    $update_amount = rand(10, 500);
    $tax_amount = $update_amount * 0.2;
    $request_url = "$api_url/api/merchant/updateItems";
    $request_header = "Content-type: application/json\r\nAuthorization: Bearer " . $_SESSION['merchant_token'] . "\r\n";
    $request_payload = array(
        "purchaseId" => $_SESSION['purchase_id'], "items" => array(array(
            "description" => "Some item",
            "notes" => "",
            "amount" => (int)$update_amount,
            "taxCode" => "20",
            "taxAmount" => (int)$tax_amount
        )),
    );
    $update_items_result = send_post_request($request_url, $request_header, $request_payload);
    if ($update_items_result === false) { /* Handle error */ };
    $data = json_decode($update_items_result);
};
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>AVARDA - PHP Integration Demo</title>
    <meta name="description" content="DemoShop">
    <meta name="author" content="Avarda">
</head>

<body>
    <h1>AVARDA - PHP Integration Demo</h1>
    <button><a href="/?accessToken=<?php echo (string)$_SESSION['encoded_access_token'] ?>&updateItems=1">Update Items</a></button>
    <!-- Checkout form will be displayed in a <div> with a custom unique ID, this ID is passed on in the checkout app initialization -->
    <div id="checkout-form"></div>
    <!-- Include one script with all neccessary JS code for the checkout app in your source code -->
    <script src="<?php echo (string)$checkout3_js_bundle ?>"></script>
    <!-- During the initialization of the checkout app, additional flags can be passed to change appearance or behaviour of the app -->
    <!-- Session access token is passed and required -->
    <!-- Redirect url is neccessary for payment methods that will redirect user to their domain while processing payment (e.g. card payment) -->
    <!-- Additional information available here: <docs.avarda.com/checkout-3/embed-checkout/#showing-the-form> -->
    <script>
        (function(e,t,n,a,s,c,o,i,r){e[a]=e[a]||function(){(e[a].q=e[a].q||[
        ]).push(arguments)};e[a].i=s;i=t.createElement(n);i.async=1
        ;i.src=o+"?v="+c+"&ts="+1*new Date;r=t.getElementsByTagName(n)[0]
        ;r.parentNode.insertBefore(i,r)})(window,document,"script",
        "avardaCheckoutInit","avardaCheckout","1.0.0",
        "https://avdonl-t-checkout-frontend.azurewebsites.net/static/js/main.js"
        );
        
        // Handle external payment methods
        // Additional information available here: <https://docs.avarda.com/checkout-3/more-features/external-payments/>
        var handleByMerchantCallback = function(avardaCheckoutInstance) {
            console.log("Handle external payment here");

            // Unmount Checkout from page when external payment is handled
            avardaCheckoutInstance.unmount();
            // Display success message instead of Checkout application
            document.getElementById("checkout-form").innerHTML = "<br><h2>External payment handled by merchant!</h2><br>";
        }

        window.avardaCheckoutInit({
            "accessToken": "<?php echo (string)$_SESSION['session_access_token'] ?>",
            "rootElementId": "checkout-form",
            "redirectUrl": "<?php echo (string)$redirect_url ?>",
            "styles": {},
            "disableFocus": true,
            "handleByMerchantCallback": handleByMerchantCallback,
        });
    </script>
    <hr>
    <!-- Refresh the page restarting the process of authentication and payment initialization, new token will be created for the session -->
    <button><a href="/">Reset Session Access Token</a></button>
    <button><a href="/getPaymentStatus.php" target="_blank">Get payment status</a></button>
    <hr>
    <button onclick="handleByMerchantCallback(avardaCheckout)">Finish External Payment Manually</button>
</body>

</html>
