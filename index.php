<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['is_index']) || $_SESSION['is_index'] !== true) {
    // បើមិនទាន់វាយកូដទេ បណ្តេញទៅទំព័រ login.php
    header("Location: login_index.php");
    exit;
}


function h($v)
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

// Telegram Notification (Optional)
function sendToTelegram($name, $branch, $start, $end, $days, $reason)
{
    $token = "YOUR_BOT_TOKEN";
    $chat_id = "YOUR_CHAT_ID";
    if ($token === "YOUR_BOT_TOKEN" || $chat_id === "YOUR_CHAT_ID") return;

    $message = "📝 *លិខិតសុំច្បាប់ថ្មី*\n\n👤 ឈ្មោះ៖ $name\n🏢 សាខា៖ $branch\n📅 ថ្ងៃ៖ $start ដល់ $end\n⏳ ចំនួន៖ $days ថ្ងៃ\n💡 មូលហេតុ៖ $reason";
    $url = "https://api.telegram.org/bot$token/sendMessage?chat_id=" . urlencode($chat_id) . "&text=" . urlencode($message) . "&parse_mode=Markdown";
    @file_get_contents($url);
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_submit'])) {
    $name = trim($_POST['emp_name'] ?? '');
    $branch = trim($_POST['branch'] ?? '');
    $start = $_POST['start_date'] ?? null;
    $end = $_POST['end_date'] ?? null;
    $days = (int)($_POST['total_days'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    if ($name !== '' && $start && $end && $days > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO leave_requests (employee_name, branch, start_date, end_date, total_days, reason, status, updated_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'Pending_Manager', NOW(), NOW())
        ");
        $stmt->execute([$name, $branch, $start, $end, $days, $reason]);
        $newId = (int)$pdo->lastInsertId();
        $_SESSION['last_request_id'] = $newId;

        sendToTelegram($name, $branch, $start, $end, $days, $reason);

        header("Location: index.php?id=" . $newId . "&status=success");
        exit;
    }
}

// Get Request Status
$requestedId = null;
if (isset($_GET['id']) && ctype_digit((string)$_GET['id'])) {
    $requestedId = (int)$_GET['id'];
} elseif (isset($_SESSION['last_request_id'])) {
    $requestedId = (int)$_SESSION['last_request_id'];
}

$row = null;
if ($requestedId) {
    $stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE id = ?");
    $stmt->execute([$requestedId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
?>

<!DOCTYPE html>
<html lang="km">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ប្រព័ន្ធសុំច្បាប់សម្រាក - Leave System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Kantumruy Pro', sans-serif;
            background-color: #f0f2f5;
        }

        .main-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .form-label {
            font-weight: 600;
            color: #495057;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .timeline-step {
            text-align: center;
            position: relative;
            flex: 1;
        }

        .timeline-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 15px;
            left: 60%;
            width: 80%;
            height: 2px;
            background: #dee2e6;
            z-index: 1;
        }

        .step-icon {
            width: 32px;
            height: 32px;
            background: #fff;
            border: 2px solid #dee2e6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            position: relative;
            z-index: 2;
        }

        .active-step .step-icon {
            border-color: #0d6efd;
            color: #0d6efd;
            background: #e7f1ff;
        }

        .completed-step .step-icon {
            background: #198754;
            border-color: #198754;
            color: white;
        }
    </style>
</head>

<body>

    <div class="container py-5">
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div>ទិន្នន័យត្រូវបានលុបចេញពីប្រព័ន្ធដោយជោគជ័យ!</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <div class="card main-card mb-4 bg-primary text-white">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-7">
                                <h4 class="fw-bold"><i class="bi bi-search me-2"></i>ឆែកមើលស្ថានភាពសំណើ</h4>
                                <p class="mb-0 text-white-50 small">បញ្ចូលលេខ ID សំណើរបស់អ្នកដើម្បីតាមដាន</p>
                            </div>
                            <div class="col-md-5 mt-3 mt-md-0">
                                <form method="GET" class="input-group">
                                    <input type="text" name="id" class="form-control border-0 shadow-none" placeholder="ឧទាហរណ៍៖ 101" value="<?= $requestedId ? h($requestedId) : '' ?>">
                                    <button class="btn btn-dark px-4" type="submit">ឆែកមើល</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($row):
                    $status = $row['status'];
                ?>
                    <div class="card main-card mb-4 border-start border-4 border-primary">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div>
                                    <h5 class="fw-bold mb-1">ព័ត៌មានសំណើ #<?= h($row['id']) ?></h5>
                                    <p class="text-muted small">ដាក់កាលពីថ្ងៃទី៖ <?= date('d-M-Y H:i', strtotime($row['created_at'])) ?></p>
                                </div>
                                <span class="status-badge 
                            <?= $status == 'Approved' ? 'bg-success-subtle text-success' : ($status == 'Rejected' ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning') ?>">
                                    <i class="bi bi-info-circle me-1"></i> <?= h($status) ?>
                                </span>
                            </div>

                            <div class="d-flex mb-4">
                                <div class="timeline-step completed-step">
                                    <div class="step-icon"><i class="bi bi-check-lg"></i></div>
                                    <div class="small fw-bold">ដាក់ពាក្យ</div>
                                </div>
                                <div class="timeline-step <?= in_array($status, ['Pending_HR', 'Approved']) ? 'completed-step' : ($status == 'Pending_Manager' ? 'active-step' : '') ?>">
                                    <div class="step-icon"><i class="bi bi-person-fill"></i></div>
                                    <div class="small fw-bold">Manager</div>
                                </div>
                                <div class="timeline-step <?= $status == 'Approved' ? 'completed-step' : ($status == 'Pending_HR' ? 'active-step' : '') ?>">
                                    <div class="step-icon"><i class="bi bi-building-check"></i></div>
                                    <div class="small fw-bold">HR</div>
                                </div>
                            </div>

                            <?php if ($status == 'Approved'): ?>
                                <div class="alert alert-success d-flex align-items-center mb-0">
                                    <i class="bi bi-check-circle-fill me-2 fs-4"></i>
                                    <div>អបអរសាទរ! សំណើរបស់អ្នកត្រូវបានអនុម័តរួចរាល់។ អ្នកអាចបោះពុម្ពឯកសារចេញបាន។</div>
                                    <a href="print_leave.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-success ms-auto btn-sm">បោះពុម្ព PDF</a>
                                </div>
                            <?php elseif ($status == 'Rejected'): ?>
                                <div class="alert alert-danger mb-0">
                                    <h6 class="fw-bold"><i class="bi bi-x-circle-fill me-2"></i>សំណើត្រូវបានបដិសេធ</h6>
                                    <p class="mb-0 small text-dark opacity-75">មតិបន្ថែម៖ <?= h($row['final_remark'] ?: 'គ្មាន') ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card main-card border-0">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-file-earmark-text me-2"></i>បំពេញពាក្យសុំច្បាប់សម្រាក</h5>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <form method="POST">
                            <div class="row g-4">
                                <div class="col-md-8">
                                    <label class="form-label">ឈ្មោះមន្ត្រី / បុគ្គលិក</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                        <input type="text" name="emp_name" class="form-control" placeholder="បញ្ចូលឈ្មោះពេញ" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">សាខា / អង្គភាព</label>
                                    <input type="text" name="branch" class="form-control" placeholder="សាខា...">
                                </div>

                                <div class="col-md-5">
                                    <label class="form-label text-primary">សុំច្បាប់ពីថ្ងៃទី</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control shadow-sm border-primary-subtle" required onchange="calcDays()">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label text-primary">ដល់ថ្ងៃទី</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control shadow-sm border-primary-subtle" required onchange="calcDays()">
                                </div>
                                <div class="col-md-2 text-center">
                                    <label class="form-label">ចំនួនថ្ងៃ</label>
                                    <input type="number" name="total_days" id="total_days" class="form-control text-center fw-bold bg-light" value="0" readonly>
                                </div>

                                <div class="col-12 mt-4">
                                    <label class="form-label">មូលហេតុនៃការសុំសម្រាក</label>
                                    <textarea name="reason" class="form-control" rows="4" placeholder="បញ្ជាក់ពីមូលហេតុឱ្យបានច្បាស់លាស់..."></textarea>
                                </div>
                            </div>

                            <div class="mt-5 d-grid gap-2 d-md-flex justify-content-md-between border-top pt-4">
                                <div class="text-muted small">
                                    <i class="bi bi-shield-check text-success me-1"></i> រាល់ការសុំច្បាប់នឹងត្រូវឆ្លងកាត់ការពិនិត្យតាមលំដាប់ថ្នាក់។
                                </div>
                                <button type="submit" name="btn_submit" class="btn btn-primary px-5 py-2 fw-bold">
                                    <i class="bi bi-send me-2"></i> ផ្ញើពាក្យសុំច្បាប់
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>


    <footer class="mt-5 pb-4">
        <hr class="text-muted opacity-25">
        <div class="d-flex justify-content-between align-items-center px-4">
            <div class="text-muted small">
                &copy; 2026 - <?= date('Y') ?> | <b>ប្រព័ន្ធគ្រប់គ្រងការសុំច្បាប់</b>
            </div>
            <div class="developer-info">
                <span class="text-muted small me-2">រៀបចំដោយ៖</span>
                <a href="#" class="text-decoration-none fw-bold text-primary shadow-sm p-2 rounded-3 bg-white border">
                    <i class="bi bi-code-slash me-1"></i> SOR SOAVERDY
                </a>
            </div>
            <a href="#" target="_blank" class="text-decoration-none fw-bold text-primary shadow-sm p-2 rounded-3 bg-white border">
                +855 966670230
            </a>
        </div>
    </footer>

    <script>
        function calcDays() {
            const start = document.getElementById('start_date').value;
            const end = document.getElementById('end_date').value;
            if (start && end) {
                const s = new Date(start);
                const e = new Date(end);
                const diff = Math.ceil((e - s) / (1000 * 60 * 60 * 24)) + 1;
                document.getElementById('total_days').value = diff > 0 ? diff : 0;
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>