<?php
require_once '../includes/auth.php';

$user = current_user();
if (!$user || !is_customer()) {
    header('Location: ../home-customer.php');
    exit();
}

require_once '../includes/db.php';

$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if ($booking_id <= 0) {
    die('Invalid booking ID');
}

try {
    $pdo = db();
    $user_id = $user['id'];
    
    // Get booking details
    $query = "SELECT 
                b.*,
                u.full_name as customer_name,
                u.email as customer_email,
                u.phone as customer_phone,
                br.name as branch_name,
                br.address as branch_address,
                sc.name as category_name,
                r.name as room_name,
                t.name as therapist_name
              FROM bookings b
              JOIN users u ON b.user_id = u.id
              JOIN branches br ON b.branch_id = br.id
              JOIN service_categories sc ON b.category_id = sc.id
              JOIN rooms r ON b.room_id = r.id
              JOIN therapists t ON b.therapist_id = t.id
              WHERE b.id = :booking_id AND b.user_id = :user_id
              AND b.status IN ('approved', 'complete')";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':booking_id' => $booking_id,
        ':user_id' => $user_id
    ]);
    
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        die('Booking not found or cannot be downloaded');
    }
    
    // Check if TCPDF exists
    if (file_exists('../vendor/autoload.php')) {
        // Use TCPDF if available
        require_once '../vendor/autoload.php';
        generatePDFWithTCPDF($booking);
    } else {
        // Fallback to simple PDF generation
        generateSimplePDF($booking);
    }

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

