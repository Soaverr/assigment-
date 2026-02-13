<?php
session_start();

include 'db_connect.php';


// ត្រួតពិនិត្យថា តើបានវាយលេខកូដត្រឹមត្រូវហើយឬនៅ?
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // បើមិនទាន់វាយកូដទេ បណ្តេញទៅទំព័រ login.php
    header("Location: login_admin.php");
    exit;
}


// មុខងារជំនួយសម្រាប់សុវត្ថិភាព
function h($v)
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

// --- ផ្នែកដោះស្រាយសកម្មភាពរបស់ Manager ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = (int)$_POST['id'];
    $action = (string)$_POST['action'];
    $remark = trim($_POST['remark'] ?? '');

    if ($id > 0) {
        if ($action === 'manager_approve') {
            $stmt = $pdo->prepare("UPDATE leave_requests SET status='Pending_HR', manager_decision='Approved', manager_remark=?, manager_decided_at=NOW(), updated_at=NOW() WHERE id=?");
            $stmt->execute([$remark, $id]);
        } elseif ($action === 'manager_reject') {
            $stmt = $pdo->prepare("UPDATE leave_requests SET status='Rejected', manager_decision='Rejected', manager_remark=?, manager_decided_at=NOW(), final_decision='Rejected', final_remark=?, final_decided_at=NOW(), updated_at=NOW() WHERE id=?");
            $stmt->execute([$remark, $remark, $id]);
        }
        header("Location: admin.php?success=1");
        exit;
    }
}

