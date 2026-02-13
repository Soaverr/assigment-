<?php
include 'db_connect.php';

function h($v)
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ១. ទាញយកទិន្នន័យពី Database
$stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE id = ?");
$stmt->execute([$id]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$r) {
    die("រកមិនឃើញទិន្នន័យ! <a href='index.php'>ត្រឡប់ក្រោយ</a>");
}

// ២. ការកំណត់ QR Code (ផ្ទៀងផ្ទាត់)
$pc_ip = "172.20.10.2";
$folder = "asstest";
$verify_url = "http://$pc_ip/$folder/index.php?id=" . $r['id'];
$qr_api = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verify_url);
?>

<!DOCTYPE html>
<html lang="km">

<head>
    <meta charset="UTF-8">
    <title>បោះពុម្ពលិខិតសុំច្បាប់_#<?= h($r['id']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Koulane&family=Kantumruy+Pro:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kantumruy Pro', sans-serif;
            background: #e9ecef;
            margin: 0;
            padding: 20px;
        }

        .a4-page {
            background: white;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 2.5cm;
            box-sizing: border-box;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .top-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .khmer-title {
            font-family: 'Koulane', serif;
            font-size: 20px;
            margin: 0;
        }

        .report-title {
            font-family: 'Koulane', serif;
            font-size: 24px;
            text-decoration: underline;
            margin-top: 40px;
            margin-bottom: 30px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 10px 5px;
            font-size: 16px;
            border-bottom: 1px solid #f0f0f0;
        }

        .qr-code {
            position: absolute;
            top: 2.5cm;
            right: 2.5cm;
            text-align: center;
        }

        .qr-code img {
            width: 90px;
            border: 1px solid #eee;
        }

        .decision-box {
            border: 1.5px solid #333;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            background: #fafafa;
        }

        /* ផ្នែកហត្ថលេខា */
        .footer-sig {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            text-align: center;
        }

        .sig-box {
            width: 45%;
        }

        .sig-display {
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
        }

        .sig-display img {
            max-height: 80px;
            max-width: 100%;
        }

        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }

        @media print {
            body {
                background: none;
                padding: 0;
            }

            .a4-page {
                box-shadow: none;
                margin: 0;
                width: 100%;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="no-print">
        <button onclick="window.print()" style="padding: 12px 30px; background: #0d6efd; color: white; border: none; cursor: pointer; font-size: 16px; border-radius: 5px; font-family: 'Kantumruy Pro';">
            បោះពុម្ពលិខិត (Print)
        </button>
    </div>

    <div class="a4-page">
        <div class="qr-code">
            <img src="<?= $qr_api ?>" alt="QR Verification">
            <p style="font-size: 9px; margin-top: 5px; color: #666;">ស្កេនដើម្បីផ្ទៀងផ្ទាត់</p>
        </div>

        <div class="top-header">
            <h1 class="khmer-title">ព្រះរាជាណាចក្រកម្ពុជា</h1>
            <h2 class="khmer-title" style="font-size: 16px;">ជាតិ សាសនា ព្រះមហាក្សត្រ</h2>

            <h1 class="report-title">ពាក្យសុំច្បាប់ឈប់សម្រាក</h1>
        </div>

        <table class="info-table">
            <tr>
                <td width="25%">ឈ្មោះមន្ត្រី/បុគ្គលិក៖</td>
                <td><strong><?= h($r['employee_name']) ?></strong></td>
                <td width="15%">អត្តលេខ៖</td>
                <td>#<?= h($r['id']) ?></td>
            </tr>
            <tr>
                <td>សាខា/អង្គភាព៖</td>
                <td colspan="3"><?= h($r['branch']) ?></td>
            </tr>
            <tr>
                <td>មូលហេតុ៖</td>
                <td colspan="3"><?= h($r['reason']) ?></td>
            </tr>
            <tr>
                <td>រយៈពេលឈប់៖</td>
                <td><strong><?= h($r['total_days']) ?> ថ្ងៃ</strong></td>
                <td>កាលបរិច្ឆេទ៖</td>
                <td><?= date('d/m/Y', strtotime($r['start_date'])) ?> ដល់ <?= date('d/m/Y', strtotime($r['end_date'])) ?></td>
            </tr>
        </table>

        <div class="decision-box">
            <div style="font-weight: bold; margin-bottom: 8px; text-decoration: underline;">ការសម្រេចរបស់ថ្នាក់ដឹកនាំ៖</div>
            <p style="margin: 5px 0;">ស្ថានភាព៖ <strong><?= h($r['status'] == 'Approved' ? 'យល់ព្រមតាមការស្នើសុំ' : 'កំពុងត្រួតពិនិត្យ') ?></strong></p>
            <p style="margin: 5px 0;">មតិយោបល់៖ <em>"<?= h($r['final_remark'] ?: 'គ្មាន') ?>"</em></p>
        </div>

        <div class="footer-sig">
            <div class="sig-box">
                <p><strong>ហត្ថលេខាបុគ្គលិក</strong></p>
                <div class="sig-display">
                    <?php if (!empty($r['employee_signature'])): ?>
                        <img src="<?= $r['employee_signature'] ?>" alt="Employee Signature">
                    <?php else: ?>
                        <div style="height: 60px;"></div>
                    <?php endif; ?>
                </div>
                <p>( <?= h($r['employee_name']) ?> )</p>
            </div>

            <div class="sig-box">
                <p><strong>អ្នកអនុម័ត (Manager/HR)</strong></p>
                <div class="sig-display">
                    <?php if (!empty($r['manager_signature'])): ?>
                        <img src="<?= $r['manager_signature'] ?>" alt="Manager Signature">
                    <?php else: ?>
                        <div style="height: 60px; border: 1px dashed #ccc; width: 150px; margin: 0 auto; color: #ccc; line-height: 60px; font-size: 12px;">រង់ចាំការអនុម័ត</div>
                    <?php endif; ?>
                </div>
                <p>( ត្រា និងហត្ថលេខាឌីជីថល )</p>
            </div>
        </div>

        <div style="position: absolute; bottom: 1.5cm; left: 2.5cm; font-size: 10px; color: #777;">
            កាលបរិច្ឆេទបោះពុម្ព៖ <?= date('d/m/Y H:i') ?> | ID សំណើ៖ #<?= h($r['id']) ?>
        </div>
    </div>

</body>

</html>