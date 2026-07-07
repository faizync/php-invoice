<?php
// public/index.php
// Invoice Generator - Main entry point
require_once __DIR__ . '/src/Invoice.php';

$invoice = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'invoice_number' => trim($_POST['invoice_number'] ?? ''),
        'invoice_date'   => trim($_POST['invoice_date'] ?? ''),
        'due_date'       => trim($_POST['due_date'] ?? ''),
        'from_name'      => trim($_POST['from_name'] ?? ''),
        'from_email'     => trim($_POST['from_email'] ?? ''),
        'from_address'   => trim($_POST['from_address'] ?? ''),
        'to_name'        => trim($_POST['to_name'] ?? ''),
        'to_email'       => trim($_POST['to_email'] ?? ''),
        'to_address'     => trim($_POST['to_address'] ?? ''),
        'items'          => $_POST['items'] ?? [],
        'notes'          => trim($_POST['notes'] ?? ''),
        'tax_rate'       => (float)($_POST['tax_rate'] ?? 0),
    ];

    $invoice = new Invoice($data);
    $errors  = $invoice->validate();

    if (!empty($errors)) {
        $invoice = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PHP Invoice Generator</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #f0f4f8;
            color: #2d3748;
            min-height: 100vh;
        }

        header {
            background: linear-gradient(135deg, #2b6cb0, #2c5282);
            color: #fff;
            padding: 20px 40px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        header svg { width: 32px; height: 32px; fill: #fff; }
        header h1 { font-size: 1.5rem; font-weight: 600; letter-spacing: 0.3px; }

        .container { max-width: 900px; margin: 36px auto; padding: 0 20px 60px; }

        .card {
            background: #fff;
            border-radius: 12px;
            padding: 36px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            margin-bottom: 28px;
        }

        .card h2 {
            font-size: 1rem;
            font-weight: 600;
            color: #2b6cb0;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ebf4ff;
        }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }

        .field { display: flex; flex-direction: column; gap: 6px; }
        .field label { font-size: 0.8rem; font-weight: 600; color: #4a5568; text-transform: uppercase; letter-spacing: 0.5px; }

        input, textarea, select {
            padding: 10px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 7px;
            font-size: 0.92rem;
            color: #2d3748;
            background: #f7fafc;
            transition: border-color 0.2s, box-shadow 0.2s;
            width: 100%;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #2b6cb0;
            box-shadow: 0 0 0 3px rgba(43,108,176,0.12);
            background: #fff;
        }

        textarea { resize: vertical; min-height: 72px; }

        /* Items Table */
        .items-header {
            display: grid;
            grid-template-columns: 3fr 1fr 1.2fr 1fr;
            gap: 10px;
            padding: 8px 0;
            font-size: 0.75rem;
            font-weight: 700;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 8px;
        }

        .item-row {
            display: grid;
            grid-template-columns: 3fr 1fr 1.2fr 1fr;
            gap: 10px;
            align-items: center;
            margin-bottom: 10px;
        }

        .remove-btn {
            background: none;
            border: none;
            color: #e53e3e;
            cursor: pointer;
            font-size: 1.1rem;
            padding: 4px 8px;
            border-radius: 4px;
            transition: background 0.15s;
        }

        .remove-btn:hover { background: #fff5f5; }

        .add-item-btn {
            margin-top: 12px;
            background: none;
            border: 2px dashed #cbd5e0;
            color: #4a5568;
            padding: 10px 20px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 0.88rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.2s;
        }

        .add-item-btn:hover { border-color: #2b6cb0; color: #2b6cb0; background: #ebf4ff; }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2b6cb0, #2c5282);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: 0.3px;
            transition: opacity 0.2s, transform 0.1s;
            margin-top: 8px;
        }

        .submit-btn:hover { opacity: 0.92; transform: translateY(-1px); }

        .errors {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 24px;
            color: #c53030;
        }

        .errors ul { padding-left: 18px; margin-top: 8px; }
        .errors li { margin-bottom: 4px; font-size: 0.9rem; }

        /* ── Invoice Preview ── */
        .invoice-preview {
            background: #fff;
            border-radius: 12px;
            padding: 48px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            margin-top: 32px;
        }

        .inv-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
        }

        .inv-title { font-size: 2.4rem; font-weight: 800; color: #2b6cb0; letter-spacing: -0.5px; }
        .inv-number { font-size: 0.9rem; color: #718096; margin-top: 4px; }

        .inv-dates { text-align: right; }
        .inv-dates p { font-size: 0.88rem; color: #4a5568; margin-bottom: 4px; }
        .inv-dates strong { color: #2d3748; }

        .inv-parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            margin-bottom: 36px;
        }

        .inv-party h4 {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #a0aec0;
            margin-bottom: 8px;
        }

        .inv-party p { font-size: 0.92rem; color: #2d3748; line-height: 1.6; }
        .inv-party .party-name { font-weight: 700; font-size: 1rem; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }

        thead tr { background: #2b6cb0; color: #fff; }
        thead th { padding: 12px 14px; text-align: left; font-size: 0.82rem; font-weight: 600; letter-spacing: 0.4px; }
        thead th:last-child { text-align: right; }

        tbody tr { border-bottom: 1px solid #e2e8f0; }
        tbody tr:last-child { border-bottom: none; }
        tbody td { padding: 12px 14px; font-size: 0.9rem; color: #2d3748; }
        tbody td:last-child { text-align: right; font-weight: 500; }
        tbody tr:nth-child(even) { background: #f7fafc; }

        .inv-totals { display: flex; justify-content: flex-end; }

        .totals-box { width: 280px; }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 0.9rem;
            color: #4a5568;
            border-bottom: 1px solid #e2e8f0;
        }

        .totals-row.grand {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2b6cb0;
            border-bottom: none;
            padding-top: 14px;
        }

        .inv-notes {
            margin-top: 36px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            font-size: 0.88rem;
            color: #718096;
        }

        .inv-notes strong { color: #4a5568; display: block; margin-bottom: 4px; }

        .inv-footer {
            text-align: center;
            margin-top: 40px;
            font-size: 0.8rem;
            color: #a0aec0;
        }

        .print-btn {
            display: block;
            margin: 24px auto 0;
            padding: 12px 36px;
            background: #38a169;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .print-btn:hover { opacity: 0.88; }

        @media print {
    header, .print-btn, .errors { display: none !important; }
    #invoiceForm { display: none !important; }
    body { background: #fff; }
    .invoice-preview { box-shadow: none; padding: 0; }
        }

        @media (max-width: 640px) {
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
            .items-header, .item-row { grid-template-columns: 2fr 1fr 1fr; }
            .items-header > *:nth-child(3),
            .item-row > *:nth-child(3) { display: none; }
            .inv-parties { grid-template-columns: 1fr; }
            .invoice-preview { padding: 24px; }
        }
    </style>
</head>
<body>

<header>
    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z"/>
    </svg>
    <h1>PHP Invoice Generator</h1>
</header>

<div class="container">

    <?php if (!empty($errors)): ?>
    <div class="errors">
        <strong>Please fix the following errors:</strong>
        <ul>
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" id="invoiceForm">

        <!-- Invoice Details -->
        <div class="card">
            <h2>Invoice Details</h2>
            <div class="grid-3">
                <div class="field">
                    <label>Invoice Number</label>
                    <input type="text" name="invoice_number"
                           value="<?= htmlspecialchars($_POST['invoice_number'] ?? 'INV-' . date('Ymd') . '-001') ?>"
                           placeholder="INV-001" required />
                </div>
                <div class="field">
                    <label>Invoice Date</label>
                    <input type="date" name="invoice_date"
                           value="<?= htmlspecialchars($_POST['invoice_date'] ?? date('Y-m-d')) ?>" required />
                </div>
                <div class="field">
                    <label>Due Date</label>
                    <input type="date" name="due_date"
                           value="<?= htmlspecialchars($_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days'))) ?>" required />
                </div>
            </div>
        </div>

        <!-- From / To -->
        <div class="grid-2" style="gap:20px; margin-bottom:28px;">
            <div class="card" style="margin-bottom:0">
                <h2>From (Your Details)</h2>
                <div class="field" style="margin-bottom:12px">
                    <label>Your Name / Company</label>
                    <input type="text" name="from_name"
                           value="<?= htmlspecialchars($_POST['from_name'] ?? '') ?>"
                           placeholder="Acme Corp" required />
                </div>
                <div class="field" style="margin-bottom:12px">
                    <label>Email</label>
                    <input type="email" name="from_email"
                           value="<?= htmlspecialchars($_POST['from_email'] ?? '') ?>"
                           placeholder="you@company.com" required />
                </div>
                <div class="field">
                    <label>Address</label>
                    <textarea name="from_address" placeholder="123 Main St&#10;City, Country"><?= htmlspecialchars($_POST['from_address'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="card" style="margin-bottom:0">
                <h2>Bill To (Client)</h2>
                <div class="field" style="margin-bottom:12px">
                    <label>Client Name / Company</label>
                    <input type="text" name="to_name"
                           value="<?= htmlspecialchars($_POST['to_name'] ?? '') ?>"
                           placeholder="Client Name" required />
                </div>
                <div class="field" style="margin-bottom:12px">
                    <label>Email</label>
                    <input type="email" name="to_email"
                           value="<?= htmlspecialchars($_POST['to_email'] ?? '') ?>"
                           placeholder="client@email.com" required />
                </div>
                <div class="field">
                    <label>Address</label>
                    <textarea name="to_address" placeholder="456 Client Ave&#10;City, Country"><?= htmlspecialchars($_POST['to_address'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <div class="card">
            <h2>Line Items</h2>
            <div class="items-header">
                <span>Description</span>
                <span>Qty</span>
                <span>Unit Price ($)</span>
                <span style="text-align:right">Amount</span>
            </div>
            <div id="itemsContainer">
                <?php
                $postedItems = $_POST['items'] ?? [['description'=>'','qty'=>1,'price'=>'']];
                foreach ($postedItems as $i => $item):
                ?>
                <div class="item-row">
                    <input type="text" name="items[<?= $i ?>][description]"
                           value="<?= htmlspecialchars($item['description'] ?? '') ?>"
                           placeholder="Service or product description" />
                    <input type="number" name="items[<?= $i ?>][qty]"
                           value="<?= htmlspecialchars($item['qty'] ?? 1) ?>"
                           min="1" step="1" placeholder="1" />
                    <input type="number" name="items[<?= $i ?>][price]"
                           value="<?= htmlspecialchars($item['price'] ?? '') ?>"
                           min="0" step="0.01" placeholder="0.00" />
                    <button type="button" class="remove-btn" onclick="removeItem(this)" title="Remove">✕</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="add-item-btn" onclick="addItem()">+ Add Line Item</button>
        </div>

        <!-- Tax & Notes -->
        <div class="card">
            <h2>Tax & Notes</h2>
            <div class="grid-2">
                <div class="field">
                    <label>Tax Rate (%)</label>
                    <input type="number" name="tax_rate"
                           value="<?= htmlspecialchars($_POST['tax_rate'] ?? 0) ?>"
                           min="0" max="100" step="0.1" placeholder="0" />
                </div>
                <div class="field">
                    <label>Notes / Payment Terms</label>
                    <textarea name="notes" placeholder="Payment due within 30 days. Thank you for your business!"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <button type="submit" class="submit-btn">⚡ Generate Invoice</button>
    </form>

    <!-- Invoice Preview -->
    <?php if ($invoice): ?>
    <div class="invoice-preview" id="invoicePreview">
        <div class="inv-header">
            <div>
                <div class="inv-title">INVOICE</div>
                <div class="inv-number"># <?= htmlspecialchars($invoice->invoice_number) ?></div>
            </div>
            <div class="inv-dates">
                <p>Date: <strong><?= htmlspecialchars(date('M d, Y', strtotime($invoice->invoice_date))) ?></strong></p>
                <p>Due: <strong><?= htmlspecialchars(date('M d, Y', strtotime($invoice->due_date))) ?></strong></p>
            </div>
        </div>

        <div class="inv-parties">
            <div class="inv-party">
                <h4>From</h4>
                <p class="party-name"><?= htmlspecialchars($invoice->from_name) ?></p>
                <p><?= htmlspecialchars($invoice->from_email) ?></p>
                <p><?= nl2br(htmlspecialchars($invoice->from_address)) ?></p>
            </div>
            <div class="inv-party">
                <h4>Bill To</h4>
                <p class="party-name"><?= htmlspecialchars($invoice->to_name) ?></p>
                <p><?= htmlspecialchars($invoice->to_email) ?></p>
                <p><?= nl2br(htmlspecialchars($invoice->to_address)) ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoice->items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['description']) ?></td>
                    <td><?= (int)$item['qty'] ?></td>
                    <td>$<?= number_format((float)$item['price'], 2) ?></td>
                    <td>$<?= number_format($item['qty'] * $item['price'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="inv-totals">
            <div class="totals-box">
                <div class="totals-row">
                    <span>Subtotal</span>
                    <span>$<?= number_format($invoice->subtotal(), 2) ?></span>
                </div>
                <?php if ($invoice->tax_rate > 0): ?>
                <div class="totals-row">
                    <span>Tax (<?= $invoice->tax_rate ?>%)</span>
                    <span>$<?= number_format($invoice->tax(), 2) ?></span>
                </div>
                <?php endif; ?>
                <div class="totals-row grand">
                    <span>Total Due</span>
                    <span>$<?= number_format($invoice->total(), 2) ?></span>
                </div>
            </div>
        </div>

        <?php if ($invoice->notes): ?>
        <div class="inv-notes">
            <strong>Notes & Payment Terms</strong>
            <?= nl2br(htmlspecialchars($invoice->notes)) ?>
        </div>
        <?php endif; ?>

        <div class="inv-footer">
            Generated by PHP Invoice Generator &mdash; <?= date('Y') ?>
        </div>

        <button class="print-btn" onclick="window.print()">🖨 Print / Save as PDF</button>
    </div>
    <?php endif; ?>

</div>

<script>
let itemIndex = <?= count($postedItems ?? [['']]) ?>;

function addItem() {
    const container = document.getElementById('itemsContainer');
    const row = document.createElement('div');
    row.className = 'item-row';
    row.innerHTML = `
        <input type="text"   name="items[${itemIndex}][description]" placeholder="Service or product description" />
        <input type="number" name="items[${itemIndex}][qty]"         value="1" min="1" step="1" />
        <input type="number" name="items[${itemIndex}][price]"       placeholder="0.00" min="0" step="0.01" />
        <button type="button" class="remove-btn" onclick="removeItem(this)" title="Remove">✕</button>
    `;
    container.appendChild(row);
    itemIndex++;
}

function removeItem(btn) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length > 1) {
        btn.closest('.item-row').remove();
    }
}
</script>
</body>
</html>
