<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "overflow_db";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // ករណី database មិនទាន់មាន → បង្កើត rồi reconnect
    if (stripos($e->getMessage(), 'Unknown database') !== false) {
        try {
            $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e2) {
            die("ការតភ្ជាប់បរាជ័យ: " . $e2->getMessage());
        }
    } else {
        die("ការតភ្ជាប់បរាជ័យ: " . $e->getMessage());
    }
}

// បង្កើត/ធ្វើ upgrade table (សម្រាប់ flow: Employee → Manager → HR → Manager → Employee)
$pdo->exec("
CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_name VARCHAR(100) NOT NULL,
    branch VARCHAR(100) NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    total_days INT NULL,
    reason TEXT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Pending_Manager',

    manager_decision VARCHAR(20) NULL,
    manager_remark TEXT NULL,
    manager_decided_at DATETIME NULL,

    hr_decision VARCHAR(20) NULL,
    hr_remark TEXT NULL,
    hr_decided_at DATETIME NULL,

    final_decision VARCHAR(20) NULL,
    final_remark TEXT NULL,
    final_decided_at DATETIME NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// បន្ថែម column ដែលខ្វះ (ករណី table ចាស់)
try {
    $existingCols = $pdo->query("SHOW COLUMNS FROM leave_requests")->fetchAll(PDO::FETCH_COLUMN, 0);
    $needed = [
        'status' => "status VARCHAR(50) NOT NULL DEFAULT 'Pending_Manager'",
        'manager_decision' => "manager_decision VARCHAR(20) NULL",
        'manager_remark' => "manager_remark TEXT NULL",
        'manager_decided_at' => "manager_decided_at DATETIME NULL",
        'hr_decision' => "hr_decision VARCHAR(20) NULL",
        'hr_remark' => "hr_remark TEXT NULL",
        'hr_decided_at' => "hr_decided_at DATETIME NULL",
        'final_decision' => "final_decision VARCHAR(20) NULL",
        'final_remark' => "final_remark TEXT NULL",
        'final_decided_at' => "final_decided_at DATETIME NULL",
        'updated_at' => "updated_at DATETIME NULL",
        'branch' => "branch VARCHAR(100) NULL",
        'start_date' => "start_date DATE NULL",
        'end_date' => "end_date DATE NULL",
        'total_days' => "total_days INT NULL",
        'reason' => "reason TEXT NULL",
        'created_at' => "created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
    ];

    foreach ($needed as $col => $def) {
        if (!in_array($col, $existingCols, true)) {
            $pdo->exec("ALTER TABLE leave_requests ADD COLUMN $def");
        }
    }
} catch (PDOException $e) {
    // ប្រសិនបើ table/privilege មានបញ្ហា → អោយ app បន្តដំណើរការ តែអាចបាត់ feature ខ្លះ
}
