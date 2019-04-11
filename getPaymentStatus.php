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

$merchant_token = $_SESSION['merchant_token'];
$purchase_id = $_SESSION['purchase_id'];

// Get payment status by purchase ID 
// Send purchase ID to /api/merchant/getPaymentStatus
// More info available here: <https://docs.avarda.com/checkout-3/confirmation/#get-payment-status>
// Merchant has to send merchant access token as an authorization in the POST request heeader
//      Authorization: Bearer <merchant_access_token_here>
// Successful request returns information about the payment
$request_url = "$api_url/api/merchant/getPaymentStatus";
$request_header = "Content-type: application/json\r\nAuthorization: Bearer $merchant_token\r\n";
$request_payload = array("purchaseId" => $purchase_id);
$payment_status_result = send_post_request($request_url, $request_header, $request_payload)
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>AVARDA - PHP Integration Demo - Get Payment Status</title>
    <meta name="description" content="DemoShop">
    <meta name="author" content="Avarda">
</head>

<body>
    <h1> Payment status for <?php echo (string)$purchase_id ?></h1>
    <?php
    if ($payment_status_result === false) { /* Handle error */ } else {
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