// Function using TCPDF
function generatePDFWithTCPDF($booking) {
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    $pdf->SetCreator('Rebah Massage');
    $pdf->SetAuthor('Rebah Massage');
    $pdf->SetTitle('Invoice #' . str_pad($booking['id'], 3, '0', STR_PAD_LEFT));
    
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);
    
    $bookingCode = str_pad($booking['id'], 3, '0', STR_PAD_LEFT);
    $bookingDate = date('d-m-Y', strtotime($booking['booking_date']));
    $startTime = date('H:i', strtotime($booking['start_time']));
    $endTime = date('H:i', strtotime($booking['end_time']));
    
    $html = '
    <style>
        body { font-family: Arial, sans-serif; color: #46486F; }
        .header { margin-bottom: 30px; }
        .logo { width: 120px; float: left; }
        .invoice-title { font-size: 36px; color: #46486F; font-weight: bold; letter-spacing: 3px; text-align: right; margin-top: 20px; }
        .invoice-to { margin: 30px 0; clear: both; }
        .invoice-to h3 { color: #46486F; font-size: 14px; margin-bottom: 8px; }
        .invoice-to p { color: #46486F; font-size: 12px; margin: 3px 0; }
        .invoice-date { text-align: right; color: #46486F; font-size: 16px; font-weight: bold; margin: 20px 0; }
        .section-title { color: #46486F; font-size: 16px; font-weight: bold; margin: 20px 0 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead { background-color: #46486F; color: white; }
        th { padding: 12px; text-align: left; font-weight: 500; font-size: 12px; }
        td { padding: 12px; color: #46486F; font-size: 11px; border-bottom: 1px solid rgba(70, 72, 111, 0.1); }
        .highlight-row td { border-bottom: 3px solid #46486F; }
        .thank-you { text-align: center; color: #46486F; font-size: 16px; font-weight: bold; margin: 30px 0; padding: 15px 0; border-top: 2px solid #46486F; border-bottom: 2px solid #46486F; }
        .footer { margin-top: 30px; }
        .location { width: 48%; float: left; }
        .location h4 { color: #46486F; font-size: 14px; margin-bottom: 8px; font-weight: bold; }
        .location p { color: #46486F; font-size: 11px; line-height: 1.6; margin: 2px 0; }
    </style>
    
    <div class="header">
        <div class="invoice-title">INVOICE</div>
    </div>
    
    <div class="invoice-to">
        <h3>Invoice To:</h3>
        <p><strong>' . htmlspecialchars($booking['customer_name']) . '</strong></p>
        <p>' . htmlspecialchars($booking['customer_email']) . '</p>
        <p>' . htmlspecialchars($booking['customer_phone']) . '</p>
    </div>
    
    <div class="invoice-date">' . date('F j, Y') . '</div>
    
    <div class="section-title">Customer Information</div>
    <table>
        <thead>
            <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>Phone</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>' . htmlspecialchars($booking['customer_name']) . '</td>
                <td>' . htmlspecialchars($booking['customer_email']) . '</td>
                <td>' . htmlspecialchars($booking['customer_phone']) . '</td>
            </tr>
        </tbody>
    </table>
    
    <div class="section-title">Booking Details</div>
    <table>
        <thead>
            <tr>
                <th>Branch</th>
                <th>Service</th>
                <th>Date</th>
                <th>Time</th>
                <th>Duration</th>
                <th>Room</th>
                <th>Therapist</th>
            </tr>
        </thead>
        <tbody>
            <tr class="highlight-row">
                <td>' . htmlspecialchars($booking['branch_name']) . '</td>
                <td>' . htmlspecialchars($booking['category_name']) . '</td>
                <td>' . $bookingDate . '</td>
                <td>' . $startTime . '-' . $endTime . '</td>
                <td>' . $booking['duration'] . ' mins</td>
                <td>Room ' . htmlspecialchars($booking['room_name']) . '</td>
                <td>' . htmlspecialchars($booking['therapist_name']) . '</td>
            </tr>
        </tbody>
    </table>
    
    <div class="thank-you">Thank You for Your Business</div>
    
    <div class="footer">
        <div class="location">
            <h4>Rebah Fatmawati</h4>
            <p>Fatmawati Raya Street, West Cilandak,</p>
            <p>Cilandak, South Jakarta, 12430</p>
            <p>+62 822-9999-4259</p>
        </div>
        <div class="location" style="float: right;">
            <h4>Rebah Menteng</h4>
            <p>51 Teuku Cik Ditiro Street, Menteng,</p>
            <p>Central Jakarta, 10310</p>
            <p>+62 822-9999-4263</p>
        </div>
    </div>
    ';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('Rebah_Invoice_' . $bookingCode . '.pdf', 'D');
}

// Fallback simple PDF generation without library
function generateSimplePDF($booking) {
    $bookingCode = str_pad($booking['id'], 3, '0', STR_PAD_LEFT);
    $bookingDate = date('d-m-Y', strtotime($booking['booking_date']));
    $startTime = date('H:i', strtotime($booking['start_time']));
    $endTime = date('H:i', strtotime($booking['end_time']));
    
    // Set headers for download
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="Rebah_Invoice_' . $bookingCode . '.html"');
    
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Rebah Invoice</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                background-color: #f5f5f5;
                padding: 30px 20px;
            }

            .invoice-container {
                max-width: 800px;
                margin: 0 auto;
                background-color: white;
                padding: 50px;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
            }

            .header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 50px;
            }

            .logo {
                width: 150px;
                height: auto;
            }

            .invoice-title {
                font-size: 36px;
                color: #46486F;
                font-weight: 600;
                letter-spacing: 3px;
            }

            .invoice-to {
                margin-bottom: 40px;
            }

            .invoice-to h3 {
                color: #46486F;
                font-size: 16px;
                margin-bottom: 10px;
                font-weight: 600;
            }

            .invoice-to p {
                color: #46486F;
                font-size: 14px;
                margin: 5px 0;
            }

            .invoice-date {
                text-align: right;
                color: #46486F;
                font-size: 18px;
                font-weight: 600;
                margin-top: -30px;
                margin-bottom: 30px;
            }

            .section-title {
                color: #46486F;
                font-size: 18px;
                font-weight: 600;
                margin-bottom: 15px;
                margin-top: 30px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
            }

            thead {
                background-color: #46486F;
                color: white;
            }

            th {
                padding: 15px;
                text-align: left;
                font-weight: 500;
                font-size: 14px;
            }

            td {
                padding: 15px;
                color: #46486F;
                font-size: 14px;
                border-bottom: 1px solid rgba(70, 72, 111, 0.1);
            }

            tbody tr {
                background-color: white;
            }

            tbody tr:hover {
                background-color: #f7fafc;
            }

            .highlight-row {
                border-bottom: 3px solid #46486F !important;
            }

            .highlight-row td {
                border-bottom: 3px solid #46486F !important;
            }

            .thank-you {
                text-align: center;
                color: #46486F;
                font-size: 18px;
                font-weight: 600;
                margin: 40px 0;
                padding: 20px 0;
                border-top: 2px solid #46486F;
                border-bottom: 2px solid #46486F;
            }

            .footer {
                display: flex;
                justify-content: space-between;
                margin-top: 40px;
                gap: 50px;
            }

            .location {
                flex: 1;
                max-width: 48%;
            }

            .location h4 {
                color: #46486F;
                font-size: 16px;
                margin-bottom: 10px;
                font-weight: 600;
            }

            .location p {
                color: #46486F;
                font-size: 13px;
                line-height: 1.6;
                margin: 3px 0;
            }

            .no-print {
                text-align: center;
                margin-top: 30px;
            }

            .print-btn {
                padding: 12px 30px;
                background: #46486F;
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
            }

            .print-btn:hover {
                background: #363855;
            }

            @media print {
                body {
                    background-color: white;
                    padding: 0;
                }
                .invoice-container {
                    box-shadow: none;
                    padding: 20px;
                }
                .no-print {
                    display: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="invoice-container">
            <div class="header">
                <img src="../assets/images/logo.png" alt="Rebah Massage & Reflexology" class="logo">
                <div class="invoice-title">INVOICE</div>
            </div>

            <div class="invoice-to">
                <h3>Invoice To :</h3>
                <p><strong>' . htmlspecialchars($booking['customer_name']) . '</strong></p>
                <p>' . htmlspecialchars($booking['customer_email']) . '</p>
                <p>' . htmlspecialchars($booking['customer_phone']) . '</p>
            </div>

            <div class="invoice-date">' . date('F j, Y') . '</div>

            <h3 class="section-title">Customer Information</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Phone</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>' . htmlspecialchars($booking['customer_name']) . '</td>
                        <td>' . htmlspecialchars($booking['customer_email']) . '</td>
                        <td>' . htmlspecialchars($booking['customer_phone']) . '</td>
                    </tr>
                </tbody>
            </table>

            <h3 class="section-title">Booking Details</h3>
            <table>
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Duration</th>
                        <th>Room</th>
                        <th>Therapist</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="highlight-row">
                        <td>' . htmlspecialchars($booking['branch_name']) . '</td>
                        <td>' . htmlspecialchars($booking['category_name']) . '</td>
                        <td>' . $bookingDate . '</td>
                        <td>' . $startTime . '-' . $endTime . '</td>
                        <td>' . $booking['duration'] . ' mins</td>
                        <td>Room ' . htmlspecialchars($booking['room_name']) . '</td>
                        <td>' . htmlspecialchars($booking['therapist_name']) . '</td>
                    </tr>
                </tbody>
            </table>

            <div class="thank-you">Thank You for Your Business</div>

            <div class="footer">
                <div class="location">
                    <h4>Rebah Fatmawati</h4>
                    <p>Fatmawati Raya Street, West Cilandak,</p>
                    <p>Cilandak, South Jakarta, 12430</p>
                    <p>+62 822-9999-4259</p>
                </div>
                <div class="location">
                    <h4>Rebah Menteng</h4>
                    <p>51 Teuku Cik Ditiro Street, Menteng,</p>
                    <p>Central Jakarta, 10310</p>
                    <p>+62 822-9999-4263</p>
                </div>
            </div>
        </div>
        
        <div class="no-print">
            <button onclick="window.print()" class="print-btn">Print Receipt</button>
        </div>
    </body>
    </html>';
}
?>