<?php
require_once '../../core/Database.php';
require_once '../../core/Session.php';

\App\Core\Session::init();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'pharmacist') {
    die("Unauthorized access");
}

$bill_id = $_GET['id'] ?? null;
if (!$bill_id) {
    die("Invalid Bill ID");
}

$db = new \App\Core\Database();
$conn = $db->getConnection();

// Fetch Bill
$stmtBill = $conn->prepare("
    SELECT b.*, u.full_name as pharmacist_name 
    FROM bills b 
    LEFT JOIN users u ON b.pharmacist_id = u.id 
    WHERE b.id = ?
");
$stmtBill->execute([$bill_id]);
$bill = $stmtBill->fetch(PDO::FETCH_ASSOC);

if (!$bill) {
    die("Bill not found.");
}

// Fetch Sale Items
$stmtItems = $conn->prepare("
    SELECT s.quantity, s.total_price, m.name as medicine_name, m.price as unit_price 
    FROM sales s 
    JOIN medicines m ON s.medicine_id = m.id 
    WHERE s.bill_id = ?
");
$stmtItems->execute([$bill_id]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo $bill['id']; ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            background: #f4f4f4;
            color: #000;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }
        .receipt-container {
            background: #fff;
            width: 100%;
            max-width: 400px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 1.5rem;
        }
        .header p {
            margin: 2px 0;
            font-size: 0.9rem;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        th, td {
            text-align: left;
            padding: 5px 0;
        }
        th {
            border-bottom: 1px solid #000;
            border-top: 1px solid #000;
        }
        .text-right {
            text-align: right;
        }
        .totals-section {
            border-top: 2px dashed #000;
            padding-top: 10px;
            font-size: 0.9rem;
        }
        .grand-total {
            font-size: 1.1rem;
            font-weight: bold;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #000;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.85rem;
        }
        
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                max-width: 100%;
            }
            #printBtn {
                display: none;
            }
        }
        
        #printBtn {
            display: block;
            width: 100%;
            padding: 10px;
            margin-top: 20px;
            background: #0ea5e9;
            color: white;
            border: none;
            font-size: 1rem;
            font-family: inherit;
            cursor: pointer;
        }
        #printBtn:hover {
            background: #0284c7;
        }
    </style>
</head>
<body>

<div class="receipt-container">
    <div class="header">
        <h1>MEDIN PHARMACY</h1>
        <p>123 Medical Avenue, City Center</p>
        <p>Tel: +1 234 567 8900</p>
    </div>

    <div class="info-row">
        <span>Receipt No:</span>
        <span>#<?php echo str_pad($bill['id'], 6, '0', STR_PAD_LEFT); ?></span>
    </div>
    <div class="info-row">
        <span>Date:</span>
        <span><?php echo date('Y-m-d H:i A', strtotime($bill['created_at'])); ?></span>
    </div>
    <div class="info-row">
        <span>Pharmacist:</span>
        <span><?php echo htmlspecialchars($bill['pharmacist_name'] ?? 'Staff'); ?></span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['medicine_name']); ?></td>
                <td class="text-right"><?php echo $item['quantity']; ?></td>
                <td class="text-right">$<?php echo number_format($item['unit_price'], 2); ?></td>
                <td class="text-right">$<?php echo number_format($item['total_price'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals-section">
        <div class="info-row">
            <span>Subtotal:</span>
            <span>$<?php echo number_format($bill['subtotal'], 2); ?></span>
        </div>
        
        <?php if($bill['discount_amount'] > 0): ?>
        <div class="info-row">
            <span>Discount:</span>
            <span>-$<?php echo number_format($bill['discount_amount'], 2); ?></span>
        </div>
        <?php endif; ?>
        
        <?php if($bill['bag_charge'] > 0): ?>
        <div class="info-row">
            <span>Plastic Bag:</span>
            <span>$<?php echo number_format($bill['bag_charge'], 2); ?></span>
        </div>
        <?php endif; ?>

        <div class="info-row">
            <span>Tax (5%):</span>
            <span>$<?php echo number_format($bill['tax'], 2); ?></span>
        </div>

        <div class="info-row grand-total">
            <span>TOTAL:</span>
            <span>$<?php echo number_format($bill['grand_total'], 2); ?></span>
        </div>
    </div>

    <div class="footer">
        <p>*** THANK YOU FOR VISITING ***</p>
        <p>Please keep this receipt for returns.</p>
    </div>
    
    <button id="printBtn" onclick="window.print()">Print Receipt</button>
</div>

<script>
// Auto print when opened
window.onload = function() {
    // window.print(); // Optional: Automatically trigger print dialog
}
</script>
</body>
</html>
