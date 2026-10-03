<?php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION["role_type"]) || $_SESSION["role_type"] !== "admin") {
    header("Location: ../admin/admin_login.php");
    exit;
}

if (!isset($_GET["id"])) {
    echo "ไม่พบเกมที่ต้องการลบ";
    exit;
}

$game_id = (int)$_GET["id"];

// ลบเกม
$stmt = $conn->prepare("DELETE FROM games WHERE game_id = ?");
$stmt->bind_param("i", $game_id);

if ($stmt->execute()) {
    header("Location: ../admin/admin_dashboard.php?delete_success=1");
    exit;
} else {
    echo "เกิดข้อผิดพลาดในการลบ: " . $conn->error;
}

$stmt->close();
$conn->close();
