<?php 
session_start();
require "vendor/autoload.php";

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

$payment_status_url = "$api_url/api/merchant/getPaymentStatus";
$payment_data = array("purchaseId" => $purchase_id);

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\nAuthorization: Bearer $merchant_token\r\n",
        'method'  => 'POST',
        'content' => json_encode($payment_data)
    )
);
$context  = stream_context_create($options);
$payment_status_result = file_get_contents($payment_status_url, false, $context);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>AVARDA - PHP Integration Demo - Payment Status</title>
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