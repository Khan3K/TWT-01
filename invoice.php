<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isset($_GET['id'])) {
    header("Location: sales.php");
    exit();
}

$sale_id = (int)$_GET['id'];
$sale = $conn->query("SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email, c.address as customer_address, u.full_name as cashier_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.customer_id LEFT JOIN users u ON s.user_id = u.user_id WHERE s.sale_id = $sale_id")->fetch_assoc();

if (!$sale) {
    header("Location: sales.php");
    exit();
}

$items = $conn->query("SELECT si.*, m.medicine_name, m.batch_no, m.expiry_date FROM sale_items si LEFT JOIN medicines m ON si.medicine_id = m.medicine_id WHERE si.sale_id = $sale_id");

$company_name = APP_NAME;
$company_phone = "+1 (555) 123-4567";
$company_email = "info@pharmacyms.com";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo $sale['invoice_no']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', Arial, Helvetica, sans-serif; background: #e2e8f0; color: #1e293b; }
        
        .wrap { max-width: 800px; margin: 20px auto; }
        
        /* ACTION BAR */
        .actions {
            background: #fff; border-radius: 12px; padding: 16px 24px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px;
        }
        .actions a, .actions button {
            font-family: 'Inter', sans-serif; font-weight: 600; font-size: 14px;
            padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
            transition: all 0.2s;
        }
        .btn-back { background: #f1f5f9; color: #475569; }
        .btn-back:hover { background: #e2e8f0; }
        .btn-print { background: #6366f1; color: #fff; }
        .btn-print:hover { background: #4f46e5; }
        .btn-pdf { background: #ef4444; color: #fff; }
        .btn-pdf:hover { background: #dc2626; }
        .btn-pdf:disabled { opacity: 0.6; cursor: wait; }
        .right-btns { display: flex; gap: 10px; }
        
        /* INVOICE */
        .invoice {
            background: #fff; border-radius: 12px; overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.1); position: relative;
        }
        
        /* HEADER */
        .inv-hdr {
            background: linear-gradient(135deg, #1e1b4b, #312e81, #4f46e5);
            color: #fff; padding: 36px 40px; position: relative; overflow: hidden;
        }
        .inv-hdr::before {
            content: ''; position: absolute; top: -40px; right: -40px;
            width: 160px; height: 160px; border-radius: 50%;
            background: rgba(255,255,255,0.06);
        }
        .inv-hdr::after {
            content: ''; position: absolute; bottom: -60px; left: 40%;
            width: 250px; height: 250px; border-radius: 50%;
            background: rgba(255,255,255,0.03);
        }
        .hdr-top { display: flex; justify-content: space-between; align-items: flex-start; position: relative; z-index: 1; }
        .company-name { font-size: 22px; font-weight: 800; letter-spacing: -0.5px; }
        .company-sub { font-size: 12px; opacity: 0.7; margin-top: 2px; }
        .company-contact { font-size: 11px; opacity: 0.55; margin-top: 10px; line-height: 1.8; }
        .inv-badge { text-align: right; }
        .inv-badge h1 { font-size: 32px; font-weight: 800; letter-spacing: 3px; margin: 0; }
        .inv-badge .inv-no { font-size: 13px; opacity: 0.9; margin-top: 4px; }
        .inv-badge .inv-date { font-size: 12px; opacity: 0.65; margin-top: 2px; }
        
        /* BODY */
        .inv-body { padding: 36px 40px; }
        
        /* INFO ROW */
        .info-row { display: flex; gap: 20px; margin-bottom: 28px; }
        .info-box { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; }
        .info-box h5 {
            font-size: 10px; text-transform: uppercase; letter-spacing: 1px;
            color: #6366f1; font-weight: 700; margin-bottom: 10px;
        }
        .info-box p { font-size: 13px; line-height: 1.7; color: #334155; }
        .info-box p strong { font-weight: 600; }
        .info-box .name { font-size: 15px; font-weight: 700; color: #1e293b; }
        
        /* TABLE */
        .inv-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .inv-table thead th {
            background: #4f46e5; color: #fff; padding: 12px 14px;
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px;
            font-weight: 600; text-align: left;
        }
        .inv-table thead th:first-child { border-radius: 8px 0 0 0; }
        .inv-table thead th:last-child { border-radius: 0 8px 0 0; text-align: right; }
        .inv-table tbody td {
            padding: 13px 14px; border-bottom: 1px solid #f1f5f9;
            font-size: 13px; color: #334155;
        }
        .inv-table tbody tr:nth-child(even) { background: #fafbfc; }
        .inv-table .med-name { font-weight: 600; color: #1e293b; }
        .inv-table .batch { background: #f1f5f9; color: #64748b; padding: 2px 8px; border-radius: 4px; font-size: 11px; }
        .inv-table .r { text-align: right; }
        .inv-table .c { text-align: center; }
        .inv-table .exp { font-size: 11px; color: #94a3b8; }
        
        /* TOTALS */
        .totals-wrap { display: flex; justify-content: flex-end; margin-bottom: 28px; }
        .totals { width: 300px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; }
        .tot-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; }
        .tot-row .lbl { color: #64748b; }
        .tot-row .val { font-weight: 500; color: #1e293b; }
        .tot-row.disc .val { color: #ef4444; }
        .tot-row.tax .val { color: #f59e0b; }
        .tot-hr { border: none; border-top: 2px solid #e2e8f0; margin: 8px 0; }
        .tot-row.total .lbl { font-size: 15px; font-weight: 700; color: #1e293b; }
        .tot-row.total .val { font-size: 20px; font-weight: 800; color: #059669; }
        
        /* BADGES */
        .badges { display: flex; gap: 10px; margin-bottom: 28px; }
        .badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 7px 14px; border-radius: 7px; font-size: 12px; font-weight: 600;
        }
        .badge-cash { background: rgba(99,102,241,0.1); color: #6366f1; }
        .badge-card { background: rgba(14,165,233,0.1); color: #0ea5e9; }
        .badge-online { background: rgba(168,85,247,0.1); color: #a855f7; }
        .badge-paid { background: rgba(16,185,129,0.1); color: #059669; }
        .badge-pending { background: rgba(245,158,11,0.1); color: #f59e0b; }
        
        /* SIGNATURE */
        .sig-row { display: flex; justify-content: space-between; margin-top: 40px; padding-top: 20px; }
        .sig-box { text-align: center; width: 200px; }
        .sig-line { border-top: 1px solid #cbd5e1; margin-top: 55px; padding-top: 8px; }
        .sig-label { font-size: 11px; color: #64748b; font-weight: 600; }
        
        /* FOOTER */
        .inv-footer {
            background: #f8fafc; border-top: 1px solid #e2e8f0;
            padding: 22px 40px; text-align: center;
        }
        .inv-footer .ty { font-size: 15px; font-weight: 700; color: #1e293b; }
        .inv-footer .sub { font-size: 12px; color: #94a3b8; margin-top: 2px; }
        .inv-footer .contact { display: flex; justify-content: center; gap: 20px; margin-top: 10px; font-size: 11px; color: #94a3b8; }
        .inv-footer .contact span { display: flex; align-items: center; gap: 4px; }
        
        /* WATERMARK */
        .watermark {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 80px; font-weight: 800; color: rgba(99,102,241,0.03);
            pointer-events: none; white-space: nowrap; z-index: 0;
        }
        
        /* PRINT */
        @media print {
            body { background: #fff !important; }
            .actions { display: none !important; }
            .wrap { margin: 0 !important; max-width: 100% !important; }
            .invoice { box-shadow: none !important; border-radius: 0 !important; }
            .inv-hdr { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .inv-table thead th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge, .info-box, .totals { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { margin: 8mm; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <!-- ACTIONS -->
    <div class="actions" id="actionBar">
        <a href="sales.php" class="btn-back">← Back to Sales</a>
        <div class="right-btns">
            <button class="btn-print" onclick="window.print()">🖨️ Print</button>
            <button class="btn-pdf" id="pdfBtn" onclick="generatePDF()">📄 Download PDF</button>
        </div>
    </div>

    <!-- INVOICE -->
    <div class="invoice" id="invoice">
        <div class="watermark">INVOICE</div>
        
        <!-- HEADER -->
        <div class="inv-hdr">
            <div class="hdr-top">
                <div>
                    <div class="company-name">💊 <?php echo $company_name; ?></div>
                    <div class="company-sub">Medical Store Management System</div>
                    <div class="company-contact">
                        📍 123 Health Street, Medical District<br>
                        📞 <?php echo $company_phone; ?><br>
                        ✉️ <?php echo $company_email; ?>
                    </div>
                </div>
                <div class="inv-badge">
                    <h1>INVOICE</h1>
                    <div class="inv-no"><?php echo $sale['invoice_no']; ?></div>
                    <div class="inv-date"><?php echo format_date($sale['sale_date'], 'd M Y'); ?> at <?php echo format_date($sale['sale_date'], 'h:i A'); ?></div>
                </div>
            </div>
        </div>

        <!-- BODY -->
        <div class="inv-body">
            <!-- INFO -->
            <div class="info-row">
                <div class="info-box">
                    <h5>👤 Bill To</h5>
                    <p class="name"><?php echo $sale['customer_name'] ?: 'Walk-in Customer'; ?></p>
                    <p>
                        <?php if($sale['customer_phone']): ?>📞 <?php echo $sale['customer_phone']; ?><br><?php endif; ?>
                        <?php if($sale['customer_email']): ?>✉️ <?php echo $sale['customer_email']; ?><br><?php endif; ?>
                        <?php if($sale['customer_address']): ?>📍 <?php echo $sale['customer_address']; ?><?php endif; ?>
                        <?php if(!$sale['customer_phone'] && !$sale['customer_email'] && !$sale['customer_address']): ?>
                            <span style="color:#94a3b8;">No details provided</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="info-box">
                    <h5>💳 Payment</h5>
                    <p>
                        <strong>Method:</strong> <?php echo ucfirst($sale['payment_method']); ?><br>
                        <strong>Status:</strong> <span style="color:<?php echo $sale['payment_status']=='paid'?'#059669':'#f59e0b'; ?>;font-weight:700;"><?php echo ucfirst($sale['payment_status']); ?></span><br>
                        <strong>Served by:</strong> <?php echo $sale['cashier_name']; ?>
                    </p>
                </div>
                <div class="info-box">
                    <h5>📋 Summary</h5>
                    <p>
                        <strong>Date:</strong> <?php echo format_date($sale['sale_date'], 'd M Y'); ?><br>
                        <strong>Time:</strong> <?php echo format_date($sale['sale_date'], 'h:i A'); ?><br>
                        <strong>Items:</strong> <?php echo $items->num_rows; ?> product(s)
                    </p>
                </div>
            </div>

            <!-- TABLE -->
            <table class="inv-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Medicine</th>
                        <th>Batch</th>
                        <th>Expiry</th>
                        <th class="c">Qty</th>
                        <th class="r">Price</th>
                        <th class="r">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i=1; $sub=0; while($item=$items->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td class="med-name"><?php echo $item['medicine_name']; ?></td>
                        <td><span class="batch"><?php echo $item['batch_no'] ?? 'N/A'; ?></span></td>
                        <td><span class="exp"><?php echo $item['expiry_date'] ? format_date($item['expiry_date'],'M Y') : 'N/A'; ?></span></td>
                        <td class="c"><?php echo $item['quantity']; ?></td>
                        <td class="r"><?php echo format_currency($item['unit_price']); ?></td>
                        <td class="r" style="font-weight:600;"><?php echo format_currency($item['subtotal']); ?></td>
                    </tr>
                    <?php $sub += $item['subtotal']; endwhile; ?>
                </tbody>
            </table>

            <!-- TOTALS -->
            <div class="totals-wrap">
                <div class="totals">
                    <div class="tot-row">
                        <span class="lbl">Subtotal</span>
                        <span class="val"><?php echo format_currency($sub); ?></span>
                    </div>
                    <?php if($sale['discount'] > 0): ?>
                    <div class="tot-row disc">
                        <span class="lbl">Discount</span>
                        <span class="val">-<?php echo format_currency($sale['discount']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if($sale['tax'] > 0): ?>
                    <div class="tot-row tax">
                        <span class="lbl">Tax</span>
                        <span class="val">+<?php echo format_currency($sale['tax']); ?></span>
                    </div>
                    <?php endif; ?>
                    <hr class="tot-hr">
                    <div class="tot-row total">
                        <span class="lbl">TOTAL</span>
                        <span class="val"><?php echo format_currency($sale['total_amount']); ?></span>
                    </div>
                </div>
            </div>

            <!-- BADGES -->
            <div class="badges">
                <span class="badge badge-<?php echo $sale['payment_method']; ?>">
                    <?php echo $sale['payment_method']=='cash'?'💵':($sale['payment_method']=='card'?'💳':'🌐'); ?>
                    <?php echo ucfirst($sale['payment_method']); ?>
                </span>
                <span class="badge badge-<?php echo $sale['payment_status']; ?>">
                    <?php echo $sale['payment_status']=='paid'?'✅':'⏳'; ?>
                    <?php echo ucfirst($sale['payment_status']); ?>
                </span>
            </div>

            <!-- SIGNATURES -->
            <div class="sig-row">
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <div class="sig-label">Customer Signature</div>
                </div>
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <div class="sig-label">Authorized Signature</div>
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="inv-footer">
            <div class="ty">Thank you for your purchase!</div>
            <div class="sub">Your health is our priority. We appreciate your trust.</div>
            <div class="contact">
                <span>📞 <?php echo $company_phone; ?></span>
                <span>✉️ <?php echo $company_email; ?></span>
                <span>🌐 www.pharmacyms.com</span>
            </div>
        </div>
    </div>
</div>

<script>
function generatePDF() {
    const btn = document.getElementById('pdfBtn');
    const actionBar = document.getElementById('actionBar');
    
    btn.innerHTML = '⏳ Generating PDF...';
    btn.disabled = true;
    
    // Hide action bar for clean capture
    actionBar.style.display = 'none';
    
    const element = document.getElementById('invoice');
    
    // Use html2canvas + jsPDF
    html2canvas(element, {
        scale: 2,
        useCORS: true,
        logging: false,
        backgroundColor: '#ffffff',
        windowWidth: 820
    }).then(canvas => {
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');
        
        const imgData = canvas.toDataURL('image/jpeg', 0.95);
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
        
        pdf.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight);
        pdf.save('<?php echo $sale["invoice_no"]; ?>.pdf');
        
        // Restore
        actionBar.style.display = 'flex';
        btn.innerHTML = '📄 Download PDF';
        btn.disabled = false;
    }).catch(err => {
        console.error('PDF Error:', err);
        // Fallback: open print dialog
        actionBar.style.display = 'flex';
        btn.innerHTML = '📄 Download PDF';
        btn.disabled = false;
        alert('PDF generation failed. Use Print > Save as PDF instead.');
        window.print();
    });
}

// Auto-print
<?php if(isset($_GET['print'])): ?>
window.onload = function() { window.print(); };
<?php endif; ?>
</script>
</body>
</html>
