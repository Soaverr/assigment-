<?php
include 'db_connect.php';

function h($v)
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ទាញយកទិន្នន័យដើម្បីផ្ទៀងផ្ទាត់
$stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE id = ?");
$stmt->execute([$id]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="km">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ផ្ទៀងផ្ទាត់ឯកសារមន្ត្រី</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Kantumruy Pro', sans-serif;
            background-color: #f4f7fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

        .verify-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            max-width: 450px;
            width: 90%;
            padding: 30px;
            text-align: center;
        }

        .status-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed #eee;
            text-align: left;
        }

        .label {
            color: #64748b;
            font-size: 14px;
        }

        .value {
            color: #1e293b;
            font-weight: bold;
            font-size: 15px;
        }
    </style>
</head>

<body>

    <div class="verify-card">
        <?php if ($r): ?>
            <div class="status-icon text-success">
                <i class="bi bi-patch-check-fill"></i>
            </div>
            <h4 class="fw-bold text-success mb-1">ឯកសារត្រឹមត្រូវ</h4>
            <p class="text-muted small mb-4">ទិន្នន័យត្រូវបានផ្ទៀងផ្ទាត់ដោយជោគជ័យ</p>

            <div class="info-row">
                <span class="label">ឈ្មោះបុគ្គលិក</span>
                <span class="value"><?= h($r['employee_name']) ?></span>
            </div>
            <div class="info-row">
                <span class="label">សាខា</span>
                <span class="value"><?= h($r['branch']) ?></span>
            </div>
            <div class="info-row">
                <span class="label">ប្រភេទ/រយៈពេល</span>
                <span class="value"><?= h($r['total_days']) ?> ថ្ងៃ</span>
            </div>
            <div class="info-row">
                <span class="label">ស្ថានភាព</span>
                <span class="value text-primary"><?= h($r['status']) ?></span>
            </div>
            <div class="info-row">
                <span class="label">សម្រេចនៅថ្ងៃទី</span>
                <span class="value"><?= date('d/m/Y', strtotime($r['final_decided_at'])) ?></span>
            </div>

            <div class="mt-4">
                <small class="text-muted">© ប្រព័ន្ធគ្រប់គ្រងធនធានមនុស្ស</small>
            </div>

        <?php else: ?>
            <div class="status-icon text-danger">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <h4 class="fw-bold text-danger">មិនអាចផ្ទៀងផ្ទាត់បាន</h4>
            <p class="text-muted">សោកស្តាយ! លេខកូដនេះមិនមានក្នុងប្រព័ន្ធទិន្នន័យរបស់យើងទេ។</p>
            <a href="index.php" class="btn btn-secondary mt-3">ត្រឡប់ក្រោយ</a>
        <?php endif; ?>
    </div>

</body>

</html>