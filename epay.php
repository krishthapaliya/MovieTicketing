<?php
include('includes/db_connect.php'); // Include the database connection
include('includes/header.php'); // Include the header


$folder_name = basename(dirname(__FILE__));
$amt = $_GET['price'];
$email = $_GET['email'];
$date = $_GET['date'];
$showtime_id = $_GET['showtimeid'];
$booked_seats = $_GET['seats'];

// Define charges
$tax = 10;
$service_charge = 10;

// Generate random UUID for the transaction
function generateRandomUuid()
{
    return sprintf(
        '%s-%s-%s-%s-%s',
        bin2hex(random_bytes(4)),
        bin2hex(random_bytes(2)),
        bin2hex(random_bytes(2)),
        bin2hex(random_bytes(2)),
        bin2hex(random_bytes(6))
    );
}

// Generate transaction UUID
$trans_uuid = generateRandomUuid();

// Calculate total amount
$total_amt = $amt + $tax + $service_charge;

// Prepare signed field names
$parameter = "total_amount,transaction_uuid,product_code";
$signed_field_names = "total_amount=$total_amt,transaction_uuid=$trans_uuid,product_code=EPAYTEST";
$secret_key = "8gBm/:&EnhH.1/q";

// Generate signature
$s = hash_hmac("sha256", $signed_field_names, $secret_key, true);

?>

<link rel="stylesheet" href="css/epay.css">
<div class="invoice">
    <h2>Payment Details</h2>
    <table>
        <tr>
            <th>Description</th>
            <th>Amount (NPR)</th>
        </tr>
        <tr>
            <td>Initial Amount</td>
            <td><?= $amt ?></td>
        </tr>
        <tr>
            <td>Service Charge</td>
            <td><?= $service_charge ?></td>
        </tr>
        <tr>
            <td>Tax</td>
            <td><?= $tax ?></td>
        </tr>
        <tr>
            <td><strong>Total Amount</strong></td>
            <td><strong><?= $total_amt ?></strong></td>
        </tr>
    </table>

    <form action="https://rc-epay.esewa.com.np/api/epay/main/v2/form" method="POST" id="epay-form">
        <input type="hidden" name="amount" value="<?= $amt ?>" required>
        <input type="hidden" name="email" value="<?= $amt ?>" required>
        <input type="hidden" name="tax_amount" value="<?= $tax ?>" required>
        <input type="hidden" name="total_amount" value="<?= $total_amt ?>" id="total_amt" required>
        <input type="hidden" name="transaction_uuid" id="trans_uuid" value="<?= $trans_uuid ?>" required>
        <input type="hidden" name="product_code" value="EPAYTEST" required>
        <input type="hidden" name="product_service_charge" value="<?= $service_charge ?>" required>
        <input type="hidden" name="product_delivery_charge" value="0" required>
        <input type="hidden" name="success_url" value="http://localhost/MovieTicketing/payment_success.php?email=<?= $email ?>&seats=<?= $booked_seats ?>&price=<?= $amt ?>&showtimeid=<?= $showtime_id ?>" required>
        <input type="hidden" name="failure_url" value="http://localhost/MovieTicketing/payment_failure.php" required>
        <input type="hidden" name="signed_field_names" value="total_amount,transaction_uuid,product_code" required>
        <input type="hidden" name="signature" value="<?= base64_encode($s) ?>" required>
        <button type="submit" class="pay-button">Pay with eSewa</button>
    </form>
</div>