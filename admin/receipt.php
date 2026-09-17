<?php
session_name('HOA_ADMIN_SESSION');
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

require_once("../config/db.php");
require_once("../vendor/autoload.php");

use Dompdf\Dompdf;
use Dompdf\Options;

$id = $_GET['id'];

$result = mysqli_query($conn, "
    SELECT 
        p.*,
        h.name,
        h.full_address
    FROM payments p
    LEFT JOIN soa_records s ON p.soa_id = s.soa_id
    LEFT JOIN homeowners_masterlist h ON s.homeowner_id = h.homeowner_id
    WHERE p.payment_id = '$id'
");

$row = mysqli_fetch_assoc($result);

if (!$row) {
    die("Receipt not found.");
}

$logoPath = "../assets/css/uploads/banner.png";
$logoSrc = "";

if (file_exists($logoPath)) {
    $logoData = base64_encode(file_get_contents($logoPath));
    $logoSrc = "data:image/png;base64," . $logoData;
}

$html = '
<!DOCTYPE html>
<html>
<head>
<style>
    body {
        font-family: Arial, sans-serif;
        color: #111;
    }

    .receipt-box {
    border: 1px solid #111;
    padding: 15px;
    
    }

    .header-img {
    width: 100%;
    height: auto;
    margin-bottom: 12px;
    }

    .title {
        text-align: center;
        font-weight: bold;
        font-size: 18px;
        margin: 10px 0;
    }

    .line {
        border-top: 1px solid #333;
        margin-bottom: 15px;
    }

    .row {
        margin-bottom: 14px;
        font-size: 14px;
    }

    .label {
        font-weight: bold;
        display: inline-block;
        width: 170px;
    }

    .footer {
        margin-top: 45px;
        text-align: center;
        font-size: 12px;
    }
</style>
</head>

<body>
<div class="receipt-box">

    ' . ($logoSrc ? '<img src="' . $logoSrc . '" class="header-img">' : '') . '

    <div class="title">OFFICIAL PAYMENT RECEIPT</div>
    <div class="line"></div>

    <div class="row">
        <span class="label">Receipt No:</span>
        ' . htmlspecialchars($row['receipt_number']) . '
    </div>

    <div class="row">
        <span class="label">Homeowner Name:</span>
        ' . htmlspecialchars($row['name'] ?? 'N/A') . '
    </div>

    <div class="row">
        <span class="label">Address:</span>
        ' . htmlspecialchars($row['full_address'] ?? 'N/A') . '
    </div>

    <div class="row">
        <span class="label">Amount Paid:</span>
        PHP ' . number_format($row['amount_paid'], 2) . '
    </div>

    <div class="row">
        <span class="label">Payment Purpose:</span>
        ' . htmlspecialchars($row['payment_purpose'] ?? 'Monthly Dues') . '
    </div>

    <div class="row">
        <span class="label">Payment Method:</span>
        ' . htmlspecialchars($row['payment_method']) . '
    </div>

    <div class="row">
        <span class="label">Payment Date:</span>
        ' . htmlspecialchars($row['payment_date']) . '
    </div>

    <div class="row">
        <span class="label">Status:</span>
        ' . ucfirst(htmlspecialchars($row['status'])) . '
    </div>

    <div class="footer">
        This receipt is system-generated and valid without signature.
    </div>

</div>
</body>
</html>
';

$options = new Options();
$options->set("isRemoteEnabled", true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper("A4", "portrait");
$dompdf->render();

$filename = "receipt_" . ($row['receipt_number'] ?? $id) . ".pdf";
$dompdf->stream($filename, ["Attachment" => false]);
exit;
?>