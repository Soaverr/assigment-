<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $return_url = $_POST['return_url'] ?? 'admin.php'; // ត្រឡប់ទៅទំព័រដើមវិញក្រោយលុប

    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM leave_requests WHERE id = ?");
            $stmt->execute([$id]);

            // ជោគជ័យ៖ ត្រឡប់ទៅទំព័រដែលមកមុននេះ
            header("Location: " . $return_url . "?msg=deleted");
            exit;
        } catch (PDOException $e) {
            die("Error deleting record: " . $e->getMessage());
        }
    }
} else {
    // បើចូលមកទីនេះដោយអត់ចុចប៊ូតុង
    header("Location: admin.php");
    exit;
}
