<?php
session_start();

// កំណត់លេខកូដសម្ងាត់នៅទីនេះ
$correct_pin = "99999999";

if (isset($_POST['login'])) {
    $entered_pin = $_POST['pin'] ?? '';

    if ($entered_pin === $correct_pin) {
        $_SESSION['is_index'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = "លេខកូដមិនត្រឹមត្រូវទេ!";
    }
}
?>

<!DOCTYPE html>
<html lang="km">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kantumruy Pro', sans-serif;
            background: #f4f7fe;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pin-card {
            max-width: 350px;
            width: 100%;
            padding: 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .pin-input {
            letter-spacing: 5px;
            text-align: center;
            font-size: 1.5rem;
            border-radius: 12px;
        }
    </style>
</head>

<body>
    <div class="pin-card text-center">
        <div class="mb-4">
            <i class="bi bi-shield-lock-fill text-primary" style="font-size: 3rem;"></i>
            <h4 class="fw-bold mt-2">វាយលេខកូដសម្ងាត់</h4>
            <p class="text-muted small">សូមបញ្ចូលលេខកូដដើម្បីបន្តទៅកាន់ ទព័សុំច្បាប់</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger py-2 small"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <input type="password" name="pin" class="form-control pin-input" placeholder="••••••••" autofocus required>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100 py-2 fw-bold" style="border-radius: 12px;">ផ្ទៀងផ្ទាត់</button>
        </form>
    </div>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</body>

</html>