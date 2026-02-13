<?php
session_start();
session_destroy(); // លុប Session ទាំងអស់
header("Location: login.php"); // បញ្ជូនទៅទំព័រ Login វិញ
exit;
