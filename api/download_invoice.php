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

// Shared HTML Generation Function (Table-based for TCPDF compatibility)
function getInvoiceHTML($booking) {
    $bookingCode = str_pad($booking['id'], 3, '0', STR_PAD_LEFT);
    
    // PERUBAHAN 1: Gunakan tanggal real-time saat download (bukan booking_date)
    $currentDate = date('F j, Y'); // Tanggal saat ini
    
    $formattedDate = date('d-m-Y', strtotime($booking['booking_date']));
    $startTime = date('H:i', strtotime($booking['start_time']));
    $endTime = date('H:i', strtotime($booking['end_time']));
    $isPdf = func_num_args() > 1 ? func_get_arg(1) : false;

    // Base URL for images - handle both PDF and Web contexts if needed
    if ($isPdf) {
        $logoPath = realpath(__DIR__ . '/../public/logorebah.jpg');
    } else {
        $logoPath = '../public/logorebah.jpg';
    }

    $html = '
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-family: Helvetica, Arial, sans-serif; color: #46486F; font-size: 12px; border-collapse: collapse;">
        <!-- Header -->
        <tr>
            <td width="50%" align="left" style="vertical-align: middle;">
                <img src="' . $logoPath . '" style="width: 150px; height: auto;" height="50" />
            </td>
            <td width="50%" align="right" style="vertical-align: middle; font-size: 30px; font-weight: bold; color: #46486F; letter-spacing: 2px;">
                INVOICE
            </td>
        </tr>
        <tr><td colspan="2" height="30"></td></tr>

        <!-- Info Section -->
        <tr>
            <td width="60%" valign="top">
                <div style="font-weight: bold; font-size: 14px; color: #46486F; margin-bottom: 5px;">Invoice To :</div>
                <div style="font-size: 12px; color: #46486F; line-height: 1.4;">
                    <strong>' . htmlspecialchars($booking['customer_name']) . '</strong><br>
                    ' . htmlspecialchars($booking['customer_email']) . '<br>
                    ' . htmlspecialchars($booking['customer_phone']) . '
                </div>
            </td>
            <td width="40%" valign="bottom" align="right">
                <div style="font-size: 16px; font-weight: bold; color: #46486F;">' . $currentDate . '</div>
            </td>
        </tr>
        <tr><td colspan="2" height="30"></td></tr>

        <!-- Customer Info -->
        <tr>
            <td colspan="2" style="font-size: 14px; font-weight: bold; color: #46486F; border-bottom: 2px solid #46486F; padding-bottom: 5px;">
                Customer Information
            </td>
        </tr>
        <tr><td colspan="2" height="10"></td></tr>
    </table>

    <table cellpadding="8" cellspacing="0" width="100%" style="font-family: Helvetica; font-size: 11px; border-collapse: collapse;">
        <tr style="background-color: #46486F;">
            <td width="33%" style="font-weight: bold; color: #FFFFFF; padding: 10px;">Nama</td>
            <td width="34%" style="font-weight: bold; text-align:center; color: #FFFFFF; padding: 10px;">Email</td>
            <td width="33%" style="font-weight: bold; text-align:center; color: #FFFFFF; padding: 10px;">Phone</td>
        </tr>
        <tr style="background-color: #FFFFFF;">
            <td style="border-bottom: 1px solid #46486F; color: #46486F; padding: 8px;">'.htmlspecialchars($booking['customer_name']).'</td>
            <td style="border-bottom: 1px solid #46486F; text-align:center; color: #46486F; padding: 8px;">'.htmlspecialchars($booking['customer_email']).'</td>
            <td style="border-bottom: 1px solid #46486F; text-align:center; color: #46486F; padding: 8px;">'.htmlspecialchars($booking['customer_phone']).'</td>
        </tr>
    </table>

    <!-- PERUBAHAN 2: Tambah jarak/spasi lebih besar sebelum Booking Details -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-family: Helvetica; color: #46486F;">
        <tr><td colspan="7" height="30"></td></tr>
        <tr>
            <td colspan="7" style="font-size: 14px; font-weight: bold; border-bottom: 2px solid #46486F; padding: 5px 0;">
                Booking Details
            </td>
        </tr>
    </table>

    <table cellpadding="8" cellspacing="0" width="100%" style="font-family: Helvetica; font-size: 11px; border-collapse: collapse;">
        <tr style="background-color: #46486F;">
            <td width="16%" style="font-weight: bold; color: #FFFFFF; padding: 10px;">Branch</td>
            <td width="18%" style="font-weight: bold; text-align:center; color: #FFFFFF; padding: 10px;">Service</td>
            <td width="14%" style="font-weight: bold; text-align:center; color: #FFFFFF; padding: 10px;">Date</td>
            <td width="14%" style="font-weight: bold; text-align:center; color: #FFFFFF; padding: 10px;">Time</td>
            <td width="13%" style="font-weight: bold; text-align:center; color: #FFFFFF; padding: 10px;">Duration</td>
            <td width="12%" style="font-weight: bold; text-align:center; color: #FFFFFF; padding: 10px;">Room</td>
            <td width="13%" style="font-weight: bold; text-align:center; color: #FFFFFF; padding: 10px;">Therapist</td>
        </tr>

        <tr style="background-color: #FFFFFF;">
            <td style="border-bottom: 1px solid #46486F; color: #46486F; padding: 8px;">'.htmlspecialchars($booking['branch_name']).'</td>
            <td style="border-bottom: 1px solid #46486F; text-align:center; color: #46486F; padding: 8px;">'.htmlspecialchars($booking['category_name']).'</td>
            <td style="border-bottom: 1px solid #46486F; text-align:center; color: #46486F; padding: 8px;">'.$formattedDate.'</td>
            <td style="border-bottom: 1px solid #46486F; text-align:center; color: #46486F; padding: 8px;">'.$startTime.' - '.$endTime.'</td>
            <td style="border-bottom: 1px solid #46486F; text-align:center; color: #46486F; padding: 8px;">'.$booking['duration'].' mins</td>
            <td style="border-bottom: 1px solid #46486F; text-align:center; color: #46486F; padding: 8px;">'.htmlspecialchars($booking['room_name']).'</td>
            <td style="border-bottom: 1px solid #46486F; text-align:center; color: #46486F; padding: 8px;">'.htmlspecialchars($booking['therapist_name']).'</td>
        </tr>
    </table>

    <!-- PERUBAHAN 3: Footer disimetriskan - Fatmawati kiri, Menteng kanan -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-family: Helvetica, Arial, sans-serif; color: #46486F; font-size: 11px;">
        <tr>
            <td width="50%" valign="top" align="left">
                <div style="font-size: 14px; font-weight: bold; margin-bottom: 5px;">Rebah Fatmawati</div>
                <div style="line-height: 1.4;">
                    Fatmawati Raya Street, West Cilandak,<br>
                    Cilandak, South Jakarta, 12430<br>
                    +62 822-9999-4259
                </div>
            </td>
            <td width="50%" valign="top" align="right">
                <div style="font-size: 14px; font-weight: bold; margin-bottom: 5px;">Rebah Menteng</div>
                <div style="line-height: 1.4;">
                    51 Teuku Cik Ditiro Street, Menteng,<br>
                    Central Jakarta, 10310<br>
                    +62 822-9999-4263
                </div>
            </td>
        </tr>
    </table>
    ';
    return $html;
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
    
    $html = getInvoiceHTML($booking, true);
    
    $pdf->writeHTML($html, true, false, true, false, '');
    $bookingCode = str_pad($booking['id'], 3, '0', STR_PAD_LEFT);
    $pdf->Output('Rebah_Invoice_' . $bookingCode . '.pdf', 'D');
}

// Fallback simple PDF generation without library
function generateSimplePDF($booking) {
    $bookingCode = str_pad($booking['id'], 3, '0', STR_PAD_LEFT);
    
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="Rebah_Invoice_' . $bookingCode . '.html"');
    
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Rebah Invoice</title>
        <style>body { background-color: #f5f5f5; padding: 40px; } .invoice-container { background: white; padding: 40px; max-width: 900px; margin: 0 auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }</style>
    </head>
    <body>
        <div class="invoice-container">';
    
    echo getInvoiceHTML($booking, false);
    
    echo '
            <div style="text-align: center; margin-top: 30px;" class="no-print">
                <button onclick="window.print()" style="padding: 10px 20px; background: #46486F; color: white; border: none; border-radius: 5px; cursor: pointer;">Print Receipt</button>
            </div>
        </div>
    </body>
    </html>';
}
?>