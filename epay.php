<?php
include('includes/db_connect.php'); // Include the database connection
include('includes/header.php'); // Include the header

$folder_name = basename(dirname(__FILE__));
$amt = isset($_GET['price']) ? $_GET['price'] : 0;
$email = isset($_GET['email']) ? $_GET['email'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';
$showtime_id = isset($_GET['showtimeid']) ? $_GET['showtimeid'] : '';
$booked_seats = isset($_GET['seats']) ? $_GET['seats'] : '';

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

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pay - MovieMania</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg:#141414;
      --card:#1f1f1f;
      --muted:#b3b3b3;
      --accent:#e50914; /* Netflix red */
      --glass: rgba(255,255,255,0.04);
    }
    *{box-sizing:border-box;font-family: 'Montserrat', sans-serif}
    body{margin:0;background:var(--bg);color:#fff}
    .container{max-width:1200px;margin:40px auto;padding:20px}

    /* Header */
    .site-header{display:flex;align-items:center;gap:20px;padding:10px 0}
    .logo{display:flex;align-items:center;gap:10px}
    .logo .mark{width:48px;height:48px;background:var(--accent);border-radius:4px;display:flex;align-items:center;justify-content:center;font-weight:800}
    .logo h1{font-size:20px;margin:0;color:#fff}

    /* Layout */
    .layout{display:grid;grid-template-columns: 1fr 420px;gap:24px;margin-top:18px}

    /* Left: hero/movie preview */
    .hero{
      background: linear-gradient(90deg, rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('../uploaded_img/<?php echo isset($booked_seats) ? htmlspecialchars($movie['image'] ?? '') : '' ?>') center/cover no-repeat;
      border-radius:12px;padding:28px;min-height:420px;position:relative;overflow:hidden;border:1px solid rgba(255,255,255,0.04)
    }
    .hero .title{font-size:36px;font-weight:700;margin:0 0 12px}
    .hero .meta{color:var(--muted);margin-bottom:18px}
    .movie-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px}
    .movie-card{background:var(--card);border-radius:8px;overflow:hidden;border:1px solid rgba(255,255,255,0.03)}
    .movie-card img{width:100%;height:170px;object-fit:cover;display:block}
    .movie-card .mc-body{padding:10px}
    .movie-card h4{margin:0 0 6px;font-size:14px}
    .movie-card p{margin:0;font-size:12px;color:var(--muted)}

    /* Right: payment panel */
    .payment-panel{background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));border-radius:12px;padding:22px;border:1px solid rgba(255,255,255,0.04)}
    .payment-panel h3{margin:0 0 8px;color:#fff}
    .price-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px dashed rgba(255,255,255,0.03);align-items:center}
    .price-row strong{font-size:18px}
    .total{display:flex;justify-content:space-between;padding-top:12px;font-size:20px;font-weight:700}
    .pay-button{margin-top:18px;background:var(--accent);border:none;color:#fff;padding:12px;border-radius:8px;width:100%;font-weight:700;cursor:pointer}
    .pay-button:hover{filter:brightness(.95)}

    .muted{color:var(--muted);font-size:13px}

    /* Footer small */
    .secure{display:flex;align-items:center;gap:8px;margin-top:12px;color:var(--muted);font-size:13px}

    /* Responsive */
    @media (max-width:900px){.layout{grid-template-columns:1fr}.hero{min-height:320px}}
  </style>
</head>
<body>
  <div class="container">
    <header class="site-header">
      <div class="logo">
        <div class="mark">MM</div>
        <div>
          <h1>MovieMania</h1>
          <div class="muted">Secure Payment</div>
        </div>
      </div>
    </header>

    <main class="layout">
      <section class="hero">
        <h2 class="title">Confirm your booking</h2>
        <div class="meta">Showtime: <span class="muted"><?php echo htmlspecialchars($date); ?></span></div>

        <div class="movie-grid">
          <!-- Example thumbnails; replace with dynamic items if desired -->
          <div class="movie-card">
             
            <div class="mc-body">
              <h4><?php echo htmlspecialchars($movie['name'] ?? 'Selected Movie'); ?></h4>
              <p class="muted">Seats: <?php echo htmlspecialchars($booked_seats); ?></p>
            </div>
          </div>

          <div class="movie-card">
           
            <div class="mc-body">
              <h4>Extras</h4>
              <p class="muted">Snacks &amp; beverages available at theatre</p>
            </div>
          </div>

        </div>

      </section>

      <aside class="payment-panel">
        <h3>Payment summary</h3>
        <div class="muted">Transaction ID: <br><small><?php echo htmlspecialchars($trans_uuid); ?></small></div>

        <div class="price-row">
          <div class="muted">Ticket Price</div>
          <div>NPR <?php echo number_format((float)$amt,2); ?></div>
        </div>
        <div class="price-row">
          <div class="muted">Service Charge</div>
          <div>NPR <?php echo number_format((float)$service_charge,2); ?></div>
        </div>
        <div class="price-row">
          <div class="muted">Tax</div>
          <div>NPR <?php echo number_format((float)$tax,2); ?></div>
        </div>

        <div class="total">
          <div class="muted">Total</div>
          <div>NPR <?php echo number_format((float)$total_amt,2); ?></div>
        </div>

        <form action="https://rc-epay.esewa.com.np/api/epay/main/v2/form" method="POST" id="epay-form">
            <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amt); ?>" required>
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            <input type="hidden" name="tax_amount" value="<?php echo htmlspecialchars($tax); ?>" required>
            <input type="hidden" name="total_amount" value="<?php echo htmlspecialchars($total_amt); ?>" id="total_amt" required>
            <input type="hidden" name="transaction_uuid" id="trans_uuid" value="<?php echo htmlspecialchars($trans_uuid); ?>" required>
            <input type="hidden" name="product_code" value="EPAYTEST" required>
            <input type="hidden" name="product_service_charge" value="<?php echo htmlspecialchars($service_charge); ?>" required>
            <input type="hidden" name="product_delivery_charge" value="0" required>
            <input type="hidden" name="success_url" value="http://localhost/MovieTicketing/payment_success.php?email=<?php echo urlencode($email); ?>&seats=<?php echo urlencode($booked_seats); ?>&price=<?php echo urlencode($amt); ?>&showtimeid=<?php echo urlencode($showtime_id); ?>" required>
            <input type="hidden" name="failure_url" value="http://localhost/MovieTicketing/payment_failure.php" required>
            <input type="hidden" name="signed_field_names" value="total_amount,transaction_uuid,product_code" required>
            <input type="hidden" name="signature" value="<?php echo base64_encode($s); ?>" required>
            <button type="submit" class="pay-button">Pay with eSewa</button>
        </form>

        <div class="secure">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 1L3 5v6c0 5 3.8 9.2 9 11 5.2-1.8 9-6 9-11V5l-9-4z" stroke="#b3b3b3" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <div>Secure payment processed by eSewa</div>
        </div>

      </aside>

    </main>
  </div>
</body>
</html>