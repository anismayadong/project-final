<?php
session_start();
require_once "../includes/db_connect.php";

// ตรวจสอบสิทธิ์ผู้ดูแลระบบ
if (!isset($_SESSION["role_type"]) || $_SESSION["role_type"] !== "admin") {
    header("Location: ../admin/admin_login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $game_name = trim($_POST["game_name"]);
    $description = trim($_POST["description"]);
    $difficulty = $_POST["difficulty"];
    $game_url = trim($_POST["game_url"]);
    $cover_image = "";

    // จัดการอัปโหลดภาพหน้าปก
    if (!empty($_FILES["cover_image"]["name"])) {
        $targetDir = "../uploads/";
        $fileName = basename($_FILES["cover_image"]["name"]);
        $targetFile = $targetDir . uniqid() . "_" . $fileName;

        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($imageFileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES["cover_image"]["tmp_name"], $targetFile)) {
                $cover_image = basename($targetFile);
            }
        }
    }

    // บันทึกข้อมูลลงฐานข้อมูล
    $stmt = $conn->prepare("INSERT INTO games (game_name, description, difficulty, game_url, cover_image, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $game_name, $description, $difficulty, $game_url, $cover_image);

    if ($stmt->execute()) {
        header("Location: ../admin/admin_dashboard.php?success=1");
        exit;
    } else {
        echo "เกิดข้อผิดพลาดในการบันทึกเกม: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "การเข้าถึงไม่ถูกต้อง";
}
