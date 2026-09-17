<?php
session_name('HOA_ADMIN_SESSION');
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

require_once "../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;


$type   = $_GET['type']   ?? '';
$format = $_GET['format'] ?? '';

// ─── FETCH DATA BASED ON TYPE ──────────────────────────────

$title = '';
$headers = [];
$rows = [];

if ($type == 'payments') {
    $title = 'Payment Report';
    $headers = ['Month', 'Total Collected (₱)'];

    $q = mysqli_query($conn, "
    SELECT DATE_FORMAT(payment_date, '%M %Y') as month,
           SUM(amount_paid) as total
    FROM payments
    WHERE status = 'approved'
    GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
    ORDER BY MIN(payment_date) DESC
");
while ($row = mysqli_fetch_assoc($q)) {
    $rows[] = [$row['month'], number_format($row['total'], 2)];
}

} elseif ($type == 'rentals') {
    $title = 'Rental Report';
    $headers = ['Item', 'Times Rented', 'Total Earned (₱)'];

    $q = mysqli_query($conn, "
        SELECT ri.item_name as name,
               COUNT(rri.item_id) as times_rented,
               SUM(rri.subtotal) as total_earned
        FROM rental_request_items rri
        JOIN rental_items ri ON rri.item_id = ri.item_id
        JOIN rental_requests rr ON rri.rental_id = rr.rental_id
        WHERE rr.status = 'completed'
        GROUP BY rri.item_id
        ORDER BY times_rented DESC
    ");
    while ($row = mysqli_fetch_assoc($q)) {
        $rows[] = [$row['name'], $row['times_rented'] . 'x', number_format($row['total_earned'], 2)];
    }

} elseif ($type == 'documents') {
    $title = 'Document Report';
    $headers = ['Document Type', 'Total', 'Completed', 'Pending', 'Rejected'];

    $q = mysqli_query($conn, "
        SELECT document_type,
               COUNT(*) as total,
               SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
               SUM(CASE WHEN status = 'pending'   THEN 1 ELSE 0 END) as pending,
               SUM(CASE WHEN status = 'rejected'  THEN 1 ELSE 0 END) as rejected
        FROM document_requests
        GROUP BY document_type
        ORDER BY total DESC
    ");
    while ($row = mysqli_fetch_assoc($q)) {
        $rows[] = [
            $row['document_type'],
            $row['total'],
            $row['completed'],
            $row['pending'],
            $row['rejected']
        ];
    }

} elseif ($type == 'incidents') {
    $title = 'Incident Report';
    $headers = ['Category', 'Total', 'Open', 'In Progress', 'Resolved'];

    $q = mysqli_query($conn, "
        SELECT subject as category,
               COUNT(*) as total,
               SUM(CASE WHEN status = 'open'        THEN 1 ELSE 0 END) as open,
               SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
               SUM(CASE WHEN status = 'closed'    THEN 1 ELSE 0 END) as resolved
        FROM support_tickets
        GROUP BY subject
        ORDER BY total DESC
    ");
    while ($row = mysqli_fetch_assoc($q)) {
        $rows[] = [
            ucfirst($row['category']),
            $row['total'],
            $row['open'],
            $row['in_progress'],
            $row['resolved']
        ];
    }

} elseif ($type == 'audit') {
    $title = 'Audit Logs Report';
    $headers = ['ID', 'User', 'Action', 'Type', 'Date'];

    $q = mysqli_query($conn, "
        SELECT a.*, u.first_name, u.last_name
        FROM audit_logs a
        LEFT JOIN users u ON a.user_id = u.user_id
        ORDER BY a.created_at DESC
    ");
    while ($row = mysqli_fetch_assoc($q)) {
        $rows[] = [
            $row['log_id'],
            trim(($row['first_name'] ?? 'System') . ' ' . ($row['last_name'] ?? '')),
            $row['action'],
            $row['log_type'],
            $row['created_at']
        ];
    }
}

// ─── EXPORT PDF ────────────────────────────────────────────

if ($format == 'pdf') {

    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $dompdf = new Dompdf($options);

    // Build HTML table
    $html = "
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #111; }
        h2   { color: #dc1623; margin-bottom: 4px; }
        p    { color: #555; font-size: 11px; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th {
            background: #dc1623;
            color: white;
            padding: 10px 12px;
            text-align: left;
            font-size: 12px;
        }
        td {
            padding: 9px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 12px;
        }
        tr:nth-child(even) td { background: #f9fafb; }
    </style>

    <h2>Sapilara Lower HOA</h2>
    <p>$title &nbsp;|&nbsp; Generated: " . date('F d, Y h:i A') . "</p>

    <table>
        <thead>
            <tr>";

    foreach ($headers as $h) {
        $html .= "<th>$h</th>";
    }
    $html .= "</tr></thead><tbody>";

    if (empty($rows)) {
        $html .= "<tr><td colspan='" . count($headers) . "' style='text-align:center;color:#888;'>No data available.</td></tr>";
    } else {
        foreach ($rows as $row) {
            $html .= "<tr>";
            foreach ($row as $cell) {
                $html .= "<td>" . htmlspecialchars($cell) . "</td>";
            }
            $html .= "</tr>";
        }
    }

    $html .= "</tbody></table>";

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream($type . '_report_' . date('Ymd') . '.pdf', ['Attachment' => true]);
    exit;
}

// ─── EXPORT EXCEL ──────────────────────────────────────────

if ($format == 'excel') {

    $filename = $type . '_report_' . date('Ymd') . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head><meta charset="UTF-8"></head><body>';
    echo '<table border="1" cellpadding="6" cellspacing="0">';

    // Title rows
    echo '<tr><td colspan="' . count($headers) . '" style="font-size:14pt;font-weight:bold;color:#DC1623;">Sapilara Lower HOA — ' . htmlspecialchars($title) . '</td></tr>';
    echo '<tr><td colspan="' . count($headers) . '" style="font-size:10pt;color:#888888;">Generated: ' . date('F d, Y h:i A') . '</td></tr>';
    echo '<tr><td colspan="' . count($headers) . '"></td></tr>';

    // Header row
    echo '<tr>';
    foreach ($headers as $h) {
        echo '<th style="background-color:#DC1623;color:#FFFFFF;font-weight:bold;padding:8px;">' . htmlspecialchars($h) . '</th>';
    }
    echo '</tr>';

    // Data rows
    if (empty($rows)) {
        echo '<tr><td colspan="' . count($headers) . '" style="text-align:center;color:#888;">No data available.</td></tr>';
    } else {
        $i = 0;
        foreach ($rows as $row) {
            $bg = ($i % 2 == 0) ? '#FFFFFF' : '#F9FAFB';
            echo '<tr style="background-color:' . $bg . ';">';
            foreach ($row as $cell) {
                echo '<td style="padding:7px;">' . htmlspecialchars($cell) . '</td>';
            }
            echo '</tr>';
            $i++;
        }
    }

    echo '</table></body></html>';
    exit;
}

// If no valid type/format
echo "<script>alert('Invalid export request.'); window.history.back();</script>";