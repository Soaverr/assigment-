<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['is_hr']) || $_SESSION['is_hr'] !== true) {
    // បើមិនទាន់វាយកូដទេ បណ្តេញទៅទំព័រ login.php
    header("Location: login_hr.php");
    exit;
}


function h($v)
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

// --- Logic សម្រាប់ Approval/Rejection ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = (int)$_POST['id'];
    $action = (string)$_POST['action'];
    $remark = trim($_POST['remark'] ?? '');

    if ($id > 0) {
        if ($action === 'hr_approve') {
            $stmt = $pdo->prepare("UPDATE leave_requests SET status='Approved', final_decision='Approved', final_remark=?, final_decided_at=NOW(), updated_at=NOW() WHERE id=?");
            $stmt->execute([$remark, $id]);
        } elseif ($action === 'hr_reject') {
            $stmt = $pdo->prepare("UPDATE leave_requests SET status='Rejected', final_decision='Rejected', final_remark=?, final_decided_at=NOW(), updated_at=NOW() WHERE id=?");
            $stmt->execute([$remark, $id]);
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit;
    }
}

// ទាញយកទិន្នន័យ
$pendingHR = $pdo->query("SELECT * FROM leave_requests WHERE status='Pending_HR' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$history = $pdo->query("SELECT * FROM leave_requests WHERE status IN ('Approved','Rejected') ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="km">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HR Professional Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-color: #0dcaf0;
        }

        body {
            font-family: 'Kantumruy Pro', sans-serif;
            background-color: #f0f2f5;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            background: #fff;
            border-right: 1px solid #e0e0e0;
            z-index: 1000;
        }

        .nav-link {
            padding: 12px 20px;
            color: #555;
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 8px;
            margin: 4px 15px;
        }

        .nav-link.active,
        .nav-link:hover {
            background-color: var(--primary-color);
            color: white !important;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
        }

        .custom-card {
            background: #fff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 25px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .manager-quote {
            font-size: 0.85rem;
            color: #666;
            border-left: 3px solid var(--primary-color);
            padding-left: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        @media (max-width: 992px) {
            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="sidebar-header p-4 text-center">
            <h4 class="fw-bold text-info"><i class="bi bi-shield-lock-fill"></i> HR-MS</h4>
            <span class="badge bg-light text-dark border">Version 2.5</span>
        </div>
        <div class="mt-4">
            <a href="hr.php" class="nav-link active"><i class="bi bi-speedometer2"></i> ផ្ទាំងគ្រប់គ្រង</a>
            <a href="admin.php" class="nav-link"><i class="bi bi-people"></i> ផ្នែក Manager</a>
            <button onclick="exportToExcel()" class="nav-link border-0 bg-transparent w-100 text-start"><i class="bi bi-file-earmark-excel text-success"></i> ទាញយក Excel</button>
            <hr class="mx-3">
            <a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> ចាកចេញ</a>
        </div>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark m-0">ពិនិត្យសំណើសុំច្បាប់</h3>
                <p class="text-muted small">គ្រប់គ្រងការអនុម័ត និងរបាយការណ៍សរុប</p>
            </div>
            <div class="input-group w-25">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" id="searchInput" class="form-control border-start-0" placeholder="ស្វែងរកឈ្មោះបុគ្គលិក...">
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> រក្សាទុកទិន្នន័យជោគជ័យ!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="custom-card">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history text-warning me-2"></i>សំណើដែលកំពុងរង់ចាំ (Pending)</h5>
            </div>
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0" id="pendingTable">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">បុគ្គលិក</th>
                            <th>រយៈពេល</th>
                            <th>មតិ Manager</th>
                            <th class="text-center">សកម្មភាពសម្រេច</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$pendingHR): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">មិនមានសំណើថ្មីទេ</td>
                            </tr>
                            <?php else: foreach ($pendingHR as $r): ?>
                                <tr class="searchable-row">
                                    <td class="ps-4">
                                        <div class="fw-bold emp-name"><?= h($r['employee_name']) ?></div>
                                        <small class="text-muted">ID: #<?= h($r['id']) ?> | <?= h($r['branch']) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary"><?= h($r['total_days']) ?> ថ្ងៃ</div>
                                        <small class="text-muted"><?= date('d M', strtotime($r['start_date'])) ?> - <?= date('d M', strtotime($r['end_date'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="manager-quote">"<?= h($r['manager_remark'] ?: 'យល់ព្រម') ?>"</div>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-flex gap-2 justify-content-center">
                                            <input type="hidden" name="id" value="<?= h($r['id']) ?>">
                                            <input type="text" name="remark" class="form-control form-control-sm" style="width: 120px;" placeholder="មតិ HR...">
                                            <button class="btn btn-sm btn-info text-white px-3" name="action" value="hr_approve" onclick="return confirm('យល់ព្រម?')"><i class="bi bi-check-lg"></i></button>
                                            <button class="btn btn-sm btn-outline-danger" name="action" value="hr_reject" onclick="return confirm('បដិសេធ?')"><i class="bi bi-x-lg"></i></button>
                                        </form>
                                    </td>
                                </tr>
                        <?php endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="custom-card">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="mb-0 fw-bold text-secondary"><i class="bi bi-archive me-2"></i>ប្រវត្តិនៃការសម្រេច</h5>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="historyTable">
                    <thead>
                        <tr class="text-muted small">
                            <th class="ps-4">ID</th>
                            <th>ឈ្មោះ</th>
                            <th>ស្ថានភាព</th>
                            <th>ថ្ងៃសម្រេច</th>
                            <th class="text-center">ជម្រើស</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $r): ?>
                            <tr class="searchable-row">
                                <td class="ps-4">#<?= h($r['id']) ?></td>
                                <td class="fw-bold emp-name"><?= h($r['employee_name']) ?></td>
                                <td>
                                    <span class="status-badge <?= $r['status'] === 'Approved' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                                        <?= h($r['status']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= date('d-M-Y H:i', strtotime($r['final_decided_at'])) ?></td>
                                <td class="text-center">
                                    <a href="print_leave.php?id=<?= h($r['id']) ?>" target="_blank" class="btn btn-sm btn-light border shadow-sm">
                                        <i class="bi bi-printer text-info"></i> របាយការណ៍
                                    </a>
                                    <form action="delete_request.php" method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?= h($r['id']) ?>">
                                        <button type="submit" class="btn btn-sm btn-light border text-danger" onclick="return confirm('លុប?')"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

    <script>
        // មុខងារ Search
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('.searchable-row');
            rows.forEach(row => {
                let text = row.querySelector('.emp-name').textContent.toLowerCase();
                row.style.display = text.includes(filter) ? "" : "none";
            });
        });

        // មុខងារ Export Excel
        function exportToExcel() {
            let table = document.getElementById("historyTable");
            let wb = XLSX.utils.table_to_book(table, {
                sheet: "Leave History"
            });
            XLSX.writeFile(wb, "HR_Leave_Report.xlsx");
        }
    </script>
</body>

</html>