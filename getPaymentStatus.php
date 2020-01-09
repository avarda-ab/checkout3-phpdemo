<?php
session_start();
require "vendor/autoload.php";
require "utils.php";

$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();
$dotenv->required('CLIENT_ID')->notEmpty();
$dotenv->required('CLIENT_SECRET')->notEmpty();
$dotenv->required('CHECKOUT3_BACKEND_API_URL')->notEmpty();
$dotenv->required('CHECKOUT3_JS_BUNDLE')->notEmpty();
$dotenv->required('PUBLIC_URL')->notEmpty();

$api_url = getenv('CHECKOUT3_BACKEND_API_URL');

$merchant_access_token = $_SESSION['merchant_access_token'];
$purchase_id = $_SESSION['purchase_id'];

// Get payment status by "PurchaseId" 
// Send "PurchaseId" to /api/merchant/paymentStatus?PurchaseId=<purchase_id_here>
// More info available here: <https://docs.avarda.com/checkout-3/confirmation/#get-payment-status>
// Merchant has to send "Merchant access token" as an authorization in the GET request header
//      Authorization: Bearer <merchant_access_token_here>
// Successful request returns information about the payment
$request_payload = array('PurchaseId' => $purchase_id);
// Create encoded HTTP query according to the RFC3986
$query_parameters = http_build_query($request_payload, null, '&', PHP_QUERY_RFC3986);
$request_url = "$api_url/api/merchant/paymentStatus?$query_parameters";
$request_header = "Authorization: Bearer $merchant_access_token";
$payment_status_result = send_get_request($request_url, $request_header)
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>AVARDA - Checkout3 - PHP Integration Demo - Get Payment Status</title>
    <meta name="description" content="DemoShop">
    <meta name="author" content="Avarda">
</head>

<body>
    <h1> Payment status for <?php echo (string) $purchase_id ?></h1>
    <?php
    if ($payment_status_result === false) { /* Handle error */
    } else {
        $payment_status_response = json_decode($payment_status_result, JSON_PRETTY_PRINT);
        echo "<pre>";
        echo json_encode($payment_status_response, JSON_PRETTY_PRINT);
        echo "</pre>";
    };
    ?>
    <hr>
    <button><a href="/getPaymentStatus.php">Refresh Payment Status</a></button>
</body>

</html>