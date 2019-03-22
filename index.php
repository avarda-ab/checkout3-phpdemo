<?php
require "vendor/autoload.php";

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


// Get merchant access token
// This token is used as authentication for all further comunication with the checkout api
// This token should never be displayed to the user or sent to the frontend of the application
// CLIENT_ID and CLIENT_SECRET is used for generating a merchant access token
// POST request is sent to '/api/merchant/accessToken'
// Additional info can be found in the documentation here: <https://docs.avarda.com/?post_type=checkout30&p=1552#accessToken-obtaining>
$access_token_url = "$api_url/api/merchant/accessToken";
$data = array('clientId' => $client_id, 'clientSecret' => $client_secret);

$options = array(
    'http' => array(
        'header'  => "Accept: application/json\r\nContent-type: application/json-patch+json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data)
    )
);

$context  = stream_context_create($options);
$result = file_get_contents($access_token_url, false, $context);
if ($result === false) { /* Handle error */ };

$json_data = json_decode($result);
$merchant_token = $json_data->token;

// Initialize payment in the Checkout
// Send language, items list and other additional information
// Exhaustive list of all possibilities available here: <https://docs.avarda.com/?post_type=checkout30&p=1552#initialize-payment>
// Merchant has to send merchant access token as an authorization in the POST request header:
//      Authorization: Bearer <merchant_access_token_here>
// Successfull initialization returns unique JWT customer access token and purchase ID
// Customer access token is used to display checkout form on the frontend for the current session
$init_payment_url = "$api_url/api/merchant/initializePayment";
$payment_data = array(
    "language" => "English", "items" => array(array(
        "description" => "Some item",
        "notes" => "",
        "amount" => 120,
        "taxCode" => "20",
        "taxAmount" => 42
    )),
);

$opts = array(
    'http' => array(
        'header'  => "Accept: application/json\r\nContent-type: application/json-patch+json\r\nAuthorization: Bearer $merchant_token\r\n",
        'method'  => 'POST',
        'content' => json_encode($payment_data)
    )
);
$cont  = stream_context_create($opts);
$init_result = file_get_contents($init_payment_url, false, $cont);
if ($init_result === false) { /* Handle error */ };

$init_data = json_decode($init_result);
$access_token = $init_data->jwt;
$purchase_id = $init_data->purchaseId;


// Encode customer access token so it can be displayed in the URL
$encoded_access_token = urlencode($access_token);
if (empty($_GET['accessToken'])) {
    header("Location: http://localhost:8000/?accessToken=$encoded_access_token");
    die();
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

    <!-- Checkout form will be displayed in a <div> with a custom unique ID, this ID is passed on in the checkout app initialization -->
    <div id="checkout-form"></div>
    <!-- Include one script with all neccessary JS code for the checkout app in your source code -->
    <script src="<?php echo (string)$checkout3_js_bundle ?>"></script>
    <!-- During the initialization of the checkout app, additional flags can be passed to change appearance or behaviour of the app -->
    <!-- Customer access token is passed and required -->
    <!-- Redirect url is neccessary for payment methods that will redirect user to their domain while processing payment (e.g. card payment) -->
    <!-- Additional information available here: <https://docs.avarda.com/?post_type=checkout30&p=1552#showing-the-form> -->
    <script>
        window.avardaCheckoutInit({
            "accessToken": "<?php echo (string)$access_token ?>",
            "rootElementId": "checkout-form",
            "redirectUrl": "<?php echo (string)$public_url ?>",
            "styles": {},
            "disableFocus": true,
        });
    </script>
    <hr>
    <!-- Refresh the page restarting the process of authentication and payment initialization, new token will be created for the session -->
    <button><a href="/">Reset Access Token</a></button>
</body>

</html> 