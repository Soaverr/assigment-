<?php
include 'db_connect.php';

function h($v)
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

// ១. ទាញយកបញ្ជីសំណើដែលមិនទាន់បានអនុម័ត (Pending)
$stmt = $pdo->query("SELECT * FROM leave_requests WHERE status LIKE 'Pending%' ORDER BY id DESC");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ២. ដំណើរការនៅពេល Manager ចុច Approve
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_approve'])) {
    $id = (int)$_POST['request_id'];
    $remark = trim($_POST['final_remark'] ?? '');
    $manager_sig = $_POST['manager_signature'] ?? '';

    if ($manager_sig !== '') {
        $update = $pdo->prepare("
            UPDATE leave_requests 
            SET status = 'Approved', 
                final_remark = ?, 
                manager_signature = ?, 
                final_decided_at = NOW() 
            WHERE id = ?
        ");
        $update->execute([$remark, $manager_sig, $id]);
        header("Location: approve_leave.php?msg=success");
        exit;
    } else {
        $error = "សូមចុះហត្ថលេខាមុននឹងចុចអនុម័ត!";
    }
}
?>

<!DOCTYPE html>
<html lang="km">

<head>
    <meta charset="UTF-8">
    <title>ការអនុម័តច្បាប់សម្រាក - Manager/HR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kantumruy Pro', sans-serif;
            background: #f4f7f6;
        }

        .sig-canvas-admin {
            border: 2px dashed #198754;
            background: #fff;
            border-radius: 8px;
            cursor: crosshair;
            touch-action: none;
        }

        .card-request {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: 0.3s;
        }

        .card-request:hover {
            transform: translateY(-5px);
        }
    </style>
</head>

<body>

    <div class="container py-5">
        <h3 class="fw-bold mb-4 text-center text-primary">ផ្ទាំងគ្រប់គ្រង និងអនុម័តច្បាប់សម្រាក</h3>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">អនុម័តសំណើរួចរាល់ដោយជោគជ័យ!</div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="row">
            <?php foreach ($requests as $r): ?>
                <div class="col-md-6 mb-4">
                    <div class="card card-request p-4">
                        <div class="d-flex justify-content-between">
                            <h5 class="fw-bold text-dark"><?= h($r['employee_name']) ?></h5>
                            <span class="badge bg-warning text-dark">ID: #<?= $r['id'] ?></span>
                        </div>
                        <hr>
                        <p class="mb-1"><strong>សាខា៖</strong> <?= h($r['branch']) ?></p>
                        <p class="mb-1"><strong>មូលហេតុ៖</strong> <?= h($r['reason']) ?></p>
                        <p class="mb-1"><strong>រយៈពេល៖</strong> <?= h($r['total_days']) ?> ថ្ងៃ (<?= date('d/m/Y', strtotime($r['start_date'])) ?> ដល់ <?= date('d/m/Y', strtotime($r['end_date'])) ?>)</p>

                        <div class="mt-2 mb-3">
                            <small class="text-muted">ហត្ថលេខាបុគ្គលិក៖</small><br>
                            <img src="<?= $r['signature_data'] ?>" style="height: 50px; border-bottom: 1px solid #ddd;">
                        </div>

                        <form method="POST" class="mt-3 border-top pt-3">
                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">

                            <div class="mb-3">
                                <label class="form-label fw-bold">មតិយោបល់ (បើមាន)៖</label>
                                <input type="text" name="final_remark" class="form-control" placeholder="ឧទាហរណ៍៖ យល់ព្រម">
                            </div>

                            <div class="mb-3 text-center">
                                <label class="form-label fw-bold text-success d-block text-start">ហត្ថលេខា Manager អនុម័ត៖</label>
                                <canvas id="sig-<?= $r['id'] ?>" class="sig-canvas-admin" width="350" height="120"></canvas>
                                <input type="hidden" name="manager_signature" id="input-sig-<?= $r['id'] ?>">
                                <div class="mt-1">
                                    <button type="button" class="btn btn-sm btn-link text-danger" onclick="clearCanvas(<?= $r['id'] ?>)">លុបហត្ថលេខា</button>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="btn_approve" class="btn btn-success fw-bold" onclick="saveSig(<?= $r['id'] ?>)">
                                    ✅ ពិនិត្យ និងអនុម័តសំណើ
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($requests)): ?>
                <div class="col-12 text-center text-muted py-5">
                    <h4>🎉 មិនមានសំណើរង់ចាំការអនុម័តទេ</h4>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const canvases = {};
        const contexts = {};

        // កំណត់ដំណើរការ Canvas សម្រាប់គ្រប់សំណើទាំងអស់
        document.querySelectorAll('.sig-canvas-admin').forEach(canvas => {
            const id = canvas.id.split('-')[1];
            const ctx = canvas.getContext('2d');
            contexts[id] = ctx;
            canvases[id] = canvas;

            ctx.lineWidth = 3;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#00008B'; // ពណ៌ខៀវសម្រាប់ហត្ថលេខាមេ

            let drawing = false;

            const getPos = (e) => {
                const rect = canvas.getBoundingClientRect();
                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                return {
                    x: clientX - rect.left,
                    y: clientY - rect.top
                };
            };

            const start = (e) => {
                drawing = true;
                ctx.beginPath();
                const p = getPos(e);
                ctx.moveTo(p.x, p.y);
            };
            const draw = (e) => {
                if (!drawing) return;
                e.preventDefault();
                const p = getPos(e);
                ctx.lineTo(p.x, p.y);
                ctx.stroke();
            };
            const stop = () => {
                drawing = false;
            };

            canvas.addEventListener('mousedown', start);
            canvas.addEventListener('mousemove', draw);
            window.addEventListener('mouseup', stop);

            canvas.addEventListener('touchstart', (e) => {
                e.preventDefault();
                start(e);
            }, {
                passive: false
            });
            canvas.addEventListener('touchmove', (e) => {
                e.preventDefault();
                draw(e);
            }, {
                passive: false
            });
            canvas.addEventListener('touchend', stop);
        });

        function clearCanvas(id) {
            contexts[id].clearRect(0, 0, canvases[id].width, canvases[id].height);
            document.getElementById('input-sig-' + id).value = '';
        }

        function saveSig(id) {
            const dataUrl = canvases[id].toDataURL();
            // បើគូរទទេ វានឹងចេញទិន្នន័យខ្លីមួយ ប៉ុន្តែយើងគួរឆែក
            document.getElementById('input-sig-' + id).value = dataUrl;
        }
    </script>

</body>

</html>