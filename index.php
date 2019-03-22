<?php
require "vendor/autoload.php";

$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();
$dotenv->required('CLIENT_ID')->notEmpty();
$dotenv->required('CLIENT_SECRET')->notEmpty();

$api_url = getenv('CHECKOUT3_BACKEND_API_URL');
$checkout3_js_bundle = getenv('CHECKOUT3_JS_BUNDLE');
$public_url = getenv('PUBLIC_URL');
$client_id =  getenv('CLIENT_ID');
$client_secret = getenv('CLIENT_SECRET');


// 1. get merchnat.... 
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
if ($result === false) { /* Handle error */ }

$json_data = json_decode($result);
$merchant_token = $json_data->token;


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
if ($init_result === false) { /* Handle error */ }


$init_data = json_decode($init_result);
$access_token = $init_data->jwt;
$purchase_id = $init_data->purchaseId;


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

    <div id="checkout-form"></div>
    <script src="<?php echo (string) $checkout3_js_bundle ?>"></script>
    <script>
        window.avardaCheckoutInit({
            "accessToken": "<?php echo (string) $access_token ?>",
            "rootElementId": "checkout-form",
            "redirectUrl": "<?php echo (string) $public_url ?>",
            "styles": {},
            "disableFocus": true,
        });
    </script>

</body>

</html> 