// ទាញទិន្នន័យ (Optimization: ប្រើ Query តែមួយសម្រាប់ Count បើចង់បានល្បឿន ប៉ុន្តែនេះក៏អូខេដែរ)
$pendingManager = $pdo->query("SELECT * FROM leave_requests WHERE status IN ('Pending_Manager','Pending') ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$pendingHR = $pdo->query("SELECT * FROM leave_requests WHERE status='Pending_HR' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$history = $pdo->query("SELECT * FROM leave_requests WHERE status IN ('Approved','Rejected') ORDER BY id DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="km">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --bg-body: #f4f7fe;
        }

        body {
            font-family: 'Kantumruy Pro', sans-serif;
            background-color: var(--bg-body);
            color: #2b2d42;
        }

        .main-content {
            padding: 2rem;
        }

        .card {
            border: none;
            border-radius: 16px;
            transition: all 0.3s ease;
        }

        .card-stats {
            border-bottom: 4px solid transparent;
        }

        .card-stats:hover {
            transform: translateY(-5px);
        }

        .icon-shape {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        .table thead th {
            background-color: #f8f9fa;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            font-weight: 700;
            color: #8d99ae;
            border-bottom: none;
        }

        .status-badge {
            padding: 0.5em 1em;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .btn-action-round {
            width: 35px;
            height: 35px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }

        .search-input {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 0.5rem 1rem;
        }

        footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .developer-info a {
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }

        .developer-info a:hover {
            background-color: var(--primary-color) !important;
            color: white !important;
            transform: translateY(-2px);
            display: inline-block;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <main class="col-lg-11 mx-auto main-content">

                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h2 class="fw-bold mb-1">ផ្ទាំងគ្រប់គ្រងសំណើសម្រាក</h2>
                        <p class="text-muted mb-0">គ្រប់គ្រង និងពិនិត្យមើលរាល់សំណើរបស់បុគ្គលិក</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-white shadow-sm border" onclick="location.reload();">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                        <a href="index.php" class="btn btn-primary shadow-sm px-4">
                            <i class="bi bi-plus-lg me-2"></i>បង្កើតសំណើថ្មី
                        </a>
                    </div>
                </div>

                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="card card-stats shadow-sm border-start-0 border-danger border-4 p-3">
                            <div class="d-flex align-items-center">
                                <div class="icon-shape bg-danger-subtle text-danger me-3">
                                    <i class="bi bi-envelope-exclamation-fill fs-4"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">សំណើរង់ចាំ</small>
                                    <span class="h3 fw-bold mb-0"><?= count($pendingManager) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-stats shadow-sm border-start-0 border-info border-4 p-3">
                            <div class="d-flex align-items-center">
                                <div class="icon-shape bg-info-subtle text-info me-3">
                                    <i class="bi bi-person-check-fill fs-4"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">កំពុងនៅ HR</small>
                                    <span class="h3 fw-bold mb-0"><?= count($pendingHR) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-stats shadow-sm border-start-0 border-success border-4 p-3">
                            <div class="d-flex align-items-center">
                                <div class="icon-shape bg-success-subtle text-success me-3">
                                    <i class="bi bi-calendar-check-fill fs-4"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">សម្រេចរួចរាល់</small>
                                    <span class="h3 fw-bold mb-0"><?= count($history) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-5">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-list-stars me-2"></i>សំណើថ្មីត្រូវពិនិត្យ</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-4">ព័ត៌មានបុគ្គលិក</th>
                                    <th>កាលបរិច្ឆេទសម្រាក</th>
                                    <th>មូលហេតុ</th>
                                    <th class="text-end pe-4">ការសម្រេចចិត្ត</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$pendingManager): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">មិនមានសំណើថ្មីសម្រាប់អ្នកឡើយ</td>
                                    </tr>
                                    <?php else: foreach ($pendingManager as $r): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-light rounded-circle p-2 me-3 text-center" style="width: 40px;">
                                                        <i class="bi bi-person text-secondary"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark"><?= h($r['employee_name']) ?></div>
                                                        <small class="text-muted">ID: #<?= h($r['id']) ?> | <?= h($r['branch']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?= h($r['start_date']) ?> <i class="bi bi-arrow-right text-muted small px-1"></i> <?= h($r['end_date']) ?></div>
                                                <small class="badge bg-light text-dark fw-normal border">សរុប <?= h($r['total_days']) ?> ថ្ងៃ</small>
                                            </td>
                                            <td>
                                                <div class="text-wrap" style="max-width: 250px; font-size: 0.9rem;"><?= h($r['reason']) ?></div>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <form method="POST" class="d-flex gap-2 justify-content-end align-items-center">
                                                    <input type="hidden" name="id" value="<?= h($r['id']) ?>">
                                                    <input type="text" name="remark" class="form-control form-control-sm search-input w-50" placeholder="មតិយោបល់...">
                                                    <button class="btn btn-success btn-action-round shadow-sm" name="action" value="manager_approve" title="យល់ព្រម" onclick="return confirm('បញ្ជូនទៅ HR?')">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                    <button class="btn btn-danger btn-action-round shadow-sm" name="action" value="manager_reject" title="បដិសេធ" onclick="return confirm('បដិសេធសំណើនេះ?')">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                <?php endforeach;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>ប្រវត្តិសំណើចុងក្រោយ</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">បុគ្គលិក</th>
                                    <th>ស្ថានភាព</th>
                                    <th>ថ្ងៃសម្រេច</th>
                                    <th class="text-end pe-4">សកម្មភាព</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $r): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-600 text-dark"><?= h($r['employee_name']) ?></span>
                                            <div class="small text-muted">ID: #<?= h($r['id']) ?></div>
                                        </td>
                                        <td>
                                            <?php if ($r['status'] === 'Approved'): ?>
                                                <span class="status-badge bg-success-subtle text-success"><i class="bi bi-check-circle-fill me-1"></i> បានយល់ព្រម</span>
                                            <?php else: ?>
                                                <span class="status-badge bg-danger-subtle text-danger"><i class="bi bi-x-circle-fill me-1"></i> បដិសេធ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?= date('d M, Y | h:i A', strtotime($r['final_decided_at'] ?: $r['updated_at'])) ?>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <div class="d-flex gap-2 justify-content-end">
                                                <a href="print_leave.php?id=<?= h($r['id']) ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                    <i class="bi bi-printer me-1"></i> មើល
                                                </a>
                                                <form action="delete_request.php" method="POST" onsubmit="return confirm('លុបទិន្នន័យនេះ?');">
                                                    <input type="hidden" name="id" value="<?= h($r['id']) ?>">
                                                    <input type="hidden" name="return_url" value="<?= $_SERVER['PHP_SELF'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0 ms-2">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